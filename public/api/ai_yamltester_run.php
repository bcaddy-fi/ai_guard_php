<?php
require __DIR__ . '/../../app/controllers/auth.php';
require_login();
require __DIR__ . '/../../app/controllers/db.php';
require_once __DIR__ . '/../../app/controllers/openai_inference.php'; // make sure this defines call_openai_api()

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

$yaml = $_POST['yaml'] ?? '';
$prompt = trim($_POST['prompt'] ?? '');
$filename = $_POST['filename'] ?? '';
$user = $_SESSION['user_id'] ?? 'unknown';

if (!$yaml || !$prompt) {
    echo json_encode(['error' => 'YAML and prompt are required.']);
    exit;
}

try {
    $messages = [
        ['role' => 'system', 'content' => $yaml],
        ['role' => 'user', 'content' => $prompt],
    ];

    $response = call_openai_api([
        'model' => 'gpt-4',
        'temperature' => 0.2,
        'messages' => $messages
    ]);

    $reply = $response['choices'][0]['message']['content'] ?? '';

    $stmt = $pdo->prepare("
INSERT INTO api_log (user, filename, policy_type, prompt, response) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $user,
    $filename,
    'guardrail',
    $prompt,
    json_encode($response, JSON_PRETTY_PRINT)
]);
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'model' => 'gpt-4',
        'messages' => $messages,
        'raw_response' => $response,
        'answer' => $reply,
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
