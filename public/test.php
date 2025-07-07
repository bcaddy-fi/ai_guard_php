<?php
require '../app/controllers/db.php';
$stmt = $pdo->prepare("INSERT INTO api_log (user, filename, policy_type, prompt, response) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['test_user', 'test.yaml', 'persona', 'sample prompt', 'sample response']);
echo "Inserted";