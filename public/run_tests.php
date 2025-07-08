<?php
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../app/controllers/openai_inference.php';

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

    $content = strtolower($response['choices'][0]['message']['content'] ?? '');

    if (strpos($content, 'block') !== false || strpos($content, 'deny') !== false) {
        return 'block';
    }
    return 'pass';
}

function find_matching_rules($prompt, $response, $yamlRules) {
    $matched = [];
    foreach ($yamlRules as $rule) {
        if (stripos($prompt, $rule) !== false || stripos($response, $rule) !== false) {
            $matched[] = $rule;
        }
    }
    return $matched;
}

// Get test cases
$stmt = $pdo->query("SELECT * FROM test_cases");
$testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];

foreach ($testCases as $test) {
    $contextYaml = '';
    $reference_id = $test['reference_id'];
    $input = $test['input_text'];
    $expected = $test['expected_behavior'];

    if ($test['type'] === 'persona') {
        $stmt = $pdo->prepare("SELECT file_path FROM personas WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT file_path FROM guardrails WHERE id = ?");
    }
    $stmt->execute([$reference_id]);
    $row = $stmt->fetch();
    if (!$row || !is_readable(__DIR__ . '/../' . $row['file_path'])) {
        continue;
    }

    $contextYaml = file_get_contents(__DIR__ . '/../' . $row['file_path']);
    $output = run_ai_test($input, $contextYaml);

    $pass = strtolower($output) === strtolower($expected) ? 'Pass' : 'Failed';

    $update = $pdo->prepare("UPDATE test_cases SET result = ?, last_run = NOW() WHERE id = ?");
    $update->execute([$pass, $test['id']]);

    $results[] = [
        'id' => $test['id'],
        'input' => $input,
        'expected' => $expected,
        'actual' => $output,
        'result' => $pass
    ];
}

// Output summary
header('Content-Type: text/plain');
echo "Test Run Completed:\n";
foreach ($results as $res) {
    echo "Test #{$res['id']} - {$res['result']} (Expected: {$res['expected']}, Got: {$res['actual']})\n";
}
