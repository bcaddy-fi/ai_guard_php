<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';

$type = $_GET['type'] ?? '';
$name = $_GET['name'] ?? '';

if (!$type || !$name) {
    http_response_code(400);
    echo "Missing type or name.";
    exit;
}

$dir = get_yaml_directory($type);
$filename = $dir . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);

// Make sure the filename ends with .yaml
if (!str_ends_with($filename, '.yaml') && !str_ends_with($filename, '.yml')) {
    $filename .= '.yaml';
}

if (!file_exists($filename)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

header('Content-Type: text/yaml');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
readfile($filename);
exit;
