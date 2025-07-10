<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$role = $_SESSION['role'] ?? 'read';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Management Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 1rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .navbar-brand {
      font-weight: bold;
    }
    textarea {
      font-family: monospace;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php">
        <img src="https://guard-manager.isms-cloud.com/aiguardmanager.png" width="110" height="35">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">

          <?php if (in_array($role, ['engineer', 'admin'])): ?>
            <!-- Agents Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Agents</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="manage_rules.php">Manage Agents</a></li>
                <li><a class="dropdown-item" href="build_rule.php">Agent Wizard</a></li>
              </ul>
            </li>

            <!-- Persona Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Persona</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="manage_personas.php">Manage Personas</a></li>
                <li><a class="dropdown-item" href="persona_builder.php">Persona Wizard</a></li>
                <li><a class="dropdown-item" href="persona_import.php">Persona Import</a></li>
              </ul>
            </li>

            <!-- Guardrail Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Guardrail</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="manage_guardrails.php">Manage Guardrails</a></li>
                <li><a class="dropdown-item" href="guardrail_builder.php">Guardrail Wizard</a></li>
                <li><a class="dropdown-item" href="guardrail_import.php">Guardrail Import</a></li>
              </ul>
            </li>

            <!-- Model Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Model</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="manage_models.php">Manage Models</a></li>
                <li><a class="dropdown-item" href="model_builder.php">Model Wizard</a></li>
              </ul>
            </li>

            <!-- AI Stats -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">AI Stats</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="dashboard_analytics.php">Analytics</a></li>
                <li><a class="dropdown-item" href="dashboard_openai.php">OpenAI API Calls</a></li>
              </ul>
            </li>
          <?php endif; ?>

          <?php if ($role === 'admin'): ?>
            <!-- AI Logs -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">AI Logs</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="audit_log_viewer.php">Log - Rule Tests</a></li>
                <li><a class="dropdown-item" href="audit_log.php">Log - Audit</a></li>
              </ul>
            </li>

            <!-- Admin -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="test_case_admin.php">Admin - Test Cases</a></li>
                <li><a class="dropdown-item" href="run_tests.php">Admin - Run All Tests</a></li>
                <li><a class="dropdown-item" href="user_admin.php">Admin - Users</a></li>
                <li><a class="dropdown-item" href="admin_console.php">Admin - Console</a></li>
                <li><a class="dropdown-item" href="waf_admin.php">Admin - WAF</a></li>
                <li><a class="dropdown-item" href="sso_config.php">Admin - SSO/SAML</a></li>
                <li><a class="dropdown-item" href="siem_setting.php">Admin - SIEM/Logging</a></li>
                <li><a class="dropdown-item" href="bug_tracker.php">Admin - Bug Tracker</a></li>
              </ul>
            </li>
          <?php endif; ?>
        </ul>

        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="help.html" target="_blank" title="Help">
              <i class="fa fa-circle-question"></i> Help
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about.html" target="_blank" title="About AI Guard Manager">
              <i class="fa fa-info-circle"></i> About
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php" title="Logout">
              <i class="fa fa-sign-out-alt"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <?php if (isset($content)) echo $content; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
