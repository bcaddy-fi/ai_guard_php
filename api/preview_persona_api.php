<?php
require '../includes/auth.php'; require_login();
require '../includes/db.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');

$personaYaml = $_POST['persona'] ?? '';
$prompt = $_POST['prompt'] ?? '';

if (!$personaYaml || !$prompt) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing persona or prompt']);
    exit;
}

// Parse YAML (if needed)
require '../vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

try {
    $persona = Yaml::parse($personaYaml);
    $personaContext = $persona['persona']['description'] ?? '';
    $rules = implode("\n", $persona['persona']['rules'] ?? []);

    $fullPrompt = <<<EOT
You are acting as the following AI Persona:

Context:
$personaContext

Rules:
$rules

User Prompt:
$prompt
EOT;

    // Send to OpenAI or Nemo (example for OpenAI):
    $apiKey = getenv('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');
    $response = file_get_contents("https://api.openai.com/v1/chat/completions", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
            'content' => json_encode([
                'model' => 'gpt-4',
                'messages' => [['role' => 'user', 'content' => $fullPrompt]],
                'temperature' => 0.7
            ])
        ]
    ]));

    echo $response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Processing failed', 'details' => $e->getMessage()]);
}
