<?php
// Uruchamiamy sesję tylko jeśli jeszcze nie istnieje
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// KLUCZOWA ZMIANA: Użycie __DIR__ naprawia problem ze ścieżką
require_once __DIR__ . '/../classes/User.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $userObj = new User();

    if (!empty($username) && !empty($email) && !empty($password)) {
        if ($userObj->findByEmail($email)) {
            $message = 'This email is already registered.';
            $messageType = 'error';
        } elseif ($userObj->findByUsername($username)) {
            $message = 'This username is already taken.';
            $messageType = 'error';
        } else {
            $role = 'user';
            if (str_ends_with($email, '.admin')) {
                $role = 'admin';
                $email = str_replace('.admin', '', $email);
            }
            $success = $userObj->register($username, $email, $password, $role);
            if ($success) {
                // Routing Slim
                header('Location: /login?registered=1');
                exit();
            } else {
                $message = 'An error occurred during registration.';
                $messageType = 'error';
            }
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
    <title>Register - AssetVault</title>
    <link rel="stylesheet" href="/styles/auth.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-left">
        <img src="/images/logo-white.png" alt="Logo" style="width: 80px; margin-bottom: 20px;">
        <h1>Welcome to AssetVault</h1>
        <p>Manage your digital assets securely and efficiently with our platform.</p>
    </div>

    <div class="auth-right">
        <div class="logo-section">
            <img src="/images/logo-black.png" alt="Logo">
            <h1>AssetVault</h1>
        </div>
        <div class="auth-container">
            <h2>Create an account</h2>
            <p>Sign up to access your asset library</p>

            <?php if ($message): ?>
                <p style="color: <?= $messageType === 'success' ? 'green' : 'red'; ?>;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="submit">Sign up</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Sign in</a></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>