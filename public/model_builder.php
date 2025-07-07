<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = basename($_POST['fileName']) . '.yaml';
    $path = __DIR__ . '/data/models/' . $filename;
    $yaml = $_POST['yamlContent'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9._-]+\.ya?ml$/', $filename)) {
        $error = "Invalid file name.";
    } else {
        $lines = explode("\n", $yaml);

        if (preg_match('/^#?\s*version:\s*(\d+)\.(\d+)\.(\d+)/i', $lines[0], $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            $patch = (int)$matches[3] + 1;
            $lines[0] = "# version: {$major}.{$minor}.{$patch}";
        } else {
            array_unshift($lines, "# version: 1.0.0");
        }

        $versionedYaml = implode("\n", $lines);
        file_put_contents($path, $versionedYaml);
        $success = "Model file '{$filename}' saved successfully with versioning.";

        $username = $_SESSION['user_id'] ?? 'unknown';
        $log = $pdo->prepare("INSERT INTO file_audit_log (username, filename, file_content) VALUES (?, ?, ?)");
        $log->execute([$username, $filename, $versionedYaml]);
    }
}

ob_start();
?>

<h2>Advanced Model YAML Wizard</h2>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" onsubmit="return generateComplexYAML();">
  <input type="hidden" name="yamlContent" id="yamlContent" />

  <div class="mb-3">
    <label class="form-label">File Name (no extension)</label>
    <input type="text" id="fileName" name="fileName" class="form-control" required placeholder="example_model">
  </div>

  <div class="mb-3">
    <label class="form-label">Model Identifier</label>
    <input type="text" id="modelId" class="form-control" required placeholder="qwen/qwen3-8b">
  </div>

  <div class="mb-3">
    <label class="form-label">Base Key</label>
    <input type="text" id="baseKey" class="form-control" required placeholder="lmstudio-community/qwen3-8b-gguf">
  </div>

  <div class="mb-3">
    <label class="form-label">Source (Hugging Face)</label>
    <input type="text" id="hfUser" class="form-control mb-2" placeholder="User" value="lmstudio-community">
    <input type="text" id="hfRepo" class="form-control" placeholder="Repo" value="Qwen-3-8B-GGUF">
  </div>

  <div class="mb-3">
    <label class="form-label">Metadata Overrides</label>
    <textarea id="metadataOverrides" class="form-control" rows="6" placeholder="domain: llm&#10;architectures: [llama]&#10;..."></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Config Fields</label>
    <textarea id="configFields" class="form-control" rows="4" placeholder="- key: llm.prediction.topKSampling&#10;  value: 20"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Custom Fields</label>
    <textarea id="customFields" class="form-control" rows="6" placeholder="- key: enableThinking&#10;  displayName: Enable Thinking&#10;  type: boolean&#10;  defaultValue: true"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Suggestions</label>
    <textarea id="suggestions" class="form-control" rows="6" placeholder="- message: Suggested values...&#10;  conditions:&#10;    - type: equals&#10;      key: $.enableThinking&#10;      value: true"></textarea>
  </div>

  <div class="d-grid">
    <button class="btn btn-primary" type="submit">Save Model</button>
  </div>

  <h5 class="mt-4">YAML Preview</h5>
  <div class="bg-light p-3 border rounded" id="output" style="white-space: pre; min-height: 200px;"></div>
</form>

<script>
function indent(text, spaces = 2) {
  return text.split("\n").map(line => " ".repeat(spaces) + line).join("\n");
}

function generateComplexYAML() {
  const modelId = document.getElementById('modelId').value.trim();
  const fileName = document.getElementById('fileName').value.trim();
  const baseKey = document.getElementById('baseKey').value.trim();
  const hfUser = document.getElementById('hfUser').value.trim();
  const hfRepo = document.getElementById('hfRepo').value.trim();
  const metadataOverrides = document.getElementById('metadataOverrides').value.trim();
  const configFields = document.getElementById('configFields').value.trim();
  const customFields = document.getElementById('customFields').value.trim();
  const suggestions = document.getElementById('suggestions').value.trim();

  const yaml = `model: ${modelId}
base:
  - key: ${baseKey}
    sources:
      - type: huggingface
        user: ${hfUser}
        repo: ${hfRepo}
metadataOverrides:
${indent(metadataOverrides, 2)}
config:
  operation:
    fields:
${indent(configFields, 6)}
customFields:
${indent(customFields, 2)}
suggestions:
${indent(suggestions, 2)}`;

  document.getElementById("yamlContent").value = yaml;
  document.getElementById("output").textContent = `# File: ${fileName}.yaml\n\n${yaml}`;
  return true;
}
</script>
<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
