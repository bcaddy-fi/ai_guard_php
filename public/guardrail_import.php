<?php
require __DIR__ . '/../app/controllers/auth.php';

require_login();
require_role('admin');

$uploadDir = realpath(__DIR__ . '/../data/guardrails/') . '/';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['guardrail_yaml'])) {
    $file = $_FILES['guardrail_yaml'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $contents = file_get_contents($file['tmp_name']);

        // Check for version, add if missing
        if (!preg_match('/^#\s*version:\s*\d+\.\d+\.\d+/mi', $contents)) {
            $contents = "# version: 1.0.0\n" . $contents;
        }

        // Extract guardrail name
        if (preg_match('/name:\s*(\w+)/', $contents, $matches)) {
            $name = $matches[1];
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
            $savePath = $uploadDir . $safeName . '.yaml';

            if (file_put_contents($savePath, $contents)) {
                $success = "Guardrail '$safeName' uploaded successfully.";
            } else {
                $error = "Failed to save file.";
            }
        } else {
            $error = "YAML missing guardrail name.";
        }
    } else {
        $error = "Upload error: " . $file['error'];
    }
}
?>

<?php include 'includes/layout.php'; ?>
<div class="container mt-4">
    <h2><i class="fa fa-upload"></i> Import Guardrail YAML</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm bg-white">
        <div class="mb-3">
            <label for="guardrail_yaml" class="form-label">Upload YAML File</label>
            <input type="file" class="form-control" name="guardrail_yaml" id="guardrail_yaml" accept=".yaml,.yml" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Guardrail</button>
    </form>
</div>
