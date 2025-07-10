<?php
function log_yaml_edit(PDO $pdo, array $data): void {
    $stmt = $pdo->prepare("INSERT INTO yaml_edit_log (
        file_type, filename, user_email, edit_time, 
        version_before, version_after, diff_summary, diff_json, 
        action_taken, ip_address, user_agent, referer_url, test_run_ids, notes
    ) VALUES (
        :file_type, :filename, :user_email, NOW(),
        :version_before, :version_after, :diff_summary, :diff_json,
        :action_taken, :ip_address, :user_agent, :referer_url, :test_run_ids, :notes
    )");

    $stmt->execute([
        ':file_type'       => $data['file_type'],
        ':filename'        => $data['filename'],
        ':user_email'      => $data['user_email'],
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