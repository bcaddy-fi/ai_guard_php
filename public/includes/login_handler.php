<?php
session_start();

// Fix path to db.php (go up two levels from public/includes)
require __DIR__ . '/../../app/controllers/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header("Location: /login.php?error=Missing+credentials");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND use_sso = 0 LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: /login.php?error=Invalid+username+or+password");
    exit;
}

// ? Set session correctly
$_SESSION['user'] = [
    'id'       => $user['id'] ?? null,
    'username' => $user['username'],
    'email'    => $user['email'],
    'role'     => $user['role'] ?? 'read'
];

// Optional legacy values if still needed
$_SESSION['user_id'] = $user['username'];
$_SESSION['role'] = $user['role'] ?? 'read';

// Audit log (now using correct email)
$stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE username = ?");
$stmt->execute([$user['username']]);

$log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
$log->execute([
    $user['email'],
    'Login',
    'users',
    $user['id'] ?? null,
    'Standard login from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
]);

header("Location: /dashboard.php");
exit;
