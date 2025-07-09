<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$filename = $_GET['file'] ?? null;
$error = '';
$success = '';

if (!$filename) {
    http_response_code(400);
    die("Missing file parameter.");
}

$basename = basename($filename);
$filepath = __DIR__ . '/../data/agent_rules/' . $basename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die("Agent rule file not found.");
}

$yamlContent = file_get_contents($filepath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawYaml = $_POST['yaml'] ?? '';

    try {
        // Validate YAML structure
        $parsed = Yaml::parse($rawYaml);
        if (!isset($parsed['agent'])) {
            throw new Exception("YAML must contain a top-level 'agent' key.");
        }

        // Auto-increment version
        $lines = explode("\n", $rawYaml);
        $versionLineIndex = null;
        $currentVersion = '0.0.1';

        foreach ($lines as $i => $line) {
            if (preg_match('/^#\s*version:\s*(\d+\.\d+\.\d+)/i', $line, $matches)) {
                $currentVersion = $matches[1];
                $versionLineIndex = $i;
                break;
            }
        }

        list($major, $minor, $patch) = explode('.', $currentVersion);
        $newVersion = "$major.$minor." . ((int)$patch + 1);

        if ($versionLineIndex !== null) {
            $lines[$versionLineIndex] = "# version: $newVersion";
        } else {
            array_unshift($lines, "# version: $newVersion");
        }

        $parsed['agent']['version'] = $newVersion;

        $yamlDump = Yaml::dump(['agent' => $parsed['agent']], 4, 2);
        $finalYaml = "# version: $newVersion\n" . $yamlDump;

        file_put_contents($filepath, $finalYaml);

        $success = "YAML saved successfully. Version updated to $newVersion.";
        $yamlContent = $finalYaml;

    } catch (Exception $e) {
        $error = "YAML validation failed: " . $e->getMessage();
    }
}

include 'includes/layout.php';
?>

<div class="container mt-5">
    <h2>Edit Agent YAML: <?= htmlspecialchars($basename) ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="yaml" class="form-label">YAML Content</label>
            <textarea name="yaml" class="form-control" rows="20" required><?= htmlspecialchars($yamlContent) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save YAML</button>
        <a href="manage_rules.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
