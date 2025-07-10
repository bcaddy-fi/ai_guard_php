<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$type = 'agent';
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
        $agent = $parsed['agent'] ?? [];

        $rules[] = [
            'filename'    => $filename,
            'name'        => $agent['name'] ?? '(Unnamed)',
            'description' => $agent['description'] ?? '',
            'tone'        => $agent['tone'] ?? '',
            'version'     => $agent['version'] ?? '0.0.0',
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
    <h1>Manage Agent Rules (File-based)</h1>

    <div class="mb-3">
        <a href="build_rule.php" class="btn btn-success">Create New Rule</a>
    </div>

    <?php if (empty($rules)): ?>
        <p>No YAML rules found in <code>data/agent_rules/</code>.</p>
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
                            <a href="edit_raw_yaml.php?file=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-warning">Raw Edit</a>
                            <a href="test_agent.php?type=agent&name=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-success">Test</a>
                            <a href="download_rule.php?type=agent&name=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-secondary">Download</a>
                            <a href="yaml_history.php?file=<?= urlencode($rule['filename']) ?>&type=agent" class="btn btn-sm btn-warning">History</a>
                         
<form action="delete_rule.php" method="post" style="display:inline-block;" onsubmit="return confirmDelete('<?= htmlspecialchars($rule['filename']) ?>')">
    <input type="hidden" name="filename" value="<?= htmlspecialchars($rule['filename']) ?>">
    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
</form>
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

                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>