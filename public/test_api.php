<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

header('Content-Type: application/json');
require __DIR__ . '/../vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

// Log script start
file_put_contents('/tmp/api_debug_start.txt', "Script started at " . date('c') . "\n", FILE_APPEND);

// Input
$type = $_POST['type'] ?? '';
$filename = $_POST['filename'] ?? '';
$yamlText = $_POST['yaml'] ?? '';
$prompt = trim($_POST['prompt'] ?? '');

// Log input summary
file_put_contents('/tmp/api_debug_post.txt', json_encode([
    'type' => $type,
    'filename' => $filename,
    'prompt' => $prompt,
    'has_yaml' => $yamlText ? true : false
], JSON_PRETTY_PRINT));

// Validate
if (!$prompt || (!$yamlText && !$filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing prompt or YAML content']);
    exit;
}

// Resolve filename path
$dirMap = [
    'persona' => '/../data/persona/',
    'guardrail' => '/../data/guardrails/',
    'agent' => '/../data/agent_rules/',
    'model' => '/../data/models/'
];

if (!isset($dirMap[$type])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown type']);
    exit;
}

if (!$yamlText && $filename) {
    $path = __DIR__ . $dirMap[$type] . basename($filename);
    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['error' => ucfirst($type) . ' file not found']);
        exit;
    }
    $yamlText = file_get_contents($path);
}

file_put_contents('/tmp/api_debug_yaml.txt', $yamlText);

// Parse YAML
try {
    $parsed = Yaml::parse($yamlText);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'YAML parsing failed', 'details' => $e->getMessage()]);
    exit;
}

// Build prompt
switch ($type) {
    case 'persona':
        $context = $parsed['persona']['description'] ?? '';
        $rules = implode("\n", $parsed['persona']['rules'] ?? []);
        $fallback = $parsed['persona']['fallback_response'] ?? 'This request violates policy.';
        $fullPrompt = <<<EOT
You are acting as the following AI Persona:

Context:
$context

Rules:
$rules

If any rule is violated or an inappropriate topic is detected, respond with:
$fallback

User Prompt:
$prompt
EOT;
        break;

    case 'guardrail':
        $trigger = implode("\n", $parsed['guardrail']['triggers'] ?? []);
        $response = $parsed['guardrail']['response'] ?? 'This input violates policy.';
        $fullPrompt = <<<EOT
This is a guardrail test.

Trigger Phrases:
$trigger

Expected Response:
$response

User Prompt:
$prompt
EOT;
        break;

    case 'agent':
        $intro = $parsed['agent']['intro'] ?? 'AI agent';
        $capabilities = implode("\n", $parsed['agent']['capabilities'] ?? []);
        $rules = implode("\n", $parsed['agent']['rules'] ?? []);
        $fullPrompt = <<<EOT
You are an AI Agent: $intro

Capabilities:
$capabilities

Rules:
$rules

User Prompt:
$prompt
EOT;
        break;

    case 'model':
        $name = $parsed['model'] ?? 'Unknown';
        $context = json_encode($parsed['metadataOverrides'] ?? [], JSON_PRETTY_PRINT);
        $fullPrompt = <<<EOT
You are testing the following LLM model: $name

Metadata:
$context

Test Prompt:
$prompt
EOT;
        break;
}

file_put_contents('/tmp/api_payload.json', json_encode(['prompt' => $fullPrompt], JSON_PRETTY_PRINT));

// API Key
$apiKey = getenv('OPENAI_API_KEY');
foreach (@file('/etc/environment', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), 'OPENAI_API_KEY=')) {
        $apiKey = trim(explode('=', $line, 2)[1]);
        break;
    }
}
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'OPENAI_API_KEY not set']);
    exit;
}

// Call OpenAI
$payload = json_encode([
    'model' => 'gpt-4',
    'messages' => [['role' => 'user', 'content' => $fullPrompt]],
    'temperature' => 0.7
]);

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
file_put_contents('/tmp/api_response.json', $response ?: 'NO RESPONSE');

if (!$response) {
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI call failed', 'details' => $error]);
    exit;
}
if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => "OpenAI returned HTTP $httpCode", 'details' => $response]);
    exit;
}

$data = json_decode($response, true);
$text = $data['choices'][0]['message']['content'] ?? 'NO OUTPUT';
$tokenUsage = $data['usage']['total_tokens'] ?? null;

$userId = $_SESSION['user_id'] ?? null;
$modelUsed = 'gpt-4';
$status = $httpCode === 200 ? 'success' : 'error';
$errorMessage = $httpCode === 200 ? null : "HTTP $httpCode: $response";

// Log to legacy api_log
try {
    $stmt = $pdo->prepare("INSERT INTO api_log (user, filename, policy_type, prompt, response) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId ?? 'unknown',
        $filename,
        $type,
        $prompt,
        $text
    ]);
} catch (Exception $e) {
    file_put_contents('/tmp/api_log_error.txt', $e->getMessage());
}

// Log to openai_log for analytics
try {
    $stmt = $pdo->prepare("
        INSERT INTO openai_log (
            user_id, persona_name, guardrail_name, model_used,
            prompt, response, token_usage, status, error_message,
            temperature, max_tokens, top_p, frequency_penalty, presence_penalty
        ) VALUES (
            :user_id, :persona_name, :guardrail_name, :model_used,
            :prompt, :response, :token_usage, :status, :error_message,
            :temperature, :max_tokens, :top_p, :frequency_penalty, :presence_penalty
        )
    ");

    $stmt->bindValue(':user_id', $userId ?? null, $userId ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':persona_name', $type === 'persona' ? $filename : null);
    $stmt->bindValue(':guardrail_name', $type === 'guardrail' ? $filename : null);
    $stmt->bindValue(':model_used', $modelUsed);
    $stmt->bindValue(':prompt', $fullPrompt);
    $stmt->bindValue(':response', $text);
    $stmt->bindValue(':token_usage', $tokenUsage ?? null, is_numeric($tokenUsage) ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':error_message', $errorMessage);
    $stmt->bindValue(':temperature', 0.7);
    $stmt->bindValue(':max_tokens', null, PDO::PARAM_NULL); // You can replace with actual value if used
    $stmt->bindValue(':top_p', null, PDO::PARAM_NULL);
    $stmt->bindValue(':frequency_penalty', null, PDO::PARAM_NULL);
    $stmt->bindValue(':presence_penalty', null, PDO::PARAM_NULL);

    $stmt->execute();

    file_put_contents('/tmp/openai_log_success.txt', "Logged successfully\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents('/tmp/openai_log_exception.txt', $e->getMessage(), FILE_APPEND);
}

// Echo the original response
echo $response;
