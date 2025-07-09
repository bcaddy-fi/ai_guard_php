<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();

$filename = $_POST['filename'] ?? null;

if (!$filename) {
    http_response_code(400);
    echo "Missing filename.";
    exit;
}

// Sanitize filename
$basename = basename($filename);
$filepath = __DIR__ . '/../data/agent_rules/' . $basename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

if (!is_writable($filepath)) {
    http_response_code(500);
    echo "File is not writable.";
    exit;
}

if (unlink($filepath)) {
    header("Location: manage_rules.php?deleted=" . urlencode($basename));
    exit;
} else {
    http_response_code(500);
    echo "Failed to delete file.";
}
