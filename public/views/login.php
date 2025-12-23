<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../classes/User.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit();
}

$message = '';
$messageType = '';

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $message = 'Registration completed successfully. You can now log in.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $userObj = new User();
        $user = $userObj->login($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: /dashboard');
            exit();
        } else {
            $message = 'Invalid email or password.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - AssetVault</title>
    <link rel="stylesheet" href="/styles/auth.css">
</head>
<body>

<div class="auth-wrapper">

    <div class="auth-left">
        <img src="/images/logo-white.png" alt="AssetVault Logo" style="width: 80px; margin-bottom: 20px;">
        <h1>Welcome to AssetVault</h1>
        <p>Manage your digital assets securely and efficiently with our platform.</p>
    </div>

    <div class="auth-right">
        <div class="logo-section">
            <img src="/images/logo-black.png" alt="Logo">
            <h1>AssetVault</h1>
        </div>

        <div class="auth-container">
            <h2>Welcome back</h2>
            <p>Sign in to access your asset library</p>

            <?php if ($message): ?>
                <p style="color: <?= $messageType === 'success' ? 'green' : 'red'; ?>;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="submit">Sign in</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/register">Sign up</a></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
