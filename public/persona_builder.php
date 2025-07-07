<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

use Symfony\Component\Yaml\Yaml;

ob_start();
?>
<h1>Build New AI Persona</h1>

<form method="post">
  <div class="mb-3">
    <label class="form-label">Persona Name *</label>
    <input type="text" class="form-control" name="persona_name" required placeholder="e.g., security_auditor" />
  </div>

  <div class="mb-3">
    <label class="form-label">Display Title or Role *</label>
    <input type="text" class="form-control" name="title" required placeholder="e.g., Compliance Advisor" />
  </div>

  <div class="mb-3">
    <label class="form-label">Brief Description *</label>
    <textarea class="form-control" name="description" rows="3" required></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Tone and Communication Style *</label>
    <select class="form-select" name="tone" required>
      <option value="">-- Select Tone --</option>
      <option value="formal">Formal</option>
      <option value="friendly">Friendly</option>
      <option value="technical">Technical</option>
      <option value="concise">Concise</option>
      <option value="empathetic">Empathetic</option>
      <option value="neutral">Neutral</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Rules / Behavior Constraints *</label>
    <textarea class="form-control" name="rules" rows="4" required></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Use Cases</label>
    <textarea class="form-control" name="use_cases" rows="3"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Blocked Topics</label>
    <textarea class="form-control" name="blocked_topics" rows="2"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Escalation Instructions</label>
    <textarea class="form-control" name="escalation" rows="2"></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Persona Owner or Approver</label>
    <input type="text" class="form-control" name="owner" />
  </div>

  <div class="mb-3">
    <label class="form-label">Date Created / Last Reviewed</label>
    <input type="date" class="form-control" name="date_reviewed" />
  </div>

  <button type="submit" class="btn btn-primary">Generate YAML</button>
</form>

<?php
function clean($key) {
  return htmlspecialchars(trim($_POST[$key] ?? ''), ENT_QUOTES);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'):
  $name = clean('persona_name');
  $title = clean('title');
  $description = clean('description');
  $tone = clean('tone');
  $rules = array_filter(array_map('trim', explode("\n", $_POST['rules'] ?? '')));
  $use_cases = clean('use_cases');
  $blocked = clean('blocked_topics');
  $escalation = clean('escalation');
  $owner = clean('owner');
  $date = clean('date_reviewed');

  $output = "# version: 1.0.0\npersona:\n";
  $output .= "  name: $name\n";
  $output .= "  title: \"$title\"\n";
  $output .= "  description: |\n";
  foreach (explode("\n", $description) as $line) {
    $output .= "    $line\n";
  }
  $output .= "  tone: $tone\n";
  $output .= "  rules:\n";
  foreach ($rules as $rule) {
    $output .= "    - $rule\n";
  }
  if ($use_cases) {
    $output .= "  use_cases: |\n";
    foreach (explode("\n", $use_cases) as $line) {
      $output .= "    $line\n";
    }
  }
  if ($blocked) {
    $output .= "  blocked_topics: |\n";
    foreach (explode("\n", $blocked) as $line) {
      $output .= "    $line\n";
    }
  }
  if ($escalation) {
    $output .= "  escalation_instructions: |\n";
    foreach (explode("\n", $escalation) as $line) {
      $output .= "    $line\n";
    }
  }
  if ($owner) {
    $output .= "  owner: \"$owner\"\n";
  }
  if ($date) {
    $output .= "  last_reviewed: \"$date\"\n";
  }

  $dir = __DIR__ . '/data/persona';
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
  $file_path = "$dir/{$name}.yaml";
  file_put_contents($file_path, $output);
  ?>
  <hr />
  <h3>Generated YAML</h3>
  <pre class="bg-light border p-3" id="yamlOutput"><?= htmlspecialchars($output) ?></pre>

  <h4>Live LLM Preview</h4>
  <textarea class="form-control mb-3" rows="3" id="userPrompt" placeholder="Ask this persona something..."></textarea>
  <button class="btn btn-sm btn-success" onclick="testPersona()">Test Persona</button>
  <pre class="bg-light p-3 mt-2" id="llmResponse">No response yet...</pre>

  <script>
    function testPersona() {
      const prompt = document.getElementById('userPrompt').value;
      const yaml = document.getElementById('yamlOutput').textContent;

      fetch('api/preview_persona.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ persona: yaml, prompt: prompt })
      })
      .then(res => res.json())
      .then(data => {
        document.getElementById('llmResponse').textContent = data.choices?.[0]?.message?.content || "No output.";
      })
      .catch(err => {
        document.getElementById('llmResponse').textContent = 'Error: ' + err;
      });
    }
  </script>
<?php endif; ?>

<?php
include 'includes/footer.php';
$content = ob_get_clean();
include 'includes/layout.php';
