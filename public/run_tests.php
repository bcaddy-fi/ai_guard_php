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

function resolve_yaml_file_path($test) {
    global $pdo;

    // First try yaml_dir + yaml_file
    if (!empty($test['yaml_dir']) && !empty($test['yaml_file'])) {
        $path = realpath(__DIR__ . '/../data/' . $test['yaml_dir'] . '/' . $test['yaml_file']);
        if ($path && is_readable($path)) return $path;
    }

    // Otherwise, fall back to legacy table lookups
    $type = $test['type'];
    $refId = (int) $test['reference_id'];
    $table = match($type) {
        'persona' => 'personas',
        'guardrail' => 'guardrails',
        'agent' => 'agents',
        'model' => 'models',
        default => null
    };

    if ($table) {
        $stmt = $pdo->prepare("SELECT yaml_file FROM $table WHERE id = ?");
        $stmt->execute([$refId]);
        $yamlFile = $stmt->fetchColumn();
        if ($yamlFile) {
            $defaultDir = match($type) {
                'persona' => 'personas',
                'guardrail' => 'guardrails',
                'agent' => 'agent_rules',
                'model' => 'models'
            };
            $path = realpath(__DIR__ . '/../data/' . $test['yaml_dir'] . '/' . $test['yaml_file']);
            if ($path && is_readable($path)) return $path;
        }
    }

    return null;
}

// Get all test cases
$stmt = $pdo->query("SELECT * FROM test_cases");
$testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];

foreach ($testCases as $test) {
    $input = $test['input'] ?? '';
    $expected = strtolower(trim($test['expected_output'] ?? ''));

    $yamlPath = resolve_yaml_file_path($test);
    if (!$yamlPath || !is_readable($yamlPath)) {
        $results[] = [
            'id' => $test['id'],
            'result' => 'Skipped',
            'reason' => 'YAML not found'
        ];
        continue;
    }

    $contextYaml = file_get_contents($yamlPath);
    $output = run_ai_test($input, $contextYaml);
    $result = strtolower($output) === $expected ? 'pass' : 'fail';

    $update = $pdo->prepare("UPDATE test_cases SET last_result = ?, last_run = NOW() WHERE id = ?");
    $update->execute([$result, $test['id']]);

    $results[] = [
        'id' => $test['id'],
        'input' => $input,
        'expected' => $expected,
        'actual' => $output,
        'result' => ucfirst($result)
    ];
}

// Output summary
header('Content-Type: text/plain');
echo "AI Test Run Completed:\n";
foreach ($results as $res) {
    if ($res['result'] === 'Skipped') {
        echo "Test #{$res['id']} - Skipped ({$res['reason']})\n";
    } else {
        echo "Test #{$res['id']} - {$res['result']} (Expected: {$res['expected']}, Got: {$res['actual']})\n";
    }
}
