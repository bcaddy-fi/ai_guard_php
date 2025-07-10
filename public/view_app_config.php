<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require __DIR__ . '/../app/controllers/db.php';
require_role('admin');

$config = require __DIR__ . '/../app/controllers/app_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Application Config Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h1 class="mb-4">Application Configuration</h1>

    <h3>General</h3>
    <ul>
        <li><strong>Company Name:</strong> <?= htmlspecialchars($config['general']['company_name'] ?? 'N/A') ?></li>
        <li><strong>Logo Path:</strong> <?= htmlspecialchars($config['general']['logo_path'] ?? 'N/A') ?></li>
    </ul>

    <h3>SSO Profiles</h3>
    <ul>
        <?php foreach ($config['sso_profiles'] as $profile => $enabled): ?>
            <li><?= htmlspecialchars($profile) ?>: <?= $enabled ? 'Enabled' : 'Disabled' ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Email Server</h3>
    <ul>
        <li><strong>SMTP Host:</strong> <?= htmlspecialchars($config['email']['smtp_host'] ?? 'N/A') ?></li>
        <li><strong>SMTP Port:</strong> <?= htmlspecialchars($config['email']['smtp_port'] ?? 'N/A') ?></li>
        <li><strong>SMTP User:</strong> <?= htmlspecialchars($config['email']['smtp_user'] ?? 'N/A') ?></li>
        <li><strong>From Name:</strong> <?= htmlspecialchars($config['email']['smtp_from_name'] ?? 'N/A') ?></li>
        <li><strong>Secure:</strong> <?= htmlspecialchars($config['email']['smtp_secure'] ?? 'N/A') ?></li>
    </ul>
</body>
</html>
