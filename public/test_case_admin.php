<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');
require __DIR__ . '/../app/controllers/db.php';

$type = $_GET['type'] ?? '';
$filter = '';
$allowed_types = ['persona', 'guardrail', 'agent', 'model'];

if (in_array($type, $allowed_types)) {
    $filter = "WHERE t.type = " . $pdo->quote($type);
}

$stmt = $pdo->query("
    SELECT t.*, 
      CASE 
        WHEN t.type = 'persona' THEN (SELECT name FROM personas WHERE id = t.reference_id)
        WHEN t.type = 'guardrail' THEN (SELECT name FROM guardrails WHERE id = t.reference_id)
        WHEN t.type = 'agent' THEN (SELECT name FROM agents WHERE id = t.reference_id)
        WHEN t.type = 'model' THEN (SELECT name FROM models WHERE id = t.reference_id)
        ELSE NULL
      END as reference_name 
    FROM test_cases t 
    $filter 
    ORDER BY t.created_at DESC
");

$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/layout.php'; ?>
<div class="container mt-4">
  <h2>Test Case Management</h2>
  <a href="add_test_case.php" class="btn btn-sm btn-primary mb-3">Add New Test Case</a>
  <table class="table table-bordered">
<thead>
  <tr>
    <th>Type</th>
    <th>Reference</th>
    <th>YAML Path</th>
    <th>Prompt</th>
    <th>Expected Output</th>
    <th>Notes</th>
    <th>Last Result</th>
    <th>Last Run</th>
    <th>Created</th>
    <th>Actions</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($cases as $c): ?>
    <tr>
      <td><?= htmlspecialchars((string)($c['type'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['reference_name'] ?? '')) ?></td>
      <td>
        <?php 
          if (!empty($c['yaml_dir']) && !empty($c['yaml_file'])) {
              echo htmlspecialchars($c['yaml_dir'] . '/' . $c['yaml_file']);
          } else {
              echo '—';
          }
        ?>
      </td>
      <td><?= htmlspecialchars((string)($c['input'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['expected_output'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($c['notes'] ?? '')) ?></td>
      <td>
        <?php
          if ($c['last_result'] === 'pass') echo '&#x2705; Pass';
          elseif ($c['last_result'] === 'fail') echo '&#x274C; Fail';
          elseif ($c['last_result'] === 'error') echo '&#9888;&#xFE0F; Error';
          else echo '-';
        ?>
      </td>
      <td><?= htmlspecialchars((string)($c['last_run'] ?? '—')) ?></td>
      <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
      <td>
        <form action="run_test.php" method="POST" style="display:inline-block;">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn-sm btn-success">Run Test</button>
        </form>
        <a href="edit_test_case.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
        <a href="explain_output.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Explain">
          <i class="fas fa-lightbulb"></i> Explain
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>
  </table>
</div>
