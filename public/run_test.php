<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');

require_once 'includes/waf.php';
require __DIR__ . '/../app/controllers/db.php';

$testResult = '';
$error = '';
$testId = $_POST['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $testId) {
    // Fetch test case
    $stmt = $pdo->prepare("SELECT * FROM test_cases WHERE id = ?");
    $stmt->execute([$testId]);
    $testCase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$testCase) {
        $error = "Test case not found.";
    } else {
        // Get YAML file content
        $type = $testCase['type'];
        $refId = (int) $testCase['reference_id'];
        $filename = '';
        $baseDir = __DIR__ . '/../data/';

        if (!empty($testCase['yaml_file']) && !empty($testCase['yaml_dir'])) {
            $filename = realpath($baseDir . $testCase['yaml_dir'] . '/' . $testCase['yaml_file']);
        } else {
            $lookupTable = match ($type) {
                'persona' => 'personas',
                'guardrail' => 'guardrails',
                'model' => 'models',
                'agent' => 'agents',
                default => '',
            };

            if ($lookupTable) {
                $stmt = $pdo->prepare("SELECT yaml_file FROM $lookupTable WHERE id = ?");
                $stmt->execute([$refId]);
                $yamlFile = $stmt->fetchColumn();
                if ($yamlFile) {
                    $defaultDir = match ($type) {
                        'persona' => 'personas',
                        'guardrail' => 'guardrails',
                        'model' => 'models',
                        'agent' => 'agent_rules',
                    };
                    $filename = realpath($baseDir . $defaultDir . '/' . $yamlFile);
                }
            }
        }

        if (!$filename || !is_file($filename)) {
            $error = "YAML file not found.";
        } else {
            $yaml = file_get_contents($filename);
            $prompt = $testCase['input_text'] ?? $testCase['input'] ?? '';

            $postData = [
                'type' => $type,
                'filename' => basename($filename),
                'prompt' => $prompt,
                'yaml' => $yaml
            ];

            $ch = curl_init('https://guard-manager.isms-cloud.com/test_api.php');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$response) {
                $error = "API call failed: $err";
            } else {
                $testResult = $response;
            }
        }
    }
}

include 'includes/layout.php';
?>

<div class="container mt-5">
    <h2>Run LLM Test</h2>

    <form method="POST">
        <div class="mb-3">
            <label for="id" class="form-label">Test Case ID</label>
            <input type="number" class="form-control" name="id" required value="<?= htmlspecialchars($testId) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Run Test</button>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($testResult): ?>
        <div class="mt-4">
            <label for="result" class="form-label">Test Output</label>
            <textarea id="result" class="form-control" rows="20"><?= htmlspecialchars($testResult) ?></textarea>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
