<?php
require __DIR__ . '/../app/controllers/db.php';

function run_ai_test($input, $context_yaml) {
    // Replace this with actual API call (e.g., OpenAI or internal AI)
    // Simulate a fake response for now
    return rand(0, 1) === 1 ? 'pass' : 'block';
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
echo "Test Run Completed:\n";
foreach ($results as $res) {
    echo "Test #{$res['id']} - {$res['result']} (Expected: {$res['expected']}, Got: {$res['actual']})\n";
}
?>
