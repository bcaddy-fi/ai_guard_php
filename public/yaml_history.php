<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

$type = $_GET['type'] ?? '';
$filename = $_GET['file'] ?? '';

if (!$type || !$filename) {
    http_response_code(400);
    die("Missing type or file parameter.");
}

$stmt = $pdo->prepare("SELECT * FROM yaml_edit_log WHERE file_type = :type AND filename = :filename ORDER BY edit_time DESC");

$stmt->execute([
    'type' => $type,
    'filename' => $filename
]);

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout.php';
?>

<div class="container mt-5">
    <h2>YAML Edit History: <?= htmlspecialchars($filename) ?> (<?= htmlspecialchars($type) ?>)</h2>
    <a href="manage_<?= htmlspecialchars($type) === 'agent' ? 'rules' : htmlspecialchars($type) ?>.php" class="btn btn-secondary mb-3">Back</a>

    <?php if (empty($logs)): ?>
        <div class="alert alert-info">No edit history found for this file.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>From Version</th>
                    <th>To Version</th>
                    <th>Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['edit_time']) ?></td>
                        <td><?= htmlspecialchars($log['user_email']) ?></td>
                        <td><?= htmlspecialchars($log['action_taken']) ?></td>
                        <td><?= htmlspecialchars($log['version_before']) ?></td>
                        <td><?= htmlspecialchars($log['version_after']) ?></td>
                        <td>
                            <pre style="white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($log['diff_summary']) ?></pre>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
