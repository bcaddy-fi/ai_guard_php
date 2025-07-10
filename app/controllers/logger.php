<?php
function log_yaml_edit(PDO $pdo, array $data): void {
    $email = $data['email'] ?? ($_SESSION['email'] ?? 'unknown');
    $username = $_SESSION['username'] ?? 'unknown';

    // Ensure user exists
    $check = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
    $check->execute([$email]);
    if (!$check->fetch()) {
        $add = $pdo->prepare("INSERT INTO users (email, username, role) VALUES (?, ?, 'engineer')");
        $add->execute([$email, $username]);
    }

    // Insert log
    $stmt = $pdo->prepare("INSERT INTO yaml_edit_log (
        file_type, filename, email, username, edit_time, 
        version_before, version_after, diff_summary, diff_json, 
        action_taken, ip_address, user_agent, referer_url, test_run_ids, notes
    ) VALUES (
        :file_type, :filename, :email, :username, NOW(),
        :version_before, :version_after, :diff_summary, :diff_json,
        :action_taken, :ip_address, :user_agent, :referer_url, :test_run_ids, :notes
    )");

    $stmt->execute([
        ':file_type'       => $data['file_type'],
        ':filename'        => $data['filename'],
        ':email'           => $email,
        ':username'        => $username,
        ':version_before'  => $data['version_before'],
        ':version_after'   => $data['version_after'],
        ':diff_summary'    => $data['diff_summary'],
        ':diff_json'       => json_encode($data['diff_json']),
        ':action_taken'    => $data['action_taken'],
        ':ip_address'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ':referer_url'     => $_SERVER['HTTP_REFERER'] ?? '',
        ':test_run_ids'    => $data['test_run_ids'] ?? '',
        ':notes'           => $data['notes'] ?? null
    ]);
}
?>