<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

$success = '';
$error = '';
$test_result = '';

// Load current settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('splunk_hec_url', 'splunk_hec_token', 'splunk_source')");
$stmt->execute();
$settings = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'setting_value', 'setting_key');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $url = trim($_POST['splunk_hec_url'] ?? '');
    $token = trim($_POST['splunk_hec_token'] ?? '');
    $source = trim($_POST['splunk_source'] ?? '');

    try {
        $updateStmt = $pdo->prepare("UPDATE app_settings SET setting_value=? WHERE setting_key=?");
        $updateStmt->execute([$url, 'splunk_hec_url']);
        $updateStmt->execute([$token, 'splunk_hec_token']);
        $updateStmt->execute([$source, 'splunk_source']);
        $success = "Splunk settings saved successfully.";
    } catch (Exception $e) {
        $error = "Failed to save settings: " . $e->getMessage();
    }

    $settings['splunk_hec_url'] = $url;
    $settings['splunk_hec_token'] = $token;
    $settings['splunk_source'] = $source;
}

// Test log
if (isset($_POST['test_log'])) {
    $url = trim($settings['splunk_hec_url'] ?? '');
    $token = trim($settings['splunk_hec_token'] ?? '');
    $source = trim($settings['splunk_source'] ?? 'aiguard');

    if ($url && $token) {
        $payload = json_encode([
            'event' => 'Test log from AI Guard Manager SIEM Settings',
            'sourcetype' => '_json',
            'source' => $source,
            'time' => time()
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Splunk ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200) {
            $test_result = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> Test log sent successfully to Splunk.</div>';
        } else {
            $test_result = '<div class="alert alert-danger"><strong>Error:</strong> HTTP ' . $http_code . ' - ' . htmlspecialchars($response ?: $curl_error) . '</div>';
        }
    } else {
        $test_result = '<div class="alert alert-warning">Splunk HEC URL and token must be set before testing.</div>';
    }
}

ob_start();
?>

<div class="container mt-5">
  <h2><i class="fa fa-cog"></i> SIEM / Splunk Settings</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
  <?= $test_result ?>

  <form method="POST" class="card p-4 shadow-sm mb-4">
    <div class="mb-3">
      <label class="form-label">Splunk HEC URL</label>
      <input type="url" name="splunk_hec_url" class="form-control"
             value="<?= htmlspecialchars($settings['splunk_hec_url'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">HEC Token</label>
      <input type="text" name="splunk_hec_token" class="form-control"
             value="<?= htmlspecialchars($settings['splunk_hec_token'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Log Source / Index</label>
      <input type="text" name="splunk_source" class="form-control"
             value="<?= htmlspecialchars($settings['splunk_source'] ?? 'aiguard') ?>" required>
    </div>

    <button type="submit" name="save" class="btn btn-primary me-2">
      <i class="fa fa-save"></i> Save Settings
    </button>
    <button type="submit" name="test_log" class="btn btn-secondary">
      <i class="fa fa-paper-plane"></i> Send Test Log
    </button>
  </form>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
