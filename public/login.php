<?php
#require_once 'includes/waf.php'; // WAF protection
session_start();
require __DIR__ . '/../app/controllers/db.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';

// Fetch enabled SSO providers
$stmt = $pdo->prepare("SELECT * FROM sso_settings WHERE enabled = 1 ORDER BY provider_name ASC");
$stmt->execute();
$ssoProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - AI Guard Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow p-4">
        <img src="https://guard-manager.isms-cloud.com/aiguardmanager.png" width="110" height="35">
        <h3 class="mb-4 text-center">Login to AI Guard Manager</h3>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="/includes/login_handler.php" method="POST">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <?php if (!empty($ssoProviders)): ?>
        <div class="mt-4">
          <hr>
          <h6 class="text-center mb-3">Or login with SSO</h6>
          <?php foreach ($ssoProviders as $provider): ?>
            <a href="/sso_login.php?provider=<?= urlencode(strtolower($provider['provider_name'])) ?>"
               class="btn btn-outline-dark w-100 mb-2 d-flex align-items-center justify-content-start">
              <?php if (!empty($provider['icon_url'])): ?>
                <img src="<?= htmlspecialchars($provider['icon_url']) ?>" alt="<?= htmlspecialchars($provider['provider_name']) ?> icon"
                     style="width: 20px; height: 20px; margin-right: 10px;">
              <?php endif; ?>
              <?= htmlspecialchars($provider['provider_name']) ?> Login
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

</body>
</html>
