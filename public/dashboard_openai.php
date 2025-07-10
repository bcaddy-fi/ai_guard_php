<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['admin', 'engineer'])) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}
require __DIR__ . '/../app/controllers/db.php';

// Fetch OpenAI log data
$stmt = $pdo->query("SELECT * FROM openai_log ORDER BY id DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OpenAI Usage Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-4">
  <h2 class="mb-4"><i class="fa fa-brain"></i> OpenAI Usage Dashboard</h2>

  <div class="table-responsive">
    <table class="table table-bordered table-hover bg-white shadow-sm">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Model</th>
          <th>Status</th>
          <th>Tokens</th>
          <th>Date</th>
          <th>View</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><?= htmlspecialchars($log['user_id'] ?? 'unknown') ?></td>
          <td><?= htmlspecialchars($log['model_used']) ?></td>
          <td><span class="badge bg-<?= $log['status'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($log['status']) ?></span></td>
          <td><?= $log['token_usage'] ?? '-' ?></td>
          <td><?= $log['created_at'] ?? '-' ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logModal<?= $log['id'] ?>">
              Details
            </button>
          </td>
        </tr>

        <!-- Modal -->
        <div class="modal fade" id="logModal<?= $log['id'] ?>" tabindex="-1" aria-labelledby="logModalLabel<?= $log['id'] ?>" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Log Entry #<?= $log['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <pre><strong>User:</strong> <?= htmlspecialchars($log['user_id']) ?></pre>
                <pre><strong>Model:</strong> <?= htmlspecialchars($log['model_used']) ?></pre>
                <pre><strong>Tokens Used:</strong> <?= $log['token_usage'] ?></pre>
                <pre><strong>Status:</strong> <?= htmlspecialchars($log['status']) ?></pre>
                <pre><strong>Error:</strong> <?= htmlspecialchars($log['error_message']) ?></pre>
                <hr>
                <h6>Prompt</h6>
                <pre class="bg-light p-3"><?= htmlspecialchars($log['prompt']) ?></pre>
                <h6>Response</h6>
                <pre class="bg-light p-3"><?= htmlspecialchars($log['response']) ?></pre>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

</body>
</html>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
