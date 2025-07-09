<?php
session_start();

$config = require __DIR__ . '/../../config/waf_config.php';
if (empty($config['enabled'])) return;

require_once __DIR__ . '/../../app/controllers/db.php';
$pdo = $pdo ?? null;

// --- Real IP Resolution ---
$ip = '0.0.0.0';
if (!empty($config['use_x_real_ip']) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
    $ip = $_SERVER['HTTP_X_REAL_IP'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$path = $_SERVER['REQUEST_URI'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$country = getCountryByIP($ip);

// --- Helpers ---
function log_waf_denial($ip, $country, $reason, $path, $ua) {
    global $pdo;
    if (!$pdo instanceof PDO) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO waf_denials (ip_address, country_code, reason, path, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ip, $country, $reason, $path, $ua]);
    } catch (Exception $e) {
        error_log("WAF LOGGING ERROR: " . $e->getMessage());
    }
}

function respond_waf_block($message) {
    global $config;
    http_response_code(403);
    $isApi = !empty($config['json_response_enabled']) &&
        ((isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
         str_contains($_SERVER['REQUEST_URI'], '/api/') ||
         str_ends_with($_SERVER['SCRIPT_NAME'], '.json'));

    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'waf_blocked', 'message' => $message]);
    } else {
        echo "Access Denied: " . htmlspecialchars($message);
    }
    exit;
}

function getCountryByIP($ip): string {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE)) return 'XX';
    $result = @file_get_contents("https://ipapi.co/{$ip}/country/");
    return ($result && strlen($result) === 2) ? strtoupper(trim($result)) : 'XX';
}

function get_denial_count($ip): int {
    global $pdo;
    if (!$pdo instanceof PDO) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM waf_denials WHERE ip_address = ? AND timestamp > NOW() - INTERVAL 10 MINUTE");
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn();
}

// --- CAPTCHA redirect if rate limited ---
$denialCount = get_denial_count($ip);
if (!empty($config['rate_limit_enabled']) && $denialCount >= 3) {
    if (!empty($config['captcha_enabled']) && !isset($_SESSION['captcha_passed'])) {
        log_waf_denial($ip, $country, "Rate limit exceeded — redirecting to CAPTCHA", $path, $ua);
        header("Location: /captcha_challenge.php?return=" . urlencode($path));
        exit;
    } elseif (empty($config['captcha_enabled'])) {
        log_waf_denial($ip, $country, "Rate limit exceeded (no CAPTCHA enabled)", $path, $ua);
        respond_waf_block("Too many requests. Try again later.");
    }
}

// --- IP restrictions ---
$ipList = $config['block_ips'] ?? [];
if ($config['ip_mode'] === 'allow_all_except' && in_array($ip, $ipList)) {
    log_waf_denial($ip, $country, "IP blocked (blacklist)", $path, $ua);
    respond_waf_block("Your IP is blocked.");
}
if ($config['ip_mode'] === 'block_all_except' && !in_array($ip, $ipList)) {
    log_waf_denial($ip, $country, "IP not whitelisted", $path, $ua);
    respond_waf_block("Your IP is not whitelisted.");
}

// --- Country restrictions ---
$countryList = $config['allow_countries'] ?? [];
if ($config['country_mode'] === 'allow_all_except' && in_array($country, $countryList)) {
    log_waf_denial($ip, $country, "Country blocked (blacklist)", $path, $ua);
    respond_waf_block("Your country ($country) is blocked.");
}
if ($config['country_mode'] === 'block_all_except' && !in_array($country, $countryList)) {
    log_waf_denial($ip, $country, "Country not whitelisted", $path, $ua);
    respond_waf_block("Your country ($country) is not allowed.");
}

// --- SQL Injection detection ---
if (!empty($config['block_sql_injection'])) {
    foreach ($_REQUEST as $key => $val) {
        if (is_string($val) && preg_match('/(union\s+select|select\s.*from|insert\s+into|drop\s+table|--|\bor\b|\band\b)/i', $val)) {
            log_waf_denial($ip, $country, "SQLi attempt: $key=$val", $path, $ua);
            respond_waf_block("Blocked: SQL injection detected.");
        }
    }
}

// --- XSS detection ---
if (!empty($config['block_xss'])) {
    foreach ($_REQUEST as $key => $val) {
        if (is_string($val) && preg_match('/<script\b[^>]*>/i', $val)) {
            log_waf_denial($ip, $country, "XSS attempt: $key=$val", $path, $ua);
            respond_waf_block("Blocked: Potential XSS attack.");
        }
    }
}

// --- User-Agent blacklist ---
$badAgents = ['sqlmap', 'curl', 'python-requests', 'wget', 'httpclient', 'nikto'];
foreach ($badAgents as $bad) {
    if (stripos($ua, $bad) !== false) {
        log_waf_denial($ip, $country, "Bad user-agent: $ua", $path, $ua);
        respond_waf_block("Your client is not allowed.");
    }
}

// --- Referer spoofing or missing check ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (empty($referer)) {
        log_waf_denial($ip, $country, "POST with empty referer", $path, $ua);
        respond_waf_block("Missing referer header.");
    }
}
