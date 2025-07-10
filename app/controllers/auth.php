<?php
session_start();
require_once __DIR__ . '/db.php';

/**
 * Ensure session includes full user context
 */
function sync_user_session($username) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id, username, email, role, last_login FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_login'] = $user['last_login'];
    } else {
        // Clear session if user not found
        session_unset();
        session_destroy();
        header('Location: /ai_guard_manager/login.php');
        exit;
    }
}

/**
 * Require user to be logged in.
 */
function require_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: /ai_guard_manager/login.php');
        exit;
    }

    // Keep session synced with database record
    sync_user_session($_SESSION['username']);
}

/**
 * Require a specific role: 'read', 'engineer', or 'admin'
 */
function require_role($role) {
    $roleHierarchy = ['read' => 1, 'engineer' => 2, 'admin' => 3];
    $userRole = $_SESSION['role'] ?? 'read';

    if (($roleHierarchy[$userRole] ?? 0) < ($roleHierarchy[$role] ?? 99)) {
        http_response_code(403);
        echo "<h3>403 Forbidden</h3><p>Insufficient permissions.</p>";
        exit;
    }
}
