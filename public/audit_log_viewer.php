<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';



$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$total = $pdo->query("SELECT COUNT(*) FROM api_log")->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM api_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', 25, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();$logs = $stmt->fetchAll();

ob_start();
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * 25;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<div class="container mt-5">
  <h2>API Audit Log</h2>
  <table class="table table-bordered table-striped mt-3">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Filename</th>
        <th>Policy Type</th>
        <th>Prompt</th>
        <th>Response</th>
        <th>Timestamp</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= htmlspecialchars($log['id']) ?></td>
        <td><?= htmlspecialchars($log['user']) ?></td>
        <td><?= htmlspecialchars($log['filename']) ?></td>
        <td><?= htmlspecialchars($log['policy_type']) ?></td>
        <td><?= nl2br(htmlspecialchars($log['prompt'])) ?></td>
        <td><?= nl2br(htmlspecialchars($log['response'])) ?></td>
        <td><?= htmlspecialchars($log['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
include 'includes/footer.php';
?>
