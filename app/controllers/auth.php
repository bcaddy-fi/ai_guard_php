<?php
session_start();
require_once __DIR__ . '/db.php';

/**
 * Require user to be logged in.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require a specific role: 'read', 'engineer', or 'admin'
 */
function require_role($role) {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        header('Location: /ai_guard_manager/login.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $roles = ['read' => 1, 'engineer' => 2, 'admin' => 3];
    $userRole = $user['role'] ?? 'read';

    if (($roles[$userRole] ?? 0) < ($roles[$role] ?? 99)) {
        http_response_code(403);
        echo "<h3>403 Forbidden</h3><p>Insufficient permissions.</p>";
        exit;
    }
}
