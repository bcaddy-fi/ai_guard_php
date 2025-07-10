<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';

$type = $_GET['type'] ?? '';
$filename = $_GET['file'] ?? '';

if (!$type || !$filename) {
    http_response_code(400);
    die("Missing type or file parameter.");
}

// Normalize type using your map
try {
    $normalizedType = array_search(get_yaml_directory($type), [
        'persona'     => get_yaml_directory('persona'),
        'agent'       => get_yaml_directory('agent'),
        'model'       => get_yaml_directory('model'),
        'guardrail'   => get_yaml_directory('guardrail')
    ]);
} catch (Exception $e) {
    http_response_code(400);
    die("Invalid type provided.");
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

    <?php
    $backPages = [
        'persona' => 'manage_personas.php',
        'agent' => 'manage_rules.php',
        'model' => 'manage_models.php',
        'guardrail' => 'manage_guardrails.php'
    ];
    $backPage = $backPages[$normalizedType] ?? 'index.php';
    ?>
    <a href="<?= htmlspecialchars($backPage) ?>" class="btn btn-secondary mb-3">Back</a>

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
                        <td><?= htmlspecialchars($log['email']) ?></td>
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
