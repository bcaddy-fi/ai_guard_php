<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('engineer');
require __DIR__ . '/../app/controllers/db.php';

$guardrailDir = __DIR__ . '/../data/guardrails/';
$guardrailFiles = glob($guardrailDir . '*.yaml');
$selected = $_GET['file'] ?? '';
$yamlContent = '';

if ($selected && is_readable($guardrailDir . $selected)) {
    $yamlContent = file_get_contents($guardrailDir . $selected);
}

ob_start();
?>
<div class="container py-4">
  <h2><i class="fa fa-vial"></i> AI YAML Tester</h2>

  <form method="GET" class="mb-3">
    <div class="input-group">
      <label class="input-group-text" for="file">Choose Guardrail</label>
      <select class="form-select" name="file" id="file" onchange="this.form.submit()">
        <option value="">-- Select a guardrail file --</option>
        <?php foreach ($guardrailFiles as $f): $name = basename($f); ?>
          <option value="<?= htmlspecialchars($name) ?>" <?= $name === $selected ? 'selected' : '' ?>>
            <?= htmlspecialchars($name) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <?php if ($selected && $yamlContent): ?>
    <form id="testForm" method="POST">
      <input type="hidden" name="filename" value="<?= htmlspecialchars($selected) ?>">
      <div class="mb-3">
        <label class="form-label">YAML Content</label>
        <textarea name="yaml" class="form-control" rows="15" required><?= htmlspecialchars($yamlContent) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Prompt</label>
        <textarea name="prompt" class="form-control" rows="3" required placeholder="Type a prompt to test..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Run Test</button>
    </form>

    <div class="mt-4">
      <h4>Test Result</h4>
      <pre id="output" class="bg-light p-3 border rounded text-monospace" style="max-height: 400px; overflow-y: auto;"></pre>
    </div>
  <?php elseif ($selected): ?>
    <div class="alert alert-danger mt-3">Unable to read the selected YAML file.</div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('testForm');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const formData = new FormData(form);
    const output = document.getElementById('output');
    output.textContent = 'Running...';

    try {
      const response = await fetch('api/ai_yamltester_run.php', {
        method: 'POST',
        body: formData
      });

      const text = await response.text();
      try {
        const json = JSON.parse(text);
        output.textContent = JSON.stringify(json, null, 2);
      } catch (err) {
        output.textContent = "Raw Response (Not JSON):\n\n" + text;
      }

      window.scrollTo({ top: output.offsetTop - 100, behavior: 'smooth' });
    } catch (err) {
      output.textContent = 'Error: ' + err;
    }
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
