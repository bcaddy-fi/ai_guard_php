<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';

$type = 'guardrails';
$dir = get_yaml_directory($type);

$files = is_dir($dir)
    ? array_filter(scandir($dir), fn($f) => str_ends_with($f, '.yaml') || str_ends_with($f, '.yml'))
    : [];

include 'includes/layout.php';
?>

<div class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Guardrails</h2>
    <a href="add_yaml.php?type=guardrails" class="btn btn-success">
      <i class="fa fa-plus"></i> Add Guardrail
    </a>
  </div>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Filename</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($files as $file): ?>
        <tr>
          <td><?= htmlspecialchars($file) ?></td>
          <td>
            <a href="yaml_tester.php?name=<?= urlencode($file) ?>&type=guardrail" class="btn btn-sm btn-info">Test</a>
            <a href="edit_raw_yaml.php?file=<?= urlencode($file) ?>&type=guardrails" class="btn btn-sm btn-primary">Edit</a>
            <a href="delete_yaml.php?type=guardrails&file=<?= urlencode($file) ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?= htmlspecialchars($file) ?>')">Delete</a>
            <a href="download_yaml.php?type=guardrails&name=<?= urlencode($file) ?>" class="btn btn-sm btn-secondary">Download</a>
	    <a href="yaml_history.php?type=guardrails&file=<?= urlencode($file) ?>" class="btn btn-sm btn-warning">History</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  function confirmDelete(filename) {
    const input = prompt(`To confirm deletion of "${filename}", type: delete`);
    if (input === 'delete') {
      return true;
    } else if (input === null) {
      return false;
    } else {
      alert('Deletion canceled. You must type "delete" to proceed.');
      return false;
    }
  }
</script>

<?php include 'includes/footer.php'; ?>
