<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();

require __DIR__ . '/../app/controllers/db.php';

header('Content-Type: application/json');

// Debug start of script
file_put_contents('/tmp/log_top_of_script.txt', "Top of script reached at " . date('c') . PHP_EOL, FILE_APPEND);

// Validate request type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('/tmp/log_error_method.txt', "Invalid method: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL, FILE_APPEND);
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Read and log raw input
$rawInput = file_get_contents('php://input');
file_put_contents('/tmp/log_raw_input.json', $rawInput . PHP_EOL, FILE_APPEND);

$data = json_decode($rawInput, true);

// Validate required fields
$required = ['prompt', 'response', 'filename', 'user', 'policy_type'];
$missing = array_filter($required, fn($k) => empty($data[$k]));

if (!empty($missing)) {
    file_put_contents('/tmp/log_missing_fields.txt', json_encode($missing) . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields', 'missing' => $missing]);
    exit;
}

// Pre-insert debug
file_put_contents('/tmp/log_before_insert.txt', "Attempting insert at " . date('c') . PHP_EOL, FILE_APPEND);

try {
    $stmt = $pdo->prepare("
        INSERT INTO api_log (user, filename, policy_type, prompt, response)
        VALUES (:user, :filename, :policy_type, :prompt, :response)
    ");
    $stmt->execute([
        ':user'        => $data['user'],
        ':filename'    => $data['filename'],
        ':policy_type' => $data['policy_type'],
        ':prompt'      => $data['prompt'],
        ':response'    => $data['response']
    ]);

    file_put_contents('/tmp/log_after_insert.txt', "Insert success at " . date('c') . PHP_EOL, FILE_APPEND);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    file_put_contents('/tmp/log_exception.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
