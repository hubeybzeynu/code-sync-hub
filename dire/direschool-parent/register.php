<?php
$pageTitle = "Parent Registration";
require "includes/db.php";

$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM parents WHERE email = ? ");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = "An account with that email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO parents (full_name, email, password_hash) VALUES (?,?,?)");
            $stmt->execute([$fullName, $email, $hash]);
            $success = true;
        }
    }
}

include "includes/header.php";
?>

<h1>Parent Registration</h1>

<?php if ($success): ?>
    <div class="card form-narrow reveal">
        <div class="alert alert-success">Account created! You can now log in and link your child using their Student ID and password.</div>
        <a class="btn btn-full" href="login.php">Go to Login</a>
    </div>
<?php else: ?>
    <div class="card form-narrow reveal">
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" action="register.php">
            <div class="field"><label>Full Name</label><input type="text" name="full_name" required></div>
            <div class="field"><label>Email</label><input type="text" name="email" required></div>
            <div class="field"><label>Password</label><input type="password" name="password" required></div>
            <div class="field"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
            <button type="submit" class="btn btn-full">Register</button>
        </form>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
