<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new bug
    if (isset($_POST['title'], $_POST['description'], $_POST['priority'])) {
        $stmt = $pdo->prepare("INSERT INTO bug_tracker (title, description, reported_by, status, priority) VALUES (?, ?, ?, 'open', ?)");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_SESSION['user_id'] ?? 0,
            $_POST['priority']
        ]);
        $success = "Bug reported successfully.";
    }

    // Update bug
    if (isset($_POST['edit_id'])) {
        $stmt = $pdo->prepare("UPDATE bug_tracker SET title = ?, description = ?, status = ?, close_comment = ?, priority = ? WHERE id = ?");
        $stmt->execute([
            $_POST['edit_title'],
            $_POST['edit_description'],
            $_POST['edit_status'],
            $_POST['edit_close_comment'] ?? null,
            $_POST['edit_priority'] ?? 'medium',
            $_POST['edit_id']
        ]);
        $success = "Bug updated.";
    }

    // Delete bug
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM bug_tracker WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success = "Bug deleted.";
    }
}

$stmt = $pdo->query("SELECT * FROM bug_tracker ORDER BY created_at DESC");
$bugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bug Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php"><img src="https://guard-manager.isms-cloud.com/aiguardmanager.png" width="110" height="35"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="bug_tracker.php">Bug Tracker</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_console.php">Admin Console</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container py-5">
  <h2>Bug Tracker</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 mb-4">
    <h4>Report New Bug</h4>
    <input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
    <textarea name="description" class="form-control mb-2" placeholder="Description" required></textarea>
    <select name="priority" class="form-select mb-2">
      <option value="low">Low</option>
      <option value="medium" selected>Medium</option>
      <option value="high">High</option>
      <option value="critical">Critical</option>
    </select>
    <button type="submit" class="btn btn-danger">Submit</button>
  </form>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bugs as $bug): ?>
        <tr>
          <td><?= $bug['id'] ?></td>
          <td><?= htmlspecialchars($bug['title']) ?></td>
          <td><?= htmlspecialchars($bug['status']) ?></td>
          <td><?= htmlspecialchars($bug['priority'] ?? 'medium') ?></td>
          <td><?= htmlspecialchars($bug['created_at']) ?></td>
          <td>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $bug['id'] ?>">Edit</button>

            <div class="modal fade" id="editModal<?= $bug['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $bug['id'] ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header">
                      <h5 class="modal-title" id="editModalLabel<?= $bug['id'] ?>">Edit Bug #<?= $bug['id'] ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="edit_id" value="<?= $bug['id'] ?>">
                      <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="edit_title" class="form-control" value="<?= htmlspecialchars($bug['title']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="edit_description" class="form-control" required><?= htmlspecialchars($bug['description']) ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="edit_status" class="form-select">
                          <option value="open" <?= $bug['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                          <option value="in_progress" <?= $bug['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                          <option value="closed" <?= $bug['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="edit_priority" class="form-select">
                          <option value="low" <?= ($bug['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                          <option value="medium" <?= ($bug['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                          <option value="high" <?= ($bug['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                          <option value="critical" <?= ($bug['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Close Comment (optional)</label>
                        <input type="text" name="edit_close_comment" class="form-control" value="<?= htmlspecialchars($bug['close_comment'] ?? '') ?>">
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                      <button type="submit" name="delete_id" value="<?= $bug['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this bug?')">Delete</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
