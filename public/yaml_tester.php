<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';

$selectedType = $_GET['type'] ?? '';
$selectedFile = $_GET['name'] ?? '';

$types = ['persona', 'guardrail', 'agent', 'model'];
$files = [];

foreach ($types as $type) {
    try {
        $dir = get_yaml_directory($type);
        if (is_dir($dir)) {
            $files[$type] = array_values(array_filter(scandir($dir), function ($f) {
                return str_ends_with($f, '.yaml') || str_ends_with($f, '.yml');
            }));
        } else {
            $files[$type] = [];
        }
    } catch (Exception $e) {
        $files[$type] = [];
    }
}

ob_start();
?>

<div class="container mt-4">
  <h2><i class="fa fa-flask"></i> YAML Prompt Tester</h2>
  <form id="yamlTestForm" class="card p-4 shadow-sm mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" id="type" class="form-select" required>
          <option value="">Select Type</option>
          <?php foreach ($types as $type): ?>
            <option value="<?= $type ?>" <?= $type === $selectedType ? 'selected' : '' ?>>
              <?= ucfirst($type) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Filename</label>
        <select name="filename" id="filename" class="form-select" required>
          <option value="">Select a YAML File</option>
          <?php if ($selectedType && isset($files[$selectedType])): ?>
            <?php foreach ($files[$selectedType] as $file): ?>
              <option value="<?= $file ?>" <?= $file === $selectedFile ? 'selected' : '' ?>>
                <?= $file ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="col-md-12">
        <label class="form-label">Prompt</label>
        <textarea name="prompt" class="form-control" rows="3" required placeholder="Enter a user prompt to test..."></textarea>
      </div>

      <div class="col-md-12 text-end">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-paper-plane"></i> Run Test
        </button>
      </div>
    </div>
  </form>

  <h5>OpenAI JSON Response</h5>
  <pre id="jsonOutput" class="bg-dark text-white p-3 rounded" style="min-height: 200px;">Awaiting test result...</pre>
</div>

<script>
const allFiles = <?= json_encode($files) ?>;

document.getElementById('type').addEventListener('change', function () {
  const selected = this.value;
  const fileSelect = document.getElementById('filename');
  fileSelect.innerHTML = '<option value="">Select a YAML File</option>';

  if (allFiles[selected]) {
    allFiles[selected].forEach(file => {
      const opt = document.createElement('option');
      opt.value = file;
      opt.textContent = file;
      fileSelect.appendChild(opt);
    });
  }
});

document.getElementById('yamlTestForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  const output = document.getElementById('jsonOutput');

  output.textContent = 'Sending test to OpenAI...';

  fetch('/test_api.php', {
    method: 'POST',
    body: formData
  })
  .then(async res => {
    const contentType = res.headers.get("content-type");
    if (contentType && contentType.includes("application/json")) {
      const json = await res.json();
      output.textContent = JSON.stringify(json, null, 2);
    } else {
      const text = await res.text();
      output.textContent = "? Server returned non-JSON response:\n\n" + text;
    }
  })
  .catch(err => {
    output.textContent = 'Fetch error: ' + err.message;
  });
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
