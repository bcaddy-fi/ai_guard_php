<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');
require __DIR__ . '/../app/controllers/db.php';

$personas = $pdo->query("SELECT id, name FROM personas ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$guardrails = $pdo->query("SELECT id, name FROM guardrails ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['type'];
  $ref = (int)$_POST['reference_id'];
  $input = $_POST['input_text'];
  $expect = $_POST['expected_behavior'];
  $notes = $_POST['notes'];

  $stmt = $pdo->prepare("INSERT INTO test_cases (type, reference_id, input_text, expected_behavior, notes) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$type, $ref, $input, $expect, $notes]);
  $success = "Test case added successfully.";
}
?>
<?php include 'includes/layout.php'; ?>
<div class="container mt-4">
  <h2>Add Test Case</h2>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <form method="post" class="card p-4 bg-light">
    <div class="mb-3">
      <label class="form-label">Type</label>
      <select class="form-select" name="type" required onchange="toggleRef(this.value)">
        <option value="">-- Select --</option>
        <option value="persona">Persona</option>
        <option value="guardrail">Guardrail</option>
      </select>
    </div>

    <div class="mb-3" id="personaRef" style="display:none;">
      <label>Persona</label>
      <select name="reference_id" class="form-select">
        <?php foreach ($personas as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3" id="guardrailRef" style="display:none;">
      <label>Guardrail</label>
      <select name="reference_id" class="form-select">
        <?php foreach ($guardrails as $g): ?>
          <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Test Input</label>
      <textarea name="input_text" class="form-control" required></textarea>
    </div>
    <div class="mb-3">
      <label>Expected Behavior</label>
      <select name="expected_behavior" class="form-select" required>
        <option value="pass">Pass</option>
        <option value="block">Block</option>
        <option value="log">Log</option>
        <option value="notify">Notify</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Notes</label>
      <textarea name="notes" class="form-control"></textarea>
    </div>
    <button class="btn btn-success" type="submit">Save</button>
  </form>
</div>

<script>
function toggleRef(type) {
  document.getElementById("personaRef").style.display = type === "persona" ? "block" : "none";
  document.getElementById("guardrailRef").style.display = type === "guardrail" ? "block" : "none";
}
</script>
