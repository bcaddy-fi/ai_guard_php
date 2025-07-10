<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';

$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

$valid = ['persona', 'agent', 'guardrail', 'model'];
if (!in_array($type, $valid)) {
    die("Invalid type.");
}

$fullPath = get_yaml_directory($type) . basename($file);
if (file_exists($fullPath)) {
    unlink($fullPath);
    header("Location: manage_{$type}s.php");
} else {
    die("File not found.");
}
