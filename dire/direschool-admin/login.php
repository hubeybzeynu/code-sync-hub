<?php
// Staff login (role: admin)
require "includes/db.php";
require "includes/auth.php";

if (staffLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && $admin['role'] !== 'admin') {
            $error = "This isn't an Admin account. Please use the correct portal for your role.";
        } elseif ($admin && password_verify($password, $admin['password_hash'])) {
            if ($admin['is_banned']) {
                $error = "This staff account has been banned. Contact the Super Admin.";
            } elseif ($admin['school_id']) {
                $sc = $pdo->prepare("SELECT status FROM schools WHERE id = ?");
                $sc->execute([$admin['school_id']]);
                $school = $sc->fetch();
                if ($school && $school['status'] === 'blocked') {
                    $error = "Your school has been blocked by the Super Admin. Sign-in is disabled until it is unblocked.";
                } else {
                    $_SESSION['admin_id'] = $admin['id'];
                    header("Location: index.php");
                    exit;
                }
            } else {
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?><!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css"></head><body>
    <div class="login-wrap">
        <div class="login-card">
            <span class="login-icon"></span>
            <h1>Admin Login</h1>
            <p style="text-align:center; color:#666; margin-top:-8px; font-size:14px;">Staff sign-in</p>
            <form method="post" action="login.php">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-full">Sign In</button>
            </form>
        </div>
    </div></body></html>
