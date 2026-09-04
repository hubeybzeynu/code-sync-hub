<?php
$pageTitle = "Parent Login";
require "includes/db.php";
require "includes/auth.php";

if (parentLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM parents WHERE email = ? ");
    $stmt->execute([$email]);
    $parent = $stmt->fetch();

    if ($parent && password_verify($password, $parent['password_hash'])) {
        $_SESSION['parent_id'] = $parent['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Incorrect email or password.";
    }
}

include "includes/header.php";
?>

<h1>Parent Login</h1>
<div class="card form-narrow reveal">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" action="login.php">
        <div class="field"><label>Email</label><input type="text" name="email" required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit" class="btn btn-full">Log In</button>
    </form>
    <p class="help-text" style="margin-top:14px;">New here? <a href="register.php">Register</a></p>
</div>

<?php include "includes/footer.php"; ?>
