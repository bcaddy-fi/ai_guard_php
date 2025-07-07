<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/controllers/db.php';

use Jumbojett\OpenIDConnectClient;

// Destroy local session
$idToken = $_SESSION['id_token'] ?? null;
$_SESSION = [];
session_destroy();

// Load SSO config
$stmt = $pdo->prepare("SELECT * FROM sso_settings WHERE enabled = 1 LIMIT 1");
$stmt->execute();
$sso = $stmt->fetch();

if ($sso && $idToken) {
    $issuer = rtrim($sso['issuer_url'], '/');
    $redirectUri = urlencode("https://guard-manager.isms-cloud.com/login.php");

    // Send logout to Keycloak with id_token_hint
    $logoutUrl = $issuer . "/protocol/openid-connect/logout?" .
        "id_token_hint=" . urlencode($idToken) . "&" .
        "post_logout_redirect_uri=" . $redirectUri;

    header("Location: $logoutUrl");
    exit;
} else {
    // Fallback logout
    header("Location: /login.php?logged_out=1");
    exit;
}
