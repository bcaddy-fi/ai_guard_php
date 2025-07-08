<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

$name = $_GET['name'] ?? '';
$filename = __DIR__ . '/../data/agent_rules/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) . '.yaml';

if (!file_exists($filename)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

header('Content-Type: text/yaml');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
readfile($filename);
exit;
