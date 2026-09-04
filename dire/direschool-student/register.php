<?php
$pageTitle = "Register";
require "includes/db.php";

$error = "";
$success = false;
$transferSuccess = false;
$mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'new');

// ---- Path 1: brand-new student ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'new') {
    $schoolId = (int)($_POST['school_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $englishName = trim($_POST['english_name'] ?? '');
    $gender = $_POST['gender'] ?: null;
    $age = $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $grade = trim($_POST['grade'] ?? '');
    $kebele = trim($_POST['kebele'] ?? '');
    $houseNo = trim($_POST['house_no'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (!$schoolId || $fullName === '' || $grade === '' || $password === '') {
        $error = "School, Full Name, Grade and a Password are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 4) {
        $error = "Password should be at least 4 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO students (school_id, full_name, english_name, gender, age, grade, section, kebele, house_no, password_hash, reg_status)
            VALUES (?,?,?,?,?,?, '-', ?, ?, ?, 'pending_review')
        ");
        $stmt->execute([$schoolId, $fullName, $englishName, $gender, $age, $grade, $kebele, $houseNo, $hash]);
        $success = true;
    }
}

// ---- Path 2: transferring with an existing Student ID ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'transfer') {
    $studentNo = trim($_POST['student_no'] ?? '');
    $toSchoolId = (int)($_POST['to_school_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    $find = $pdo->prepare("SELECT * FROM students WHERE student_no = ? LIMIT 1");
    $find->execute([$studentNo]);
    $found = $find->fetch();

    if (!$studentNo || !$toSchoolId || $reason === '') {
        $error = "Student ID, the school you're joining, and a reason are all required.";
    } elseif (!$found) {
        $error = "No student with that Student ID was found. Every Student ID is unique system-wide — double-check it with your old school.";
    } elseif ($found['reg_status'] !== 'left') {
        $error = "Your old school hasn't approved you leaving yet. Ask their Staff to approve your \"Leave School\" request first.";
    } elseif ((int)$found['school_id'] === $toSchoolId) {
        $error = "That's already your school on record.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM school_transfer_requests WHERE student_id=? AND status='pending'");
        $chk->execute([$found['id']]);
        if ($chk->fetch()) {
            $error = "There is already a pending transfer request for this Student ID.";
        } else {
            $ins = $pdo->prepare("INSERT INTO school_transfer_requests (student_id, from_school_id, to_school_id, reason) VALUES (?,?,?,?)");
            $ins->execute([$found['id'], $found['school_id'], $toSchoolId, $reason]);
            $transferSuccess = true;
        }
    }
}

$schools = $pdo->query("SELECT * FROM schools WHERE status='active' ORDER BY name")->fetchAll();

include "includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9 12 4l10 5-10 5L2 9Z"/><path d="M6 11.5V17c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-5.5"/><path d="M22 9v6.5"/></svg></div>
    <h1>Student Registration</h1>
    <p>New to the system, or transferring from a school that already uses it? Pick the option that fits.</p>
</div>

<?php if ($success): ?>
    <div class="card form-narrow reveal">
        <div class="alert alert-success">
            Registration submitted! Your school will review your details, assign your section, and let you know how to pay
            the registration fee. Once they confirm payment, you'll be able to log in.
        </div>
        <a class="btn btn-full" href="login.php">Go to Login</a>
    </div>
<?php elseif ($transferSuccess): ?>
    <div class="card form-narrow reveal">
        <div class="alert alert-success">
            Transfer request submitted! Your new school's Staff will check your attendance, conduct and report card
            history, and the Super Admin gives final verification before you're moved over. Keep using your existing
            password — you'll be able to log in again once it's approved.
        </div>
        <a class="btn btn-full" href="login.php">Go to Login</a>
    </div>
<?php else: ?>

    <div class="btn-row" style="justify-content:center; margin-bottom:24px;">
        <a class="btn <?php echo $mode==='new' ? '' : 'btn-outline'; ?>" href="register.php?mode=new">New Student</a>
        <a class="btn <?php echo $mode==='transfer' ? '' : 'btn-outline'; ?>" href="register.php?mode=transfer">Transfer — I Have a Student ID</a>
    </div>

    <?php if ($mode === 'transfer'): ?>
        <div class="card form-narrow reveal">
            <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <p class="help-text" style="margin-top:0;">You must already have an approved "Leave School" request from your old school before this will work.</p>
            <form method="post" action="register.php">
                <input type="hidden" name="mode" value="transfer">
                <div class="field"><label>Your Existing Student ID</label><input type="text" name="student_no" required value="<?php echo htmlspecialchars($_POST['student_no'] ?? ''); ?>"></div>
                <div class="field">
                    <label>School You're Joining</label>
                    <select name="to_school_id" required>
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Reason for Transfer</label><textarea name="reason" required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea></div>
                <button type="submit" class="btn btn-full">Submit Transfer Request</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card form-narrow reveal">
            <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post" action="register.php">
                <input type="hidden" name="mode" value="new">
                <div class="field">
                    <label for="school_id">School</label>
                    <select id="school_id" name="school_id" required>
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" required></div>
                <div class="field"><label for="english_name">English Name (optional)</label><input type="text" id="english_name" name="english_name"></div>
                <div class="grid grid-2">
                    <div class="field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">--</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                        </select>
                    </div>
                    <div class="field"><label for="age">Age</label><input type="number" id="age" name="age"></div>
                </div>
                <div class="field"><label for="grade">Grade you're joining</label><input type="text" id="grade" name="grade" required placeholder="e.g. 9"></div>
                <div class="grid grid-2">
                    <div class="field"><label for="kebele">Kebele</label><input type="text" id="kebele" name="kebele"></div>
                    <div class="field"><label for="house_no">House No.</label><input type="text" id="house_no" name="house_no"></div>
                </div>
                <div class="grid grid-2">
                    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
                    <div class="field"><label for="confirm_password">Confirm Password</label><input type="password" id="confirm_password" name="confirm_password" required></div>
                </div>
                <button type="submit" class="btn btn-full">Submit Registration</button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
