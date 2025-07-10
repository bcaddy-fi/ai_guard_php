<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$type = 'model';
$dir = get_yaml_directory($type);

$files = is_dir($dir)
    ? array_filter(scandir($dir), fn($f) => str_ends_with($f, '.yaml') || str_ends_with($f, '.yml'))
    : [];

$rules = [];
foreach ($files as $filename) {
    $filepath = $dir . $filename;
    $content = file_get_contents($filepath);

    try {
        $parsed = Yaml::parse($content);
        $model = $parsed['model'] ?? [];

        $rules[] = [
            'filename'    => $filename,
            'name'        => $model['name'] ?? '(Unnamed)',
            'description' => $model['description'] ?? '',
            'tone'        => $model['tone'] ?? '',
            'version'     => $model['version'] ?? '0.0.0',
            'mtime'       => date('Y-m-d H:i:s', filemtime($filepath)),
        ];
    } catch (Exception $e) {
        $rules[] = [
            'filename'    => $filename,
            'name'        => '(Parse error)',
            'description' => $e->getMessage(),
            'tone'        => '',
            'version'     => 'n/a',
            'mtime'       => '',
        ];
    }
}

// Sort by filename
usort($rules, fn($a, $b) => strcmp($a['filename'], $b['filename']));

ob_start();
?>

<div class="container mt-4">
    <h1>Manage Models (File-based)</h1>

    <div class="mb-3">
        <a href="edit_raw_yaml.php?new=1&type=model" class="btn btn-success">Create New Model</a>
    </div>

    <?php if (empty($rules)): ?>
        <p>No YAML models found in <code>data/models/</code>.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Filename</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Tone</th>
                    <th>Version</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><?= htmlspecialchars($rule['filename']) ?></td>
                        <td><?= htmlspecialchars($rule['name']) ?></td>
                        <td><?= htmlspecialchars($rule['description']) ?></td>
                        <td><?= htmlspecialchars($rule['tone']) ?></td>
                        <td><?= htmlspecialchars($rule['version']) ?></td>
                        <td><?= htmlspecialchars($rule['mtime']) ?></td>
                        <td>
                            <a href="edit_raw_yaml.php?file=<?= urlencode($rule['filename']) ?>&type=model" class="btn btn-sm btn-warning">Raw Edit</a>
                            <a href="test_model.php?file=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-success">Test</a>
                            <a href="download_model.php?type=model&name=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-secondary">Download</a>
                            <a href="delete_yaml.php?file=<?= urlencode($rule['filename']) ?>&type=model" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?= htmlspecialchars($file) ?>')">Delete</a>
                            <a href="yaml_history.php?file=<?= urlencode($rule['filename']) ?>&type=model" class="btn btn-sm btn-warning">History</a>                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>
  function confirmDelete(filename) {
    const input = prompt(`To confirm deletion of "<?= urlencode($rule['filename']) ?>", type: delete`);
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

<?php
$content = ob_get_clean();
include 'includes/layout.php';
