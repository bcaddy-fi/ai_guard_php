<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
$userRole = $_SESSION['role'] ?? '';

if (!in_array($userRole, ['admin', 'engineer'])) {
    http_response_code(403);
    echo "Access denied. You must be an admin or engineer.";
    exit;
}require __DIR__ . '/../app/controllers/db.php';

require_once __DIR__ . '/../app/helpers/yaml_dirs.php';
$totalCalls = $successCalls = $failCalls = $totalTokens = 0;
$topPersonas = $dailyTokens = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM openai_log");
$totalCalls = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM openai_log WHERE status = 'success'");
$successCalls = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM openai_log WHERE status != 'success'");
$failCalls = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(token_usage) FROM openai_log");
$totalTokens = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT persona_name, COUNT(*) as total 
    FROM openai_log 
    WHERE persona_name IS NOT NULL 
    GROUP BY persona_name 
    ORDER BY total DESC 
    LIMIT 5
");
$topPersonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT DATE(timestamp) as day, SUM(token_usage) as tokens 
    FROM openai_log 
    WHERE timestamp > NOW() - INTERVAL 7 DAY 
    GROUP BY day 
    ORDER BY day
");
$dailyTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
$yamlTypes = ['persona', 'agent', 'guardrail', 'model'];
$fileCounts = [];

foreach ($yamlTypes as $type) {
    $dir = get_yaml_directory($type);
    $files = array_filter(scandir($dir), fn($f) => is_file($dir . $f) && str_ends_with($f, '.yaml'));
    $fileCounts[$type] = count($files);
}
ob_start();
?>

<div class="container mt-5">
  <h2><i class="fa fa-chart-bar"></i> OpenAI Usage Dashboard</h2>

  <div class="row g-4 mt-3">
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6>Total API Calls</h6>
        <h3><?= $totalCalls ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6>Successful Calls</h6>
        <h3 class="text-success"><?= $successCalls ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6>Failed Calls</h6>
        <h3 class="text-danger"><?= $failCalls ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6>Total Tokens Used</h6>
        <h3><?= number_format($totalTokens) ?></h3>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <ul class="list-group mb-4">
    <?php foreach ($topPersonas as $row): ?>
      <li class="list-group-item d-flex justify-content-between">
        <span><?= htmlspecialchars($row['persona_name']) ?></span>
        <span><?= $row['total'] ?> calls</span>
      </li>
    <?php endforeach; ?>
  </ul>
<h5>YAML Files under Management</h5>
<div class="row mt-4">
  <?php foreach ($fileCounts as $type => $count): ?>
    <div class="col-md-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title text-capitalize"><?= htmlspecialchars($type) ?>s</h5>
          <p class="display-6"><?= $count ?></p>
          <p class="text-muted">YAML files in /data/<?= htmlspecialchars($type) ?>/</p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
  <br><h5>Token Usage (Last 7 Days)</h5>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Date</th>
        <th>Tokens Used</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($dailyTokens as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['day']) ?></td>
          <td><?= number_format($row['tokens']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
