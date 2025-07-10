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

  <form method="GET" class="mb-3">
    <label for="type">Filter by Type:</label>
    <select name="type" id="type" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto ms-2">
      <option value="">All</option>
      <?php foreach ($allowed_types as $opt): ?>
        <option value="<?= $opt ?>" <?= $type === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <a href="add_test_case.php" class="btn btn-sm btn-primary mb-3">Add New Test Case</a>
  <table class="table table-bordered table-sm align-middle">
    <thead class="table-light">
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
          <td><?= htmlspecialchars($c['type']) ?></td>
          <td><?= htmlspecialchars($c['reference_name'] ?? '') ?></td>
          <td>
            <?php 
              echo (!empty($c['yaml_dir']) && !empty($c['yaml_file'])) 
                ? htmlspecialchars($c['yaml_dir'] . '/' . $c['yaml_file']) 
                : '—';
            ?>
          </td>
          <td><?= nl2br(htmlspecialchars($c['input'])) ?></td>
          <td><?= nl2br(htmlspecialchars($c['expected_output'])) ?></td>
          <td><?= nl2br(htmlspecialchars($c['notes'])) ?></td>
          <td>
            <?php
              echo match($c['last_result']) {
                'pass' => '&#x2705; Pass',
                'fail' => '&#x274C; Fail',
                'error' => '&#9888;&#xFE0F; Error',
                default => '-',
              };
            ?>
          </td>
          <td><?= htmlspecialchars($c['last_run'] ?? '—') ?></td>
          <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
          <td>
            <a href="edit_test_case.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
            <a href="explain_output.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Explain">
              <i class="fas fa-lightbulb"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>