<?php
session_start();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['h-captcha-response'])) {
    $captcha = $_POST['h-captcha-response'];
    $secretKey = 'ES_9d085b7e28f042eca0223a35d7929b61'; // Replace with your secret
    $verify = file_get_contents("https://hcaptcha.com/siteverify", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret' => $secretKey,
                'response' => $captcha,
                'remoteip' => $ip
            ])
        ]
    ]));

    $responseData = json_decode($verify, true);
    if (!empty($responseData['success'])) {
        $_SESSION['captcha_passed'] = true;
        header("Location: " . ($_GET['return'] ?? 'dashboard.php'));
        exit;
    } else {
        $error = "CAPTCHA failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Human Verification</title>
  <script src="https://hcaptcha.com/1/api.js" async defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light text-center" style="padding-top: 80px;">
  <div class="container">
    <h2><i class="fa fa-robot"></i> Verify You're Human</h2>
    <p>Suspicious activity was detected from your device. Complete the CAPTCHA to continue.</p>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="h-captcha d-inline-block" data-sitekey="2672933a-e088-47e6-8da2-05f0244adf2a"></div>
      <br><br>
      <button class="btn btn-primary" type="submit"><i class="fa fa-check-circle"></i> Submit</button>
    </form>
  </div>
</body>
</html>
