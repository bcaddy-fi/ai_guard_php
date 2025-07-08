<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');
require_once 'includes/waf.php'; // WAF protection
$success = '';
$error = '';

$currentUser = $_SESSION['email'] ?? 'system';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
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

$stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settingMap = [];
foreach ($settings as $s) {
    $settingMap[$s['setting_key']] = $s;
}

$auditLogStmt = $pdo->prepare("SELECT * FROM audit_log WHERE target_table = 'app_settings' ORDER BY timestamp DESC LIMIT 50");
$auditLogStmt->execute();
$auditEntries = $auditLogStmt->fetchAll(PDO::FETCH_ASSOC);
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Console - App Settings</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-top: 70px; }
  </style>
</head>
<body class="bg-light">

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php"><img src="https://guard-manager.isms-cloud.com/aiguardmanager.png" width="110" height="35"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="admin_console.php"><i class="fa fa-gears"></i> Admin Console</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<h4><i class="fa fa-vial"></i> Test OpenAI Key</h4>
<a href="admin_console.php?test_openai=1" class="btn btn-outline-primary mb-3"><i class="fa fa-play-circle"></i> Run OpenAI Key Test</a>
<?= $openai_test_result ?>
<div class="container py-4"><br><br><br>
  <h2 class="mb-4"><i class="fa fa-gears"></i> Application Settings</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm bg-white">
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
        <?php if ($row['description']): ?>
          <small class="text-muted"><?= htmlspecialchars($row['description']) ?></small>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
  </form>

  <hr class="my-5">

  <h4><i class="fa fa-history"></i> Recent Setting Changes (Audit Log)</h4>
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

<?php include 'install_checker.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
