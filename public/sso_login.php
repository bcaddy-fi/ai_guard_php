<?php
require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/controllers/auth.php';  // Includes session_start()
require __DIR__ . '/../app/controllers/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Jumbojett\OpenIDConnectClient;
$pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) 
    VALUES ('earlytest@example.com', 'Early Insert', 'test', NULL, 'Script reached top')")
    ->execute();
// Load SSO settings
$stmt = $pdo->prepare("SELECT * FROM sso_settings WHERE enabled = 1 LIMIT 1");
$stmt->execute();
$sso = $stmt->fetch();

if (!$sso) {
    header("Location: /public/login.php?error=SSO+is+not+enabled");
    exit;
}

try {
    $oidc = new OpenIDConnectClient(
        trim($sso['issuer_url']),
        trim($sso['client_id']),
        trim($sso['client_secret'])
    );

    $oidc->setRedirectURL(trim($sso['redirect_uri']));
    $oidc->addScope(['openid', 'email', 'profile']);
    $oidc->addAuthParam(['prompt' => 'login']);

    // Start or complete SSO flow
    $oidc->authenticate();
    $_SESSION['id_token'] = $oidc->getIdToken();

    // Get user info
    $email = $oidc->requestUserInfo('email');
    $name  = $oidc->requestUserInfo('name') ?? 'SSO User';

    if (!$email) {
        throw new Exception("Email not returned by provider.");
    }

    // Find or create user
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $insert = $pdo->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
        $insert->execute([$email, $name, 'read']);
        $userId = $pdo->lastInsertId();
        $role = 'read';
    } else {
        $userId = $user['id'];
        $role = $user['role'];
    }

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['email']   = $email;
    $_SESSION['name']    = $name;
    $_SESSION['role']    = $role;

    // Log audit entry
    try {
    $log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
    $log->execute([
        $email,
        'SSO Login',
        'users',
        $userId,
        'SSO login via ' . ($sso['issuer_url'] ?? 'unknown') . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    ]);
    error_log("? Audit log written for $email");
} catch (PDOException $logEx) {
    error_log("? Audit log insert failed: " . $logEx->getMessage());
}
    session_write_close();
    header("Location: dashboard.php");
    var_dump($pdo);
    exit;

} catch (Exception $e) {
    error_log("SSO Login Error: " . $e->getMessage());
    header("Location: /public/login.php?error=SSO+Login+Failed");
    exit;
}
