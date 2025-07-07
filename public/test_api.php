<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();

header('Content-Type: application/json');

// Log start
file_put_contents('/tmp/api_debug_start.txt', "Script started at " . date('c') . "\n", FILE_APPEND);

// Composer autoload
require __DIR__ . '/../vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

// Gather inputs
$personaYaml = $_POST['persona'] ?? '';
$filename = $_POST['filename'] ?? '';
$prompt = trim($_POST['prompt'] ?? '');

// Log received POST data (excluding raw persona YAML for size)
file_put_contents('/tmp/api_debug_post.txt', json_encode([
    'filename' => $filename,
    'prompt' => $prompt,
    'has_persona' => $personaYaml ? true : false
], JSON_PRETTY_PRINT));

// Validate inputs
if (!$prompt || (!$personaYaml && !$filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing prompt or persona content']);
    exit;
}

// Load YAML file if not passed
if (!$personaYaml && $filename) {
    $safeFilename = basename($filename);
    $filePath = __DIR__ . '/../data/persona/' . $safeFilename;
    if (file_exists($filePath)) {
        $personaYaml = file_get_contents($filePath);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Persona file not found']);
        exit;
    }
}

// Log raw YAML
file_put_contents('/tmp/api_debug_yaml.txt', $personaYaml ?: 'EMPTY');

// Parse YAML
try {
    $parsed = Yaml::parse($personaYaml);
} catch (Exception $e) {
    file_put_contents('/tmp/api_debug_yaml_error.txt', $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'YAML parsing failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

// Extract persona config
$personaContext = $parsed['persona']['description'] ?? '';
$rules = implode("\n", $parsed['persona']['rules'] ?? []);
$fallback = $parsed['persona']['fallback_response'] ?? 'This request violates policy.';

// Final assembled prompt
$fullPrompt = <<<EOT
You are acting as the following AI Persona:

Context:
$personaContext

Rules:
$rules

If any rule is violated or an inappropriate topic is detected, respond with:
$fallback

User Prompt:
$prompt
EOT;

// Save full prompt for review
file_put_contents('/tmp/api_payload.json', json_encode(['prompt' => $fullPrompt], JSON_PRETTY_PRINT));

// Extract API key from /etc/environment
$apiKey = getenv('OPENAI_API_KEY');
$envLines = @file('/etc/environment', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (str_starts_with(trim($line), 'OPENAI_API_KEY=')) {
        $apiKey = trim(explode('=', $line, 2)[1]);
        break;
    }
}

// Save key to verify
file_put_contents('/tmp/api_key.txt', $apiKey ?: 'MISSING KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'OPENAI_API_KEY not found in /etc/environment']);
    exit;
}

// Prepare API payload
$payload = json_encode([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'user', 'content' => $fullPrompt]
    ],
    'temperature' => 0.7
]);

// Call OpenAI API
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

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Log raw API response
file_put_contents('/tmp/api_response.json', $result ?: 'NO RESPONSE');

// Error handling
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI call failed', 'details' => $error]);
    exit;
}
if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => "OpenAI returned HTTP $httpCode", 'details' => $result]);
    exit;
}

// Echo result to frontend
echo $result;

// Parse response for logging
$responseData = json_decode($result, true);
$responseText = $responseData['choices'][0]['message']['content'] ?? null;

// --- DB Logging ---
require __DIR__ . '/../app/controllers/db.php';

if ($responseText) {
    try {
        $stmt = $pdo->prepare("INSERT INTO api_log (user, filename, policy_type, prompt, response) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? 'unknown',
            $filename,
            'persona',
            $prompt,
            $responseText
        ]);
        file_put_contents('/tmp/api_log_success.txt', "Logged at " . date('c') . "\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents('/tmp/api_log_error.txt', $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    file_put_contents('/tmp/api_log_error.txt', "Missing response text\n", FILE_APPEND);
}
