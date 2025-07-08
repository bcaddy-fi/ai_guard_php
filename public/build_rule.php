<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');
require 'includes/rule_utils.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $tone = trim($_POST['tone']);
    $categories = trim($_POST['categories']);
    $rules = array_filter($_POST['rules'] ?? []);
    $examples_good = array_filter($_POST['examples_good'] ?? []);
    $examples_bad = array_filter($_POST['examples_bad'] ?? []);
    $version = trim($_POST['version']) ?: '1.0.0';

    if (empty($name)) {
        $errors[] = "Agent name is required.";
    }

    if (empty($rules)) {
        $errors[] = "At least one rule is required.";
    }

    if (empty($errors)) {
        // Convert to JSON for DB
        $rulesJson = json_encode(array_values($rules));
        $goodJson = json_encode(array_map(fn($e) => ['user' => '', 'ai' => $e], $examples_good));
        $badJson = json_encode(array_map(fn($e) => ['user' => '', 'ai' => $e], $examples_bad));

        // Save to DB
        $stmt = $pdo->prepare("INSERT INTO agent_rules 
            (name, description, tone, categories, rules, examples_good, examples_bad, version)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $description, $tone, $categories,
            $rulesJson, $goodJson, $badJson, $version
        ]);

        // Prepare YAML
        $yaml = generate_rule_yaml([
            'name' => $name,
            'description' => $description,
            'tone' => $tone,
            'categories' => $categories,
            'rules' => $rulesJson,
            'examples_good' => $goodJson,
            'examples_bad' => $badJson,
            'version' => $version
        ]);

        if (save_rule_yaml_file($name, $yaml)) {
            $success = "Rule saved successfully and YAML file created.";
        } else {
            $errors[] = "Rule saved, but failed to write YAML file.";
        }
    }
}
?>

<?php include 'includes/layout.php'; ?>
<div class="container mt-4">
    <h1>Create New Agent Rule</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Agent Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Tone</label>
            <input type="text" name="tone" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Categories (comma-separated)</label>
            <input type="text" name="categories" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Rules</label>
            <?php for ($i = 0; $i < 5; $i++): ?>
                <input type="text" name="rules[]" class="form-control mb-1">
            <?php endfor; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Good Responses (AI output)</label>
            <?php for ($i = 0; $i < 3; $i++): ?>
                <textarea name="examples_good[]" class="form-control mb-1" rows="1"></textarea>
            <?php endfor; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Bad Responses (AI output)</label>
            <?php for ($i = 0; $i < 3; $i++): ?>
                <textarea name="examples_bad[]" class="form-control mb-1" rows="1"></textarea>
            <?php endfor; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Version</label>
            <input type="text" name="version" class="form-control" value="1.0.0">
        </div>
        <button type="submit" class="btn btn-primary">Save Rule</button>
    </form>
</div>
