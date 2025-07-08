<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');

require __DIR__ . '/../app/controllers/db.php'; // Provides $pdo
require __DIR__ . '/../app/llm/test_runner.php'; // Provides run_llm_test()

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$testId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$testId) {
    echo json_encode(['success' => false, 'message' => 'Missing test case ID.']);
    exit;
}

// Fetch test case
$stmt = $pdo->prepare("SELECT * FROM test_cases WHERE id = ?");
$stmt->execute([$testId]);
$testCase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testCase) {
    echo json_encode(['success' => false, 'message' => 'Test case not found.']);
    exit;
}

// Get YAML
$type = $testCase['type'];
$refId = (int) $testCase['reference_id'];
$filename = '';
if ($type === 'persona') {
    $stmt = $pdo->prepare("SELECT name FROM personas WHERE id = ?");
    $stmt->execute([$refId]);
    $name = $stmt->fetchColumn();
    if ($name) {
        $filename = realpath(__DIR__ . '/../data/persona/' . $name . '.yaml');
    }
} elseif ($type === 'guardrail') {
    $stmt = $pdo->prepare("SELECT name FROM guardrails WHERE id = ?");
    $stmt->execute([$refId]);
    $name = $stmt->fetchColumn();
    if ($name) {
        $filename = realpath(__DIR__ . '/../data/guardrails/' . $name . '.yaml');
    }
}

if (!$filename || !is_file($filename)) {
    echo json_encode(['success' => false, 'message' => 'YAML file not found.']);
    exit;
}

$yaml = file_get_contents($filename);
if (!$yaml) {
    echo json_encode(['success' => false, 'message' => 'Failed to read YAML file.']);
    exit;
}

// Add YAML to test case array
$testCase['yaml'] = $yaml;

// Run the test
try {
    $passed = run_llm_test($pdo, $testCase);
    echo json_encode([
        'success' => true,
        'result' => $passed ? 'Pass' : 'Failed',
        'message' => 'Test executed.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Test error: ' . $e->getMessage()
    ]);
}
exit;
