<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

// Query counts
$counts = $pdo->query("SELECT 'guardrails' AS type, COUNT(*) AS count FROM guardrails
  UNION ALL SELECT 'personas', COUNT(*) FROM personas
  UNION ALL SELECT 'models', COUNT(*) FROM models")
  ->fetchAll(PDO::FETCH_KEY_PAIR);

// Guardrail edits over last 30 days
$editData = $pdo->query("SELECT DATE(edited_at) as date, COUNT(*) as count
  FROM file_audit_log
  WHERE filename LIKE 'guardrails/%' AND edited_at >= CURDATE() - INTERVAL 30 DAY
  GROUP BY DATE(edited_at)")
  ->fetchAll(PDO::FETCH_ASSOC);

$editLabels = array_column($editData, 'date');
$editCounts = array_column($editData, 'count');

// Bug status breakdown
$bugData = $pdo->query("SELECT status, COUNT(*) as count FROM bug_tracker GROUP BY status")
  ->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Analytics Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<?php include 'layout.php'; ?>
<div class="container py-4">
  <h2 class="mb-4">Analytics Dashboard</h2>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-bg-primary mb-3">
        <div class="card-body">
          <h5 class="card-title">Guardrails</h5>
          <p class="card-text fs-3"><?= $counts['guardrails'] ?? 0 ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-success mb-3">
        <div class="card-body">
          <h5 class="card-title">Personas</h5>
          <p class="card-text fs-3"><?= $counts['personas'] ?? 0 ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-warning mb-3">
        <div class="card-body">
          <h5 class="card-title">Models</h5>
          <p class="card-text fs-3"><?= $counts['models'] ?? 0 ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">Guardrail Edits (30 days)</div>
        <div class="card-body">
          <canvas id="editChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">Bug Reports by Status</div>
        <div class="card-body">
          <canvas id="bugChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const editChart = new Chart(document.getElementById('editChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($editLabels) ?>,
    datasets: [{
      label: 'Edits',
      data: <?= json_encode($editCounts) ?>,
      borderColor: 'rgb(54, 162, 235)',
      backgroundColor: 'rgba(54, 162, 235, 0.2)',
      fill: true
    }]
  }
});

const bugChart = new Chart(document.getElementById('bugChart'), {
  type: 'pie',
  data: {
    labels: <?= json_encode(array_keys($bugData)) ?>,
    datasets: [{
      label: 'Bugs',
      data: <?= json_encode(array_values($bugData)) ?>,
      backgroundColor: ['#f00', '#ffa500', '#28a745']
    }]
  }
});
</script>
</body>
</html>
