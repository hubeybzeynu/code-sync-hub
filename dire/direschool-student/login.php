<?php
$pageTitle = "Student Login";
require "includes/db.php";
require "includes/auth.php";

if (studentLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['student_no'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($studentNo === '' || $password === '') {
        $error = "Please enter your Student ID and Password.";
    } else {
        // Registration is by name at first (no student_no yet until the
        // school assigns one), so also allow logging in with the row's
        // internal id if the school hasn't assigned a Student ID yet —
        // in practice the school assigns student_no during review.
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_no = ? LIMIT 1");
        $stmt->execute([$studentNo]);
        $student = $stmt->fetch();

        if (!$student || !$student['password_hash'] || !password_verify($password, $student['password_hash'])) {
            $error = "Incorrect Student ID or Password.";
        } elseif ($student['reg_status'] === 'pending_review') {
            $error = "Your registration is still waiting for your school to review it.";
        } elseif ($student['reg_status'] === 'pending_payment') {
            $error = "Your registration was approved — please send the registration fee and wait for your school to confirm payment before logging in.";
        } elseif ($student['reg_status'] === 'rejected') {
            $error = "Your registration was not approved. Please contact your school's office.";
        } elseif ($student['reg_status'] === 'banned') {
            $error = "This account has been banned. Reason: " . htmlspecialchars($student['ban_reason'] ?: 'contact your school office.');
        } elseif ($student['reg_status'] === 'left') {
            $error = "You've left your previous school. If you're transferring, use \"Transfer to a New School\" on the register page — once your new school and the Super Admin verify it, you'll be able to log in again.";
        } else {
            $_SESSION['student_id'] = $student['id'];
            header("Location: index.php");
            exit;
        }
    }
}

include "includes/header.php";
?>

<h1>Student Login</h1>

<div class="card form-narrow reveal">
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" action="login.php">
        <div class="field"><label for="student_no">Student ID</label><input type="text" id="student_no" name="student_no" required></div>
        <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
        <button type="submit" class="btn btn-full">Log In</button>
    </form>
    <p class="help-text" style="margin-top:14px;">New here? <a href="register.php">Register</a></p>
</div>

<?php include "includes/footer.php"; ?>
