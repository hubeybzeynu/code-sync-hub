<?php
$pageTitle = "Settings";
require "includes/db.php";
require "includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_privacy'])) {
    $hideAge = isset($_POST['hide_age']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE students SET hide_age=? WHERE id=? ");
    $stmt->execute([$hideAge, $student['id']]);
    $student['hide_age'] = $hideAge;
    $success = "Privacy setting saved.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($current, $student['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 4) {
        $error = "New password should be at least 4 characters.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE students SET password_hash=? WHERE id=? ");
        $stmt->execute([$hash, $student['id']]);
        $success = "Password changed.";
    }
}

include "includes/header.php";
?>

<h1>Settings</h1>
<p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Settings</p>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card form-narrow reveal">
    <h2>Privacy</h2>
    <form method="post" action="settings.php">
        <input type="hidden" name="save_privacy" value="1">
        <label style="display:flex; align-items:center; gap:8px; font-weight:normal;">
            <input type="checkbox" name="hide_age" style="width:auto;" <?php echo $student['hide_age'] ? 'checked' : ''; ?>>
            Hide my age from classmates
        </label>
        <button type="submit" class="btn btn-full" style="margin-top:16px;">Save</button>
    </form>
</div>

<div class="card form-narrow reveal">
    <h2>Change Password</h2>
    <form method="post" action="settings.php">
        <input type="hidden" name="change_password" value="1">
        <div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="field"><label>New Password</label><input type="password" name="new_password" required></div>
        <button type="submit" class="btn btn-full">Change Password</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>
