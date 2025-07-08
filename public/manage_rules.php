<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

$stmt = $pdo->query("SELECT * FROM agent_rules ORDER BY updated_at DESC");
$rules = $stmt->fetchAll();

include 'includes/layout.php';
?>

<div class="container mt-4">
    <h1>Manage Agent Rules</h1>

    <div class="mb-3">
        <a href="build_rule.php" class="btn btn-success">Create New Rule</a>
    </div>

    <?php if (empty($rules)): ?>
        <p>No rules have been created yet.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Tone</th>
                    <th>Version</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><?= htmlspecialchars($rule['name']) ?></td>
                        <td><?= htmlspecialchars($rule['description']) ?></td>
                        <td><?= htmlspecialchars($rule['tone']) ?></td>
                        <td><?= htmlspecialchars($rule['version']) ?></td>
                        <td><?= htmlspecialchars($rule['updated_at']) ?></td>
                        <td>
                            <a href="edit_rule.php?id=<?= $rule['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="download_rule.php?name=<?= urlencode($rule['name']) ?>" class="btn btn-sm btn-secondary">YAML</a>
                            <form action="delete_rule.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this rule?');">
                                <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
