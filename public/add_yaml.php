<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_once __DIR__ . '/../app/helpers/yaml_dirs.php';
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$allowedTypes = ['persona', 'guardrail', 'model', 'agent'];
$type = $_GET['type'] ?? 'persona';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedType = $_POST['type'] ?? '';
    $filename = trim($_POST['filename'] ?? '');
    $yamlContent = $_POST['yaml'] ?? '';

    if (!$filename || !$yamlContent || !in_array($selectedType, $allowedTypes)) {
        $error = "All fields are required.";
    } else {
        try {
            $parsed = Yaml::parse($yamlContent); // Validate syntax

            $savePath = get_yaml_directory($selectedType) . basename($filename);
            if (!str_ends_with($savePath, '.yaml')) {
                $savePath .= '.yaml';
            }

            if (file_exists($savePath)) {
                $error = "A file with this name already exists.";
            } else {
                file_put_contents($savePath, $yamlContent);
                $success = "YAML file created successfully.";
            }
        } catch (Exception $e) {
            $error = "YAML validation error: " . $e->getMessage();
        }
    }
}

include 'includes/layout.php';
?>

<div class="container mt-5">
  <h2>Add New YAML</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label for="type" class="form-label">Type</label>
      <select name="type" class="form-select" required>
        <?php foreach ($allowedTypes as $opt): ?>
          <option value="<?= $opt ?>" <?= $opt === $type ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="filename" class="form-label">Filename</label>
      <input type="text" name="filename" class="form-control" required placeholder="e.g., new_persona.yaml">
    </div>

    <div class="mb-3">
      <label for="yaml" class="form-label">YAML Content</label>
      <textarea name="yaml" class="form-control" rows="20" required></textarea>
    </div>

    <button type="submit" class="btn btn-success">Create YAML</button>
    <a href="manage_personas.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
