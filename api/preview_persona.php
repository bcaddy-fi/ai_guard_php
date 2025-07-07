<?php
require '../includes/auth.php';
require_login();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Read JSON body input
$input = json_decode(file_get_contents('php://input'), true);
$personaYaml = $input['yaml'] ?? '';
$prompt = $input['prompt'] ?? 'How would you respond to a common user request?';

if (!$personaYaml) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing persona YAML']);
    exit;
}

require '../vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

try {
    $persona = Yaml::parse($personaYaml);

    $context = $persona['persona']['description'] ?? 'No context provided.';
    $rules = implode("\n", $persona['persona']['rules'] ?? []);
    $tone = $persona['persona']['tone'] ?? 'neutral';

    $fullPrompt = <<<EOT
You are acting as an AI assistant with the following persona.

Context:
$context

Rules:
$rules

Tone: $tone

User Prompt:
$prompt
EOT;

    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) {
        throw new Exception("Missing OpenAI API key");
    }

    $payload = json_encode([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are an AI persona acting on behalf of an internal tool.'],
            ['role' => 'user', 'content' => $fullPrompt]
        ],
        'temperature' => 0.6,
    ]);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'content' => $payload
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);

    if ($response === false) {
        $error = error_get_last();
        throw new Exception('API request failed: ' . ($error['message'] ?? 'Unknown error'));
    }

    $data = json_decode($response, true);

    $reply = $data['choices'][0]['message']['content'] ?? 'No response received.';
    echo json_encode(['reply' => $reply]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'LLM Preview failed',
        'details' => $e->getMessage()
    ]);
}
