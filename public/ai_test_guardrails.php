<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require __DIR__ . '/../app/controllers/openai_inference.php'; // assumes call_openai_api() is here

$guardrailDir = __DIR__ . '/../data/guardrails/';
$files = glob($guardrailDir . '*.yaml');

$selected = $_GET['file'] ?? '';
$yamlContent = '';
if ($selected && file_exists($guardrailDir . $selected)) {
    $yamlContent = file_get_contents($guardrailDir . $selected);
}

ob_start();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container mt-5">
  <h2 class="mb-4">Guardrail Testing Tool</h2>

  <form id="guardrailForm" method="get" class="mb-4">
    <label for="file" class="form-label">Select Guardrail YAML</label>
    <select name="file" id="file" class="form-select" onchange="this.form.submit()">
      <option value="">-- Select a guardrail --</option>
      <?php foreach ($files as $f): $base = basename($f); ?>
        <option value="<?= htmlspecialchars($base) ?>" <?= $selected === $base ? 'selected' : '' ?>>
          <?= htmlspecialchars($base) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($yamlContent): ?>
  <form id="testForm">
    <div class="mb-3">
      <label for="yaml" class="form-label">YAML Content</label>
      <textarea name="yaml" id="yaml" class="form-control" rows="15"><?= htmlspecialchars($yamlContent) ?></textarea>
    </div>

    <div class="mb-3">
      <label for="prompt" class="form-label">Prompt to Test</label>
      <textarea name="prompt" id="prompt" class="form-control" rows="3" required></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Run Test</button>
  </form>

  <div class="mt-4" id="results" style="display:none;">
    <h5>JSON Payload Sent:</h5>
    <pre id="payloadBox" class="bg-light p-3 border rounded"></pre>

    <h5 class="mt-4">AI Response:</h5>
    <div class="alert alert-info" id="aiResponse" style="white-space: pre-wrap;"></div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('testForm');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const yaml = document.getElementById('yaml').value;
    const prompt = document.getElementById('prompt').value;

    const payload = {
      model: 'gpt-4',
      temperature: 0.3,
      messages: [
        { role: 'system', content: yaml },
        { role: 'user', content: prompt }
      ]
    };

    document.getElementById('payloadBox').textContent = JSON.stringify(payload, null, 2);
    document.getElementById('results').style.display = 'block';

    try {
      const res = await fetch('test_guardrail_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      document.getElementById('aiResponse').textContent = data.choices?.[0]?.message?.content || JSON.stringify(data);
    } catch (err) {
      document.getElementById('aiResponse').textContent = 'Error: ' + err;
    }
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
