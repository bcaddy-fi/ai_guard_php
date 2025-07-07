<?php
session_start();
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

// Fetch current SSO config
$sso = $pdo->query("SELECT * FROM sso_settings WHERE enabled = 1 LIMIT 1")->fetch();
if (!$sso) {
    die("SSO not configured.");
}

// Initialize client
$oidc = new OpenIDConnectClient(
    rtrim($sso['issuer_url'], '/'),
    $sso['client_id'],
    $sso['client_secret']
);

// Use discovery
$oidc->setRedirectURL($sso['redirect_uri']);
$oidc->addScope(['openid', 'email', 'profile']);

try {
    // This will:
    // 1. Check if ?code= is present
    // 2. If not, redirect to login
    // 3. If yes, send POST with grant_type=authorization_code
    $oidc->authenticate();

    // Get user info
    $email = $oidc->requestUserInfo('email');
    $username = $oidc->requestUserInfo('preferred_username') ?? $email;

    if (!$email) {
        throw new Exception("No email address returned from SSO provider.");
    }

    // Match by SSO ID (email or sub)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE sso_id = ? AND use_sso = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Auto-create user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, sso_id, use_sso, role) VALUES (?, ?, ?, 1, 'read')");
        $stmt->execute([$username, $email, $email]);
        $user = ['username' => $username, 'role' => 'read'];
    }

    $_SESSION['user_id'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    header("Location: dashboard.php");
    exit;

} catch (Exception $e) {
    error_log("SSO LOGIN ERROR: " . $e->getMessage());
    echo "<h3>SSO Login Failed</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
