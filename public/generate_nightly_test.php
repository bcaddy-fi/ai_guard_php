<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

$directory = __DIR__ . '/../data/persona/';
$files = glob($directory . '*.yaml');
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = basename($_POST['filename']);
    $prompt = trim($_POST['prompt'] ?? '');
    $policyType = $_POST['policy_type'] ?? 'persona';

    if (!$filename || !$prompt) {
        $error = "Both file and prompt are required.";
    } else {
        $scriptPath = __DIR__ . "/scripts/nightly_test_{$filename}.sh";
        $logPath = "/var/log/ai_guard_tests.log";

        $safePrompt = str_replace('"', '\"', $prompt); // escape double quotes

        $bashScript = <<<BASH
#!/bin/bash
# Auto-generated nightly AI guardrail test script

FILENAME="$filename"
POLICY_TYPE="$policyType"
PROMPT="$safePrompt"
TIMESTAMP=\$(date -u +"%Y-%m-%dT%H:%M:%SZ")

RESPONSE=\$(curl -s -X POST https://guard-manager.isms-cloud.com//test_api.php \\
  -F "filename=\$FILENAME" \\
  -F "prompt=\$PROMPT" \\
  -F "policy_type=\$POLICY_TYPE")

JSON_LOG=\$(jq -n \\
  --arg timestamp "\$TIMESTAMP" \\
  --arg filename "\$FILENAME" \\
  --arg policy_type "\$POLICY_TYPE" \\
  --arg prompt "\$PROMPT" \\
  --arg response "\$RESPONSE" \\
  '{timestamp: \$timestamp, filename: \$filename, policy_type: \$policy_type, prompt: \$prompt, response: \$response}')

{
  echo "-----"
  echo "\$JSON_LOG"
  echo ""
} >> "$logPath"
BASH;

        file_put_contents($scriptPath, $bashScript);
        chmod($scriptPath, 0755);
        $success = "Nightly test script created at: $scriptPath";
    }
}

ob_start();
?>
<h2>Nightly AI Guardrail Test Generator</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php elseif ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
  <div class="mb-3">
    <label class="form-label">Select Persona/Guardrail YAML File</label>
    <select name="filename" class="form-select" required>
      <option value="">-- Select File --</option>
      <?php foreach ($files as $file): ?>
        <option value="<?= htmlspecialchars(basename($file)) ?>"><?= htmlspecialchars(basename($file)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Prompt to Send</label>
    <textarea name="prompt" class="form-control" required rows="3" placeholder="What question should be tested?"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Policy Type</label>
    <select name="policy_type" class="form-select">
      <option value="persona">Persona</option>
      <option value="guardrail">Guardrail</option>
      <option value="model">Model</option>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">Generate Script</button>
</form>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
include 'includes/footer.php';
