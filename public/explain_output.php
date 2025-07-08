<?php
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../app/controllers/openai_inference.php';

$apiKey = getenv('OPENAI_API_KEY');

function run_ai_test($input, $context_yaml) {
    global $apiKey;

    $response = call_openai_api([
        'model' => 'gpt-4',
        'temperature' => 0.3,
        'messages' => [
            ['role' => 'system', 'content' => $context_yaml],
            ['role' => 'user', 'content' => $input]
        ]
    ]);

    $raw = $response['choices'][0]['message']['content'] ?? '';
    $content = strtolower($raw);

    if (strpos($content, 'block') !== false || strpos($content, 'deny') !== false) {
        return ['result' => 'block', 'raw' => $raw];
    }
    return ['result' => 'pass', 'raw' => $raw];
}

function find_matching_rules($prompt, $response, $yamlRules) {
    $matched = [];
    if (!is_array($yamlRules)) return $matched;

    $flatRules = new RecursiveIteratorIterator(new RecursiveArrayIterator($yamlRules));
    foreach ($flatRules as $key => $rule) {
        if (!is_string($rule)) continue;
        if (stripos($prompt, $rule) !== false || stripos($response, $rule) !== false) {
            $matched[] = $rule;
        }
    }
    return array_unique($matched);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing test case ID.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM test_cases WHERE id = ?");
$stmt->execute([$id]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
    echo json_encode(['success' => false, 'message' => 'Test case not found.']);
    exit;
}

$input = $test['input_text'];
$expected = $test['expected_behavior'];
$contextYaml = '';
$type = $test['type'];
$reference_id = $test['reference_id'];

if ($type === 'persona') {
    $stmt = $pdo->prepare("SELECT file_path FROM personas WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT file_path FROM guardrails WHERE id = ?");
}

$stmt->execute([$reference_id]);
$row = $stmt->fetch();

$yamlPath = __DIR__ . '/../' . ($row['file_path'] ?? '');
if (!$row || !is_readable($yamlPath)) {
    echo json_encode(['success' => false, 'message' => 'YAML file not found.']);
    exit;
}

$yamlText = file_get_contents($yamlPath);
$rules = yaml_parse_file($yamlPath);

$aiResult = run_ai_test($input, $yamlText);
$response = $aiResult['result'];
$responseRaw = $aiResult['raw'];
$matchedRules = find_matching_rules($input, $responseRaw, $rules);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'input' => $input,
    'expected' => $expected,
    'actual' => $response,
    'response_raw' => $responseRaw,
    'matched_rules' => $matchedRules
]);
