<?php
// app_config.php
$config_path = __DIR__ . '/../../config/application.conf';
if (!file_exists($config_path)) {
    die('Configuration file missing: ' . $config_path);
}

$ini = parse_ini_file($config_path, true);
if ($ini === false) {
    die('Failed to parse application.conf');
}

return $ini;
