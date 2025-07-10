<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../app/controllers/logger.php';
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$error = '';
$success = '';
$filename = $_GET['file'] ?? null;
$type = $_GET['type'] ?? 'agent';
$dir = get_yaml_directory($type);

if (!$filename) {
    http_response_code(400);
    die("Missing file parameter.");
}

$basename = basename($filename);
$filepath = $dir . $basename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die(ucfirst($type) . " file not found.");
}

$originalYaml = file_get_contents($filepath);
$yamlContent = $originalYaml;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawYaml = $_POST['yaml'] ?? '';

    try {
        $parsed = Yaml::parse($rawYaml);

        if (!is_array($parsed)) {
            throw new Exception("YAML is not structured as a key-value map.");
        }

        if (!isset($parsed[$type]) || !is_array($parsed[$type])) {
            throw new Exception("YAML must contain a top-level '$type' key.");
        }

        // Version handling
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

        $parsed[$type]['version'] = $newVersion;
        $yamlDump = Yaml::dump([$type => $parsed[$type]], 4, 2);
        $finalYaml = "# version: $newVersion\n" . $yamlDump;

        file_put_contents($filepath, $finalYaml);

        $oldArray = Yaml::parse($originalYaml);
        $newArray = Yaml::parse($finalYaml);
        $diff = array_diff_assoc_recursive($newArray[$type] ?? [], $oldArray[$type] ?? []);
        $summary = implode("\n", array_map(
            fn($k, $v) => "Changed: $k ? " . json_encode($v),
            array_keys($diff), $diff
        ));

        log_yaml_edit($pdo, [
            'file_type'       => $type,
            'filename'        => $basename,
            'email'           => $_SESSION['email'] ?? 'unknown',
            'username'        => $_SESSION['username'] ?? 'unknown',
            'version_before'  => $currentVersion,
            'version_after'   => $newVersion,
            'diff_summary'    => $summary,
            'diff_json'       => $diff,
            'action_taken'    => 'edit',
            'test_run_ids'    => '',
            'notes'           => 'Edited via raw YAML editor'
        ]);

        $success = "YAML saved successfully. Version updated to $newVersion.";
        $yamlContent = $finalYaml;

    } catch (Exception $e) {
        $error = "YAML validation failed: " . $e->getMessage();
    }
}

include 'includes/layout.php';
?>

<div class="container mt-5">
    <h2>Edit <?= ucfirst(htmlspecialchars($type)) ?> YAML: <?= htmlspecialchars($basename) ?></h2>

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
        <?php
        $pageMap = [
            'agent' => 'manage_rules.php',
            'persona' => 'manage_personas.php',
            'guardrails' => 'manage_guardrails.php',
            'model' => 'manage_models.php',
            'models' => 'manage_models.php',
        ];
        $backPage = $pageMap[$type] ?? 'index.php';
        ?>
        <a href="<?= $backPage ?>" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

<?php
function array_diff_assoc_recursive($array1, $array2) {
    $difference = [];
    foreach ($array1 as $key => $value) {
        if (is_array($value)) {
            if (!isset($array2[$key]) || !is_array($array2[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if (!empty($new_diff)) {
                    $difference[$key] = $new_diff;
                }
            }
        } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
            $difference[$key] = $value;
        }
    }
    return $difference;
}
?>
