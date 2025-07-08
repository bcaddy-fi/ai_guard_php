<?php
function run_llm_test(PDO $pdo, array $testCase): bool {
    $apiKey = getenv('OPENAI_API_KEY');
    $model = 'gpt-4';

    $prompt = trim($testCase['input_text']);
    $expected = trim($testCase['expected_behavior']);
    $testCaseId = (int)$testCase['id'];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    $headers = [
        'Content-Type: application/json',
        "Authorization: Bearer $apiKey"
    ];

    $body = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.2
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        $output = "Curl Error: " . $error;
        $success = false;
    } else {
        $data = json_decode($response, true);
        $output = $data['choices'][0]['message']['content'] ?? '';
        $success = stripos($output, $expected) !== false;
    }

    // Save result to test_cases
    $stmt = $pdo->prepare("UPDATE test_cases SET result = ?, last_run = NOW() WHERE id = ?");
    $stmt->execute([$success ? 'Pass' : 'Failed', $testCaseId]);

    // Log to llm_logs
    $logStmt = $pdo->prepare("
        INSERT INTO llm_logs (test_case_id, prompt, expected, response, model_used, success)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $testCaseId,
        $prompt,
        $expected,
        $output,
        $model,
        $success ? 1 : 0
    ]);

    return $success;
}
