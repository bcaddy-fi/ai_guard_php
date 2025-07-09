<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_role('admin');
require __DIR__ . '/../app/controllers/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  die("Missing test case ID.");
}

$stmt = $pdo->prepare("SELECT * FROM test_cases WHERE id = ?");
$stmt->execute([$id]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
  die("Test case not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = $_POST['input'] ?? '';
  $expected = $_POST['expected_output'] ?? '';
  $notes = $_POST['notes'] ?? '';

  $update = $pdo->prepare("UPDATE test_cases SET input = ?, expected_output = ?, notes = ?, updated_at = NOW() WHERE id = ?");
  $update->execute([$input, $expected, $notes, $id]);
  header("Location: test_case_admin.php");
  exit;
}
?>

<?php include 'includes/layout.php'; ?>
<div class="container mt-4">
  <h2>Edit Test Case</h2>
  <form method="POST" class="card p-4 shadow-sm bg-light">
    <div class="mb-3">
      <label class="form-label">Prompt</label>
      <textarea name="input" class="form-control" rows="3" required><?= htmlspecialchars($test['input'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Expected Output</label>
      <textarea name="expected_output" class="form-control" rows="3" required><?= htmlspecialchars($test['expected_output'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($test['notes'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="test_case_admin.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>
