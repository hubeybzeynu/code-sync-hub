<?php
// setup-admin.php
// Run this ONCE in your browser after importing database/schema.sql:
//   http://localhost/direschool-superadmin/setup-admin.php
// It creates (or resets) the super admin login below.
// DELETE THIS FILE after you have logged in once - it is not password protected.

require"includes/db.php";

$email = "admin@/direschool.com";
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? ");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    $upd = $pdo->prepare("UPDATE admins SET password_hash = ?, role = 'superadmin' WHERE id = ? ");
    $upd->execute([$hash, $existing['id']]);
    $msg = "Admin account already existed - password has been reset.";
} else {
    $ins = $pdo->prepare("INSERT INTO admins (email, password_hash, full_name, role) VALUES (?, ?, 'Super Admin', 'superadmin')");
    $ins->execute([$email, $hash]);
    $msg = "Super admin account created.";
}
?><!DOCTYPE html><html><head>
    <meta charset="UTF-8">
    <title>Setup Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css"></head><body><div class="login-wrap">
    <div class="login-card">
        <span class="login-icon"></span>
        <h1>Setup Complete</h1>
        <div class="alert alert-success"><?php echo $msg; ?></div>
        <p>Login email: <b><?php echo htmlspecialchars($email); ?></b></p>
        <p>Login password: <b><?php echo htmlspecialchars($password); ?></b></p>
        <p style="color:#a13a2f;"><b>Important:</b> Delete this file (setup-admin.php) now, and change your password after logging in.</p>
        <a class="btn btn-full" href="login.php" style="text-align:center; display:block;">Go to Login</a>
    </div></div></body></html>
