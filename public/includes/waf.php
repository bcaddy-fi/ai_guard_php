<?php
$config = require __DIR__ . '/../../config/waf_config.php';
if (!$config['enabled']) return;

require_once __DIR__ . '/../../app/controllers/db.php';
$pdo = $pdo ?? null;

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$path = $_SERVER['REQUEST_URI'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$country = getCountryByIP($ip);

// --- Denial Logger ---
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

// --- Response Formatter ---
function respond_waf_block($message) {
    global $config;
    http_response_code(403);

    $isApi = !empty($config['json_response_enabled']) && (
        (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
        str_contains($_SERVER['REQUEST_URI'], '/api/') ||
        str_ends_with($_SERVER['SCRIPT_NAME'], '.json')
    );

    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'waf_blocked', 'message' => $message]);
    } else {
        echo "Access Denied: " . htmlspecialchars($message);
    }
    exit;
}

// --- Country Lookup ---
function getCountryByIP($ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE) === false) {
        return 'XX';
    }
    $country = @file_get_contents("https://ipapi.co/{$ip}/country/");
    return ($country && strlen($country) === 2) ? strtoupper(trim($country)) : 'XX';
}

// --- Rate Limiting ---
function check_rate_limit($ip): bool {
    global $pdo;
    if (!$pdo instanceof PDO) return false;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM waf_denials WHERE ip_address = ? AND timestamp > NOW() - INTERVAL 10 MINUTE");
    $stmt->execute([$ip]);
    return ($stmt->fetchColumn() > 10);
}

// --- IP Filtering ---
$ipList = $config['block_ips'] ?? [];
if ($config['ip_mode'] === 'allow_all_except' && in_array($ip, $ipList)) {
    log_waf_denial($ip, $country, "IP blocked (blacklist)", $path, $ua);
    respond_waf_block("Your IP is blocked.");
}
if ($config['ip_mode'] === 'block_all_except' && !in_array($ip, $ipList)) {
    log_waf_denial($ip, $country, "IP not whitelisted", $path, $ua);
    respond_waf_block("Your IP is not whitelisted.");
}

// --- Country Filtering ---
$countryList = $config['allow_countries'] ?? [];
if ($config['country_mode'] === 'allow_all_except' && in_array($country, $countryList)) {
    log_waf_denial($ip, $country, "Country blocked (blacklist)", $path, $ua);
    respond_waf_block("Your country ($country) is blocked.");
}
if ($config['country_mode'] === 'block_all_except' && !in_array($country, $countryList)) {
    log_waf_denial($ip, $country, "Country not whitelisted", $path, $ua);
    respond_waf_block("Your country ($country) is not allowed.");
}

// --- Rate Limit Check ---
if (!empty($config['rate_limit_enabled']) && check_rate_limit($ip)) {
    log_waf_denial($ip, $country, "Rate limit exceeded", $path, $ua);
    respond_waf_block("Too many requests. Please try again later.");
}

// --- SQLi Detection ---
if (!empty($config['block_sql_injection'])) {
    foreach ($_REQUEST as $key => $val) {
        if (is_string($val) && preg_match('/(union\s+select|select\s.*from|insert\s+into|drop\s+table|--|\bor\b|\band\b)/i', $val)) {
            log_waf_denial($ip, $country, "SQLi attempt: $key=$val", $path, $ua);
            respond_waf_block("Blocked: SQL injection pattern detected.");
        }
    }
}

// --- XSS Detection ---
if (!empty($config['block_xss'])) {
    foreach ($_REQUEST as $key => $val) {
        if (is_string($val) && preg_match('/<script\b[^>]*>/i', $val)) {
            log_waf_denial($ip, $country, "XSS attempt: $key=$val", $path, $ua);
            respond_waf_block("Blocked: Potential XSS detected.");
        }
    }
}

// --- User-Agent Filtering ---
$badAgents = ['sqlmap', 'curl', 'python-requests', 'wget', 'httpclient', 'nikto'];
foreach ($badAgents as $bad) {
    if (stripos($ua, $bad) !== false) {
        log_waf_denial($ip, $country, "Blocked user-agent: $ua", $path, $ua);
        respond_waf_block("Your user-agent is not allowed.");
    }
}

// --- Referer Spoofing or Missing on POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (empty($referer)) {
        log_waf_denial($ip, $country, "POST with empty Referer", $path, $ua);
        respond_waf_block("Invalid request: Missing referer.");
    }
}
