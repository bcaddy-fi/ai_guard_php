<?php
require __DIR__ . '/../app/controllers/db.php'; // Assumes $pdo is initialized here
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>AI Guard Manager - Install Checker</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; color: #333; }
        .check { margin: 10px 0; }
        .ok { color: green; }
        .fail { color: red; }
        .warn { color: #ff9800; }
        .section { margin: 20px 0; }
        .button { padding: 10px 20px; font-size: 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .disabled { background: #ccc; cursor: not-allowed; }
        .enabled { background: #4CAF50; color: white; }
        .hash-box { background: #eee; padding: 10px; border: 1px solid #ccc; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
<h1> AI Guard Manager - Requirements Check</h1>
<div class='section'>
";

function check($label, $status, $advice = '') {
    $icon = $status ? "<i class='fas fa-check-circle ok'></i>" : "<i class='fas fa-times-circle fail'></i>";
    echo "<div class='check'>$icon <strong>$label</strong>";
    if (!$status && $advice) {
        echo "<div class='warn' style='margin-left: 25px;'>Advice: $advice</div>";
    }
    echo "</div>";
}

// Checks
check("PHP Version (" . PHP_VERSION . ")", version_compare(PHP_VERSION, '8.0.0', '>='), "Upgrade to PHP 8.0 or newer.");

$requiredExtensions = [
    'curl'     => 'sudo apt install php-curl',
    'json'     => 'Usually bundled with PHP. Reinstall PHP if missing.',
    'yaml'     => 'sudo apt install php-yaml',
    'mbstring' => 'sudo apt install php-mbstring'
];
foreach ($requiredExtensions as $ext => $fix) {
    check("PHP Extension: $ext", extension_loaded($ext), "Install using: $fix");
}

// Composer autoload
$autoloadExists = file_exists(__DIR__ . '/../vendor/autoload.php');
check("Composer Autoload File", $autoloadExists, "Run: composer install from project root.");

// Writable folders
$writableDirs = [
    __DIR__ . '/../data/persona'    => 'chmod -R 775 data/persona',
    __DIR__ . '/../data/guardrails' => 'chmod -R 775 data/guardrails',
    __DIR__ . '/../vendor'          => 'Run: composer install and ensure vendor/ is writable'
];
foreach ($writableDirs as $dir => $fix) {
    check("Writable Directory: " . basename($dir), is_writable($dir), $fix);
}

// File write test
$testPath = __DIR__ . '/../data/persona/test.tmp';
$canWriteFile = @file_put_contents($testPath, 'test') !== false;
if ($canWriteFile) unlink($testPath);
check("File Write Test (/data/persona)", $canWriteFile, "Check file system permissions.");

// OpenAI test
$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
]);
$apiResult = curl_exec($ch);
$apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$envKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? ($_SERVER['OPENAI_API_KEY'] ?? null));
$apiKeyFound = !empty($envKey);
check("OPENAI_API_KEY is set", $apiKeyFound, "Set it via php.ini, .env, or NGINX fastcgi_param.");
// Check HTTPS (SSL)
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
check("HTTPS Detected", $isHttps, "Configure HTTPS or reverse proxy with TLS termination.");

// Check if behind proxy
$isProxy = false;
$proxyIndicators = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_PROTO', 'HTTP_FORWARDED'];
foreach ($proxyIndicators as $header) {
    if (!empty($_SERVER[$header])) {
        $isProxy = true;
        break;
    }
}

// Check if direct or behind proxy based on server IP
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
$behindKnownLocal = in_array($remoteAddr, ['127.0.0.1', '::1']) || preg_match('/^192\.168\./', $remoteAddr);

$proxyStatus = $isProxy || $behindKnownLocal;
check("Behind Proxy", $proxyStatus, "Consider using a reverse proxy (e.g., NGINX) for TLS, caching, and protection.");

echo "</div><div class='section'>";
echo "<h2><i class='fas fa-key'></i> Generate Password Hash</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_hash']) && !empty($_POST['password'])) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    echo "<div class='hash-box'>$hash</div>";
}
echo "<form method='post'>
    <input type='text' name='password' placeholder='Enter password to hash' required style='padding:5px; margin-right:10px;'>
    <button class='button enabled' type='submit' name='generate_hash'><i class='fas fa-hammer'></i> Generate Hash</button>
</form>";

echo "</div></body></html>";
?>
