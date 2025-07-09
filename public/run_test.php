<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');
require_once 'includes/waf.php'; // WAF protection

require __DIR__ . '/../app/controllers/db.php';       // $pdo
require __DIR__ . '/../app/llm/test_runner.php';     // run_llm_test()

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

$type = $testCase['type'];
$refId = (int) $testCase['reference_id'];
$filename = '';
$baseDir = __DIR__ . '/../data/';

// Prefer explicit file metadata
if (!empty($testCase['yaml_file']) && !empty($testCase['yaml_dir'])) {
    $filename = realpath($baseDir . $testCase['yaml_dir'] . '/' . $testCase['yaml_file']);
} else {
    // Fallback to legacy DB lookup if yaml_file/yaml_dir not set
    $lookupTable = '';
    if ($type === 'persona') $lookupTable = 'personas';
    elseif ($type === 'guardrail') $lookupTable = 'guardrails';
    elseif ($type === 'model') $lookupTable = 'models';
    elseif ($type === 'agent') $lookupTable = 'agents';

    if ($lookupTable) {
        $stmt = $pdo->prepare("SELECT yaml_file FROM $lookupTable WHERE id = ?");
        $stmt->execute([$refId]);
        $yamlFile = $stmt->fetchColumn();
        if ($yamlFile) {
            $defaultDir = match($type) {
                'persona'   => 'personas',
                'guardrail' => 'guardrails',
                'model'     => 'models',
                'agent'     => 'agent_rules',
            };
            $filename = realpath($baseDir . $defaultDir . '/' . $yamlFile);
        }
    }
}

// Fail if no file found
if (!$filename || !is_file($filename)) {
    echo json_encode(['success' => false, 'message' => 'YAML file not found.']);
    exit;
}

// Load YAML
$yaml = file_get_contents($filename);
if (!$yaml) {
    echo json_encode(['success' => false, 'message' => 'Failed to read YAML file.']);
    exit;
}

// Inject YAML into test case
$testCase['yaml'] = $yaml;

// Run test
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
