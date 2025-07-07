<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');


$success = '';
$error = '';

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// FILE audit log
$totalFile = $pdo->query("SELECT COUNT(*) FROM file_audit_log")->fetchColumn();
$pagesFile = ceil($totalFile / $perPage);
$stmt = $pdo->prepare("SELECT * FROM file_audit_log ORDER BY edited_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$fileLogs = $stmt->fetchAll();

// USER audit log
$totalUser = $pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$pagesUser = ceil($totalUser / $perPage);
$stmt = $pdo->prepare("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$userLogs = $stmt->fetchAll();

ob_start();
?>

<!-- File Audit Log -->
<div class="card p-4 mb-5">
  <h4 class="mb-3">File Audit Log</h4>
  <table class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>File</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($fileLogs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><?= htmlspecialchars($log['username']) ?></td>
          <td><?= htmlspecialchars($log['filename']) ?></td>
          <td><?= $log['edited_at'] ?></td>
          <td>
            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewFileModal<?= $log['id'] ?>">View</button>
          </td>
        </tr>

        <!-- Modal for file content -->
        <div class="modal fade" id="viewFileModal<?= $log['id'] ?>" tabindex="-1" aria-labelledby="fileModalLabel<?= $log['id'] ?>" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="fileModalLabel<?= $log['id'] ?>">File Audit #<?= $log['id'] ?> - <?= htmlspecialchars($log['filename']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <pre><?= htmlspecialchars($log['file_content']) ?></pre>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination for file logs -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $pagesFile; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<!-- User Audit Log -->
<div class="card p-4 mb-4">
  <h4 class="mb-3">User Audit Log</h4>
  <table class="table table-sm table-bordered">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Action</th>
        <th>Table</th>
        <th>Target ID</th>
        <th>Details</th>
        <th>Timestamp</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($userLogs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><?= htmlspecialchars($log['username']) ?></td>
          <td><?= htmlspecialchars($log['action']) ?></td>
          <td><?= htmlspecialchars($log['target_table']) ?></td>
          <td><?= htmlspecialchars($log['target_id']) ?></td>
          <td><pre class="mb-0" style="white-space: pre-wrap; font-size: 0.8rem;"><?= htmlspecialchars($log['details']) ?></pre></td>
          <td><?= $log['timestamp'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination for user logs -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $pagesUser; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
