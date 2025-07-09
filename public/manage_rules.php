<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

ob_start();
$directory = __DIR__ . '/../data/agent_rules/';
$files = glob($directory . '*.yaml');
$rules = [];

foreach ($files as $filepath) {
    $filename = basename($filepath);
    $content = file_get_contents($filepath);

    // Strip leading comment lines like "# version: x.y.z"
    $lines = explode("\n", $content);
    while (!empty($lines) && str_starts_with(trim($lines[0]), '#')) {
        array_shift($lines);
    }
    $strippedYaml = implode("\n", $lines);

    try {
        $parsed = Yaml::parse($strippedYaml);
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

// ?? Sort by filename
usort($rules, function ($a, $b) {
    return strcmp($a['filename'], $b['filename']);
});

$content = ob_get_clean();
include 'includes/layout.php';

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
    <a href="test_agent.php?file=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-success">Test</a>
    <a href="download_rule.php?name=<?= urlencode($rule['filename']) ?>" class="btn btn-sm btn-secondary">Download</a>
    <form action="delete_rule.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this rule file?');">
        <input type="hidden" name="filename" value="<?= htmlspecialchars($rule['filename']) ?>">
        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
    </form>
</td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
