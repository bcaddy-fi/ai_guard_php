<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

$success = '';
$error = '';
$currentUser = $_SESSION['email'] ?? 'system';

$configPath = __DIR__ . '/../config/waf_config.php';
if (!file_exists($configPath)) {
    $error = "WAF configuration file not found.";
    $config = [];
} else {
    $config = require $configPath;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newConfig = [
            'enabled' => isset($_POST['enabled']),
            'ip_mode' => $_POST['ip_mode'] ?? 'block_all_except',
            'block_ips' => array_filter(array_map('trim', explode("\n", $_POST['block_ips']))),
            'country_mode' => $_POST['country_mode'] ?? 'allow_all_except',
            'allow_countries' => array_filter(array_map('trim', explode(",", $_POST['allow_countries']))),
            'block_sql_injection' => isset($_POST['block_sql_injection']),
            'block_xss' => isset($_POST['block_xss']),
            'rate_limit_enabled' => isset($_POST['rate_limit_enabled']),
            'json_response_enabled' => isset($_POST['json_response_enabled']),
            'captcha_enabled' => isset($_POST['captcha_enabled']),
            'use_x_real_ip' => isset($_POST['use_x_real_ip']),
            'hcaptcha_site_key' => trim($_POST['hcaptcha_site_key'] ?? ''),
            'hcaptcha_secret_key' => trim($_POST['hcaptcha_secret_key'] ?? '')
        ];

        file_put_contents($configPath, '<?php return ' . var_export($newConfig, true) . ';');
        $success = "WAF settings updated.";
        $config = $newConfig;

        $stmt = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$currentUser, 'Updated WAF config', 'waf_config', null, 'Settings changed via waf_admin.php']);
    }
}

// Audit + Denial Logs
$stmt = $pdo->prepare("SELECT * FROM audit_log WHERE target_table = 'waf_config' ORDER BY timestamp DESC LIMIT 50");
$stmt->execute();
$auditEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM waf_denials ORDER BY timestamp DESC LIMIT 50");
$stmt->execute();
$wafDenials = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="container py-4">
  <h2 class="mb-4"><i class="fa fa-shield-halved"></i> Web Application Firewall Settings</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$error): ?>
    <form method="post" class="card p-4 shadow-sm bg-white mb-5">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="enabled" id="enabled" <?= !empty($config['enabled']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="enabled">Enable WAF</label>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">IP Restriction Mode:</label>
          <select class="form-select" name="ip_mode">
            <option value="allow_all_except" <?= ($config['ip_mode'] ?? '') === 'allow_all_except' ? 'selected' : '' ?>>Allow All Except</option>
            <option value="block_all_except" <?= ($config['ip_mode'] ?? '') === 'block_all_except' ? 'selected' : '' ?>>Block All Except</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">IP List (one per line):</label>
          <textarea class="form-control" name="block_ips" rows="4"><?= htmlspecialchars(implode("\n", $config['block_ips'] ?? [])) ?></textarea>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Country Restriction Mode:</label>
          <select class="form-select" name="country_mode">
            <option value="allow_all_except" <?= ($config['country_mode'] ?? '') === 'allow_all_except' ? 'selected' : '' ?>>Allow All Except</option>
            <option value="block_all_except" <?= ($config['country_mode'] ?? '') === 'block_all_except' ? 'selected' : '' ?>>Block All Except</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Country List (comma separated):</label>
          <input type="text" class="form-control" name="allow_countries" value="<?= htmlspecialchars(implode(',', $config['allow_countries'] ?? [])) ?>">
        </div>
      </div>

      <?php foreach ([
        'block_sql_injection' => 'Block SQL Injection',
        'block_xss' => 'Block XSS',
        'rate_limit_enabled' => 'Enable Rate Limiting',
        'json_response_enabled' => 'Enable JSON-Compatible API Responses',
        'use_x_real_ip' => 'Use HTTP_X_REAL_IP instead of REMOTE_ADDR'
      ] as $key => $label): ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $key ?>" <?= !empty($config[$key]) ? 'checked' : '' ?>>
          <label class="form-check-label" for="<?= $key ?>"><?= $label ?></label>
        </div>
      <?php endforeach; ?>

      <hr class="my-4">
      <h4><i class="fa fa-robot"></i> CAPTCHA Settings</h4>

      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="captcha_enabled" id="captcha_enabled" <?= !empty($config['captcha_enabled']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="captcha_enabled">Enable CAPTCHA Challenge</label>
      </div>

      <div class="mb-3">
        <label for="hcaptcha_site_key" class="form-label">hCaptcha Site Key</label>
        <input type="text" class="form-control" id="hcaptcha_site_key" name="hcaptcha_site_key" value="<?= htmlspecialchars($config['hcaptcha_site_key'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="hcaptcha_secret_key" class="form-label">hCaptcha Secret Key</label>
        <input type="text" class="form-control" id="hcaptcha_secret_key" name="hcaptcha_secret_key" value="<?= htmlspecialchars($config['hcaptcha_secret_key'] ?? '') ?>">
      </div>

      <?php if (!empty($config['captcha_enabled']) && (empty($config['hcaptcha_site_key']) || empty($config['hcaptcha_secret_key']))): ?>
        <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> CAPTCHA is enabled but missing keys.</div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Configuration</button>
    </form>

    <h4><i class="fa fa-history"></i> WAF Audit Log</h4>
    <table class="table table-striped table-bordered mb-5">
      <thead class="table-light">
        <tr>
          <th>User</th><th>Action</th><th>Details</th><th>Time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($auditEntries as $entry): ?>
          <tr>
            <td><?= htmlspecialchars($entry['username']) ?></td>
            <td><?= htmlspecialchars($entry['action']) ?></td>
            <td><?= htmlspecialchars($entry['details']) ?></td>
            <td><?= htmlspecialchars($entry['timestamp']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h4><i class="fa fa-ban"></i> WAF Denials (Last 50)</h4>
    <table class="table table-striped table-bordered">
      <thead class="table-light">
        <tr>
          <th>IP</th><th>Country</th><th>Reason</th><th>Path</th><th>User Agent</th><th>Time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($wafDenials as $denial): ?>
          <tr>
            <td><?= htmlspecialchars($denial['ip_address']) ?></td>
            <td><?= htmlspecialchars($denial['country_code']) ?></td>
            <td><?= htmlspecialchars($denial['reason']) ?></td>
            <td><?= htmlspecialchars($denial['path']) ?></td>
            <td><?= htmlspecialchars($denial['user_agent']) ?></td>
            <td><?= htmlspecialchars($denial['timestamp']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
