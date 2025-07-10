<?php
// Debugging only (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Core requirements
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');
require_once 'includes/waf.php';

ob_start();

$success = '';
$error = '';
$currentUser = $_SESSION['email'] ?? 'system';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        // Convert checkbox value for 'waf_enabled'
        if ($key === 'waf_enabled') {
            $value = $value === 'on' ? '1' : '0';
        }

        $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);

        $log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $log->execute([
            $currentUser,
            'Updated setting',
            'app_settings',
            null,
            "Set {$key} = {$value}"
        ]);
    }
    $success = "Settings updated successfully.";
}

// Fetch settings
$stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent audit log
$auditLogStmt = $pdo->prepare("SELECT * FROM audit_log WHERE target_table = 'app_settings' ORDER BY timestamp DESC LIMIT 50");
$auditLogStmt->execute();
$auditEntries = $auditLogStmt->fetchAll(PDO::FETCH_ASSOC);

// Optional OpenAI test
$openai_test_result = '';
if (isset($_GET['test_openai'])) {
    $apiKey = getenv('OPENAI_API_KEY');
    $ch = curl_init('https://api.openai.com/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $openai_test_result = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> OpenAI key is valid and authorized.</div>';
    } elseif ($httpCode === 401) {
        $openai_test_result = '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> OpenAI key is invalid or unauthorized.</div>';
    } else {
        $openai_test_result = '<div class="alert alert-warning"><i class="fa fa-info-circle"></i> Unexpected response from OpenAI API (HTTP ' . $httpCode . ').</div>';
    }
}
?>

<div class="container">
  <h2 class="mb-4"><i class="fa fa-gears"></i> Admin Console - Application Settings</h2>

  <a href="admin_console.php?test_openai=1" class="btn btn-outline-primary mb-3">
    <i class="fa fa-play-circle"></i> Run OpenAI Key Test
  </a>

  <button type="button" class="btn btn-outline-secondary mb-3" onclick="openInstallChecker()">
    <i class="fa fa-tools"></i> Check PHP and System
  </button>

  <?= $openai_test_result ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm bg-white mb-5">
    <?php if (empty($settings)): ?>
      <div class="alert alert-warning">No settings found in the database. Please check the <code>app_settings</code> table.</div>
    <?php else: ?>
      <?php foreach ($settings as $row): ?>
        <div class="mb-3">
          <label class="form-label"><strong><?= htmlspecialchars($row['setting_key']) ?></strong></label>

          <?php if ($row['setting_key'] === 'waf_enabled'): ?>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="waf_enabled"
                     name="settings[waf_enabled]" <?= $row['setting_value'] === '1' ? 'checked' : '' ?>>
              <label class="form-check-label" for="waf_enabled">Enable Web Application Firewall</label>
            </div>
          <?php else: ?>
            <input type="text" name="settings[<?= htmlspecialchars($row['setting_key']) ?>]"
                   value="<?= htmlspecialchars($row['setting_value']) ?>" class="form-control">
          <?php endif; ?>

          <?php if (!empty($row['description'])): ?>
            <small class="text-muted"><?= htmlspecialchars($row['description']) ?></small>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
    <?php endif; ?>
  </form>

  <h4><i class="fa fa-history"></i> Recent Setting Changes</h4>
  <table class="table table-striped table-bordered mt-3">
    <thead class="table-light">
      <tr>
        <th>User</th>
        <th>Action</th>
        <th>Details</th>
        <th>Timestamp</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($auditEntries as $log): ?>
      <tr>
        <td><?= htmlspecialchars($log['username']) ?></td>
        <td><?= htmlspecialchars($log['action']) ?></td>
        <td><?= htmlspecialchars($log['details']) ?></td>
        <td><?= htmlspecialchars($log['timestamp']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal for Install Checker -->
<div class="modal fade" id="installModal" tabindex="-1" aria-labelledby="installModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-server"></i> System Requirements Check</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="installFrame" src="" width="100%" height="600" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
function openInstallChecker() {
  const modal = new bootstrap.Modal(document.getElementById('installModal'));
  document.getElementById('installFrame').src = 'install_checker.php';
  modal.show();
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
