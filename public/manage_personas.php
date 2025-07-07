<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

$directory = __DIR__ . '/../data/persona/';
$success = '';
$error = '';
$filename = '';
$content = '';

// Load file
if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $path = $directory . $filename;
    if (file_exists($path)) {
        $content = file_get_contents($path);
    } else {
        $error = "File not found.";
    }
}

// Save file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'], $_POST['content'])) {
    $filename = basename($_POST['filename']);
    $path = $directory . $filename;

    if (preg_match('/\\.ya?ml$/', $filename)) {
        $rawContent = $_POST['content'];
        $lines = explode("\n", $rawContent);

        if (preg_match('/^#?\\s*version:\\s*(\\d+)\\.(\\d+)\\.(\\d+)/i', $lines[0], $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            $patch = (int)$matches[3] + 1;
            $lines[0] = "# version: {$major}.{$minor}.{$patch}";
        } else {
            array_unshift($lines, "# version: 1.0.0");
        }

        $updatedContent = implode("\n", $lines);

        try {
            Yaml::parse($updatedContent);
            file_put_contents($path, $updatedContent);
            $content = $updatedContent;

            session_start();
            $username = $_SESSION['user_id'] ?? 'unknown';
            $log = $pdo->prepare("INSERT INTO file_audit_log (username, filename, file_content) VALUES (?, ?, ?)");
            $log->execute([$username, $filename, $content]);

            header("Location: test_persona.php?file=" . urlencode($filename));
            exit;
        } catch (ParseException $e) {
            $error = "YAML format error on line " . $e->getParsedLine() . ": " . htmlspecialchars($e->getMessage());
            $content = $_POST['content'];
        }
    } else {
        $error = "Invalid filename. Must be .yaml or .yml.";
    }
}

$files = glob($directory . '*.yaml');
ob_start();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css">
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/yaml/yaml.js"></script>

<div class="row">
  <div class="col-md-4">
    <div class="card p-3 mb-4">
      <h5>Available Personas</h5>
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
      <h5>Edit Persona</h5>
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

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>

      <div class="mt-4">
        <h6>Preview Response</h6>
        <pre id="previewResult" class="bg-light p-3 border" style="min-height: 100px;"></pre>
        <button onclick="previewPersona()" class="btn btn-secondary mt-2">Run Preview</button>
      </div>
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

  async function previewPersona() {
    const content = editor.getValue();
    const resultEl = document.getElementById('previewResult');
    try {
      const res = await fetch('api/preview_persona.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({yaml: content})
      });

      if (!res.ok) {
        throw new Error(`HTTP error! Status: ${res.status}`);
      }

      const text = await res.text();
      try {
        const json = JSON.parse(text);
        resultEl.textContent = json.reply || json.error || 'No response';
      } catch (jsonErr) {
        resultEl.textContent = 'Error parsing LLM response. Response body:\n' + text;
      }
    } catch (err) {
      resultEl.textContent = 'Preview failed: ' + err.message;
    }
  }
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
