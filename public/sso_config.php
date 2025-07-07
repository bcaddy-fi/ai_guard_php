<?php
require 'includes/auth.php';
require_login();
require_role('admin');
require 'includes/db.php';

$success = '';
$error = '';

// Save or update provider
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $provider_name = trim($_POST['provider_name']);
    $issuer_url = trim($_POST['issuer_url']);
    $client_id = trim($_POST['client_id']);
    $client_secret = trim($_POST['client_secret']);
    $redirect_uri = trim($_POST['redirect_uri']);
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    try {
        if ($enabled) {
            $pdo->query("UPDATE sso_settings SET enabled = 0"); // Disable all
        }

        if ($id) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE sso_settings SET provider_name=?, issuer_url=?, client_id=?, client_secret=?, redirect_uri=?, enabled=? WHERE id=?");
            $stmt->execute([$provider_name, $issuer_url, $client_id, $client_secret, $redirect_uri, $enabled, $id]);
            $success = "Provider updated.";
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO sso_settings (provider_name, issuer_url, client_id, client_secret, redirect_uri, enabled) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$provider_name, $issuer_url, $client_id, $client_secret, $redirect_uri, $enabled]);
            $success = "Provider added.";
        }
    } catch (Exception $e) {
        $error = "Error saving provider: " . $e->getMessage();
    }
}

// Load all providers
$providers = $pdo->query("SELECT * FROM sso_settings ORDER BY id")->fetchAll();

ob_start();
?>

<h2>SSO Provider Configuration</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php foreach ($providers as $provider): ?>
  <form method="post" class="card mb-3 p-3">
    <input type="hidden" name="id" value="<?= $provider['id'] ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Provider Name</label>
        <input type="text" name="provider_name" class="form-control" value="<?= htmlspecialchars($provider['provider_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Issuer URL</label>
        <input type="url" name="issuer_url" class="form-control" value="<?= htmlspecialchars($provider['issuer_url']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Redirect URI</label>
        <input type="url" name="redirect_uri" class="form-control" value="<?= htmlspecialchars($provider['redirect_uri']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Client ID</label>
        <input type="text" name="client_id" class="form-control" value="<?= htmlspecialchars($provider['client_id']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Client Secret</label>
        <input type="text" name="client_secret" class="form-control" value="<?= htmlspecialchars($provider['client_secret']) ?>" required>
      </div>
      <div class="col-md-2">
        <label class="form-label d-block">Enabled</label>
        <input type="checkbox" name="enabled" <?= $provider['enabled'] ? 'checked' : '' ?>>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </div>
  </form>
<?php endforeach; ?>

<!-- Add New -->
<form method="post" class="card p-3 border border-primary">
  <h5 class="text-primary">Add New SSO Provider</h5>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Provider Name</label>
      <input type="text" name="provider_name" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Issuer URL</label>
      <input type="url" name="issuer_url" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Redirect URI</label>
      <input type="url" name="redirect_uri" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Client ID</label>
      <input type="text" name="client_id" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Client Secret</label>
      <input type="text" name="client_secret" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label class="form-label d-block">Enabled</label>
      <input type="checkbox" name="enabled">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-success">Add</button>
    </div>
  </div>
</form>
<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
