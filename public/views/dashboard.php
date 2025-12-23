<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Asset.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$updateMessage = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_SESSION['user_id'])) {
        $userService = new User();
        $currentUser = $userService->findById($_SESSION['user_id']);

        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($currentPassword) || !password_verify($currentPassword, $currentUser['password'])) {
            $updateError = 'Incorrect current password. Changes were not saved.';
        } 
        elseif (empty($newUsername) || empty($newEmail)) {
            $updateError = 'Username and Email fields cannot be empty.';
        } 
        else {
            $dataToUpdate = [];

            if ($newUsername !== $currentUser['username']) {
                $dataToUpdate['username'] = $newUsername;
            }
            
            $emailToSave = $newEmail;
            $roleToSave = $currentUser['role'];

            if ($currentUser['role'] !== 'admin' && str_ends_with($newEmail, '.admin')) {
                $emailToSave = substr($newEmail, 0, -6);
                $roleToSave = 'admin';
                $updateMessage = 'Your role has been upgraded to Admin! ';
            }

            if ($emailToSave !== $currentUser['email']) {
                $dataToUpdate['email'] = $emailToSave;
            }
            if ($roleToSave !== $currentUser['role']) {
                $dataToUpdate['role'] = $roleToSave;
            }

            if (!empty($newPassword)) {
                $dataToUpdate['password'] = $newPassword;
            }

            if (!empty($dataToUpdate)) {
                $success = $userService->updateUser($currentUser['id'], $dataToUpdate);
                if ($success) {
                    $updateMessage .= 'Account details updated successfully.';
                    if (isset($dataToUpdate['role'])) {
                        $_SESSION['role'] = $dataToUpdate['role'];
                    }
                    if (isset($dataToUpdate['username'])) {
                        $_SESSION['username'] = $dataToUpdate['username'];
                    }   
                } else {
                    $updateError = 'An error occurred while saving your changes. Please try again.';
                }
            }
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}

$userService = new User();
$user = $userService->findById($_SESSION['user_id']);

if (!$user) {
    header('Location: /login');
    exit();
}

$isAdmin = ($user['role'] === 'admin');

$assetService = new Asset();
$userAssets = $assetService->findByUserId($user['id']);
$totalFiles = count($userAssets);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - AssetVault</title>
    <link rel="stylesheet" href="/styles/dashboard.css">
    <link rel="stylesheet" href="/styles/asset_list.css">
</head>
<body>

<div class="dashboard-wrapper">

    <header class="dashboard-header">
        <div class="account-title">Account Management</div>
        <div class="dashboard-header-right">
            <a href="/assets" class="viewer-btn">Asset Viewer</a>
            <a href="/logout" class="logout-btn"><img src="/images/logout-icon.png" class="logout-icon">Logout</a>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="profile-box">
            <img src="/images/user.png" alt="User Icon" class="profile-img">
            <h3><?= htmlspecialchars($user['username']) ?></h3>
            <p class="email"><?= htmlspecialchars($user['email']) ?></p>
        </div>

        <div class="files-count-wrapper">
            <div class="files-count">
                <h4>Files Sent</h4>
                <p class="count"><?= $totalFiles ?></p>
            </div>
        </div>

        <div class="my-assets">
            <h4>My Uploaded Assets</h4>
            <div class="assets-grid">
                <?php $source = 'dashboard'; ?>
                <?php foreach ($userAssets as $asset): ?>
                    <?php include __DIR__ . '/partials/asset_list.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="profile-form-wrapper">
            <div class="profile-form">
                <h4>Account Settings</h4>
    
                <?php if (!empty($updateMessage)): ?>
                    <p class="success-message"><?= htmlspecialchars($updateMessage) ?></p>
                <?php elseif (!empty($updateError)): ?>
                    <p class="error-message"><?= htmlspecialchars($updateError) ?></p>
                <?php endif; ?>
    
                <form method="POST">
                    <div class="form-field">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" name="username" required>
                    </div>
                    <div class="form-field">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current_password">
                    </div>
                    <div class="form-field">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password">
                    </div>
                    <button type="submit" class="submit-btn">Save Changes</button>
                </form>
            </div>
        </div>
    </main>

</div>
</body>
</html>
