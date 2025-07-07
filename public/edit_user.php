<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';

$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $update_fields = [];
    $params = [];

    if ($new_username !== $user['username']) {
        $update_fields[] = "username = ?";
        $params[] = $new_username;
    }

    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_fields[] = "password_hash = ?";
        $params[] = $hash;
    }

    if ($update_fields) {
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $pdo->prepare($sql)->execute($params);
        $success = "User updated successfully.";
        $user['username'] = $new_username;
    }
}

ob_start();
?>

<div class="card p-4">
  <h4 class="mb-3">Edit User</h4>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">New Password (leave blank to keep current)</label>
      <input type="password" name="password" id="password" class="form-control">
    </div>
    <div class="d-flex justify-content-between">
      <a href="user_admin.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';

