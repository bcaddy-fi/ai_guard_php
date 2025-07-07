<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');


$error = '';
$success = '';
$editingUser = null;

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $use_sso = isset($_POST['use_sso']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($role)) {
        $error = "Username and role are required.";
    } else {
        if ($id) {
            // Update user
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET role = ?, use_sso = ?, password = ? WHERE id = ?");
                $stmt->execute([$role, $use_sso, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ?, use_sso = ? WHERE id = ?");
                $stmt->execute([$role, $use_sso, $id]);
            }

            // Audit log
            $log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, 'update', 'users', ?, ?)");
            $log->execute([$_SESSION['user_id'], $id, json_encode(compact('username', 'role', 'use_sso'))]);

            $success = "User updated successfully.";
        } else {
            // Create user
            if (empty($password)) {
                $error = "Password is required for new users.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, use_sso) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $role, $use_sso]);

                // Audit log
                $log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, 'create', 'users', ?, ?)");
                $log->execute([
                    $_SESSION['user_id'],
                    $pdo->lastInsertId(),
                    json_encode(compact('username', 'role', 'use_sso'))
                ]);

                $success = "User created successfully.";
            }
        }
    }
}

// Handle Edit link
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editingUser = $stmt->fetch();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);

    $log = $pdo->prepare("INSERT INTO audit_log (username, action, target_table, target_id, details) VALUES (?, 'delete', 'users', ?, ?)");
    $log->execute([
        $_SESSION['user_id'],
        $_GET['delete'],
        json_encode(['deleted_user_id' => $_GET['delete']])
    ]);

    $success = "User deleted.";
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY username")->fetchAll();

ob_start();
?>

<h3>User Administration</h3>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" class="card p-3 mb-4">
  <?php if ($editingUser): ?>
    <input type="hidden" name="id" value="<?= $editingUser['id'] ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Username</label>
    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($editingUser['username'] ?? '') ?>" <?= $editingUser ? 'readonly' : '' ?>>
  </div>

  <div class="mb-3">
    <label class="form-label">Password <?= $editingUser ? '(leave blank to keep current)' : '' ?></label>
    <input type="password" name="password" class="form-control" <?= $editingUser ? '' : 'required' ?>>
  </div>

  <div class="mb-3">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
      <?php foreach (['read', 'engineer', 'admin'] as $role): ?>
        <option value="<?= $role ?>" <?= ($editingUser['role'] ?? '') === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="use_sso" id="use_sso" value="1" <?= !empty($editingUser['use_sso']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="use_sso">Enable SSO login</label>
  </div>

  <button class="btn btn-primary" type="submit"><?= $editingUser ? 'Update' : 'Create' ?> User</button>
  <?php if ($editingUser): ?>
    <a href="user_admin.php" class="btn btn-secondary ms-2">Cancel</a>
  <?php endif; ?>
</form>

<table class="table table-bordered table-hover">
  <thead>
    <tr>
      <th>Username</th>
      <th>Role</th>
      <th>SSO</th>
      <th>Last Login</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td><?= $user['use_sso'] ? '&#10004;' : '&#x2716;' ?></td>
        <td><?= $user['last_login'] ?? '-' ?></td>
        <td>
          <a href="?edit=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
