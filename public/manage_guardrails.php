<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

$directory = __DIR__ . '/../data/guardrails/';
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$success = '';
$error = '';
$filename = '';
$content = '';

// Handle file load
if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $path = $directory . $filename;
    if (file_exists($path)) {
        $content = file_get_contents($path);
    } else {
        $error = "File not found.";
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'], $_POST['content'])) {
    $filename = basename($_POST['filename']);
    $path = $directory . $filename;
    $autoVersion = isset($_POST['auto_version']);

    if (preg_match('/\\.ya?ml$/', $filename)) {
        $rawContent = $_POST['content'];
        $lines = explode("\n", $rawContent);

        // Add or update version line
        if (preg_match('/^#?\s*version:\s*(\d+)\.(\d+)\.(\d+)/i', $lines[0], $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            $patch = (int)$matches[3] + 1;
            $lines[0] = "# version: {$major}.{$minor}.{$patch}";
        } else {
            array_unshift($lines, "# version: 1.0.0");
        }

        $updatedContent = implode("\n", $lines);

        try {
            // YAML format validation
            Yaml::parse($updatedContent);

            // Save the file
            file_put_contents($path, $updatedContent);
            $success = $autoVersion ? "Saved successfully with versioning." : "Saved successfully.";

            // Log audit
            $username = $_SESSION['user_id'] ?? 'unknown';
            $audit = $pdo->prepare("INSERT INTO file_audit_log (username, filename, file_content) VALUES (?, ?, ?)");
            $audit->execute([$username, $filename, $updatedContent]);

            $content = $updatedContent;
        } catch (ParseException $e) {
            $error = "YAML format error on line " . $e->getParsedLine() . ": " . htmlspecialchars($e->getMessage());
            $content = $_POST['content']; // Restore user's raw content
        }
    } else {
        $error = "Invalid filename. Must be .yaml or .yml";
    }
}

// List YAML files
$files = glob($directory . '*.yaml');
ob_start();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css">
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/yaml/yaml.js"></script>
<style>
textarea {
  font-family: monospace;
  font-size: 0.9rem;
  height: 800px;
}
</style>

<div class="row">
  <div class="col-md-4">
    <div class="card p-3 mb-4">
      <h5>Available Guardrails</h5>
      <ul class="list-group">
        <?php foreach ($files as $file): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="?file=<?= urlencode(basename($file)) ?>"><?= htmlspecialchars(basename($file)) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card p-4">
      <h5>Edit Guardrail</h5>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label for="filename" class="form-label">File Name</label>
          <input type="text" class="form-control" name="filename" id="filename" value="<?= htmlspecialchars($filename) ?>" required>
        </div>

        <div class="mb-3">
          <label for="content" class="form-label">YAML Content</label>
          <textarea id="yamlEditor" name="content"><?= htmlspecialchars($content) ?></textarea>
        </div>

        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="auto_version" id="auto_version" <?= isset($_POST['auto_version']) ? 'checked' : 'checked' ?>>
          <label class="form-check-label" for="auto_version">
            Auto-version on save?
          </label>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  const editor = CodeMirror.fromTextArea(document.getElementById('yamlEditor'), {
    lineNumbers: true,
    mode: "yaml",
    theme: "default",
    lineWrapping: true,
    matchBrackets: true
  });
  editor.setSize(null, "800px");
</script>

<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
