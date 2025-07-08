<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("SELECT name FROM agent_rules WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $rule = $stmt->fetch();

    if ($rule) {
        $pdo->prepare("DELETE FROM agent_rules WHERE id = ?")->execute([$_POST['id']]);

        // Delete YAML file
        $filename = __DIR__ . '/data/agent_rules/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rule['name']) . '.yaml';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}

header("Location: manage_rules.php");
exit;
