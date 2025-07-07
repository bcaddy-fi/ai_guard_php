<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

use Symfony\Component\Yaml\Yaml;

ob_start();
?>
<h1>&#9881; Build New Guardrail</h1>

<form method="post">
  <div class="mb-3">
    <label class="form-label">Guardrail Name *</label>
    <input type="text" class="form-control" name="name" required placeholder="e.g., restrict_financial_advice" />
  </div>

  <div class="mb-3">
    <label class="form-label">Description *</label>
    <textarea class="form-control" name="description" rows="3" required></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Action *</label>
    <select class="form-select" name="action" required>
      <option value="">-- Select Action --</option>
      <option value="block">Block</option>
      <option value="log">Log</option>
      <option value="notify">Notify</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Rule Match Pattern (Regex) *</label>
    <input type="text" class="form-control" name="pattern" required placeholder="e.g., (guaranteed\s+return|stock\s+tip)" />
  </div>

  <div class="mb-3">
    <label class="form-label">Regex Helper <small class="text-muted">(optional)</small></label>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" data-bs-toggle="collapse" data-bs-target="#regexHelper">Toggle Helper</button>
    <div class="collapse" id="regexHelper">
      <div class="card card-body bg-light">
        <p><strong>Examples:</strong></p>
        <ul>
          <li><code>guaranteed\s+return</code> - matches "guaranteed return"</li>
          <li><code>stock\s+tip</code> - matches "stock tip"</li>
          <li><code>select\s+.*from</code> - matches SQL-like patterns</li>
          <li><code>&lt;script.*&gt;</code> - matches XSS script tags</li>
        </ul>

        <label class="form-label mt-2">Test a Regex Pattern</label>
        <input type="text" class="form-control" id="regexPattern" placeholder="e.g. stock\s+tip">

        <label class="form-label mt-2">Test Input</label>
        <input type="text" class="form-control" id="regexTestInput" placeholder="e.g. This is a stock tip from John.">

        <div id="regexResult" class="mt-2 fw-bold"></div>
      </div>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Custom Response Message</label>
    <textarea class="form-control" name="response" rows="3" placeholder="Optional custom message if matched."></textarea>
  </div>

  <button type="submit" class="btn btn-primary">&#9998; Generate Guardrail YAML</button>
</form>

<?php
function clean($key) {
  return htmlspecialchars(trim($_POST[$key] ?? ''), ENT_QUOTES);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'):
  $name = clean('name');
  $desc = clean('description');
  $action = clean('action');
  $pattern = trim($_POST['pattern']);
  $response = trim($_POST['response']);

$output = "# version: 1.0.0\n";
$output .= "guardrail:\n";
$output .= "  name: " . $name . "\n";
$output .= "  description: |\n";
foreach (explode("\n", $desc) as $line) {
    $output .= "    " . $line . "\n";
}
$output .= "  action: " . $action . "\n";
$output .= "  rules:\n";
$output .= "    - match: \"" . addslashes($pattern) . "\"\n";
$output .= "      type: regex\n";
if (!empty($response)) {
    $output .= "      response: |\n";
    foreach (explode("\n", $response) as $line) {
        $output .= "        " . $line . "\n";
    }
}
  $dir = __DIR__ . '/../data/guardrails';
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
  $file_path = "$dir/{$name}.yaml";
  file_put_contents($file_path, $output);
?>
  <hr />
  <h3>&#128196; Generated YAML</h3>
  <pre class="bg-light border p-3"><?= htmlspecialchars($output) ?></pre>
<?php endif; ?>

<script>
  const patternInput = document.getElementById("regexPattern");
  const testInput = document.getElementById("regexTestInput");
  const resultDisplay = document.getElementById("regexResult");

  [patternInput, testInput].forEach(el => {
    el?.addEventListener("input", () => {
      const pattern = patternInput.value;
      const input = testInput.value;

      try {
        const regex = new RegExp(pattern, "i");
        const match = regex.test(input);
        resultDisplay.textContent = match ? "&#10004; Pattern MATCHED the input." : "&#10060; Pattern did NOT match.";
        resultDisplay.className = match ? "text-success fw-bold" : "text-danger fw-bold";
      } catch (e) {
        resultDisplay.textContent = "&#9888;&#65039; Invalid Regex: " + e.message;
        resultDisplay.className = "text-warning";
      }
    });
  });
</script>

<?php
include 'includes/footer.php';
$content = ob_get_clean();
include 'includes/layout.php';
?>
