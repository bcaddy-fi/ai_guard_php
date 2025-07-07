<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();

header('Content-Type: application/json');

// Parse incoming JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['prompt', 'response', 'filename', 'user', 'policy_type'];
$missing = array_filter($required, fn($key) => empty($data[$key]), ARRAY_FILTER_USE_KEY);

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required fields',
        'missing_fields' => array_values($missing)
    ]);
    exit;
}

try {
    file_put_contents('/tmp/logger_debug.txt', json_encode($data, JSON_PRETTY_PRINT));

    $stmt = $pdo->prepare("
        INSERT INTO api_log (user, filename, policy_type, prompt, response)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['user'],
        $data['filename'],
        $data['policy_type'],
        $data['prompt'],
        $data['response']
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    file_put_contents('/tmp/logger_debug.txt', $e->getMessage(), FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'DB error', 'details' => $e->getMessage()]);
}
