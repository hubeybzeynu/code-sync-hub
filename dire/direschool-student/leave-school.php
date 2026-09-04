<?php
$pageTitle = "Leave School";
require "includes/db.php";
require "includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$error = "";
$success = "";

$existing = $pdo->prepare("SELECT * FROM student_leave_requests WHERE student_id=? ORDER BY requested_at DESC LIMIT 1");
$existing->execute([$student['id']]);
$existing = $existing->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $reason = trim($_POST['reason'] ?? '');
    if ($student['reg_status'] !== 'active') {
        $error = "Only an active student can request to leave.";
    } elseif ($existing && $existing['status'] === 'pending') {
        $error = "You already have a pending leave request.";
    } elseif ($reason === '') {
        $error = "Please give a reason for leaving.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO student_leave_requests (student_id, school_id, reason) VALUES (?,?,?)");
        $stmt->execute([$student['id'], $student['school_id'], $reason]);
        $success = "Your leave request has been sent to your school's Staff for approval.";
        $existing = $pdo->prepare("SELECT * FROM student_leave_requests WHERE student_id=? ORDER BY requested_at DESC LIMIT 1");
        $existing->execute([$student['id']]);
        $existing = $existing->fetch();
    }
}

include "includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div>
    <h1>Leave School</h1>
    <p>Moving to another school? Your current school must approve your leave request first — your report card and attendance stay on record either way.</p>
</div>
<p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Leave School</p>

<?php if ($error): ?><div class="alert alert-error reveal in-view"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success reveal in-view"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($existing): ?>
    <div class="card reveal">
        <h2>Your Latest Request</h2>
        <p><strong>Status:</strong>
            <?php if ($existing['status'] === 'pending'): ?><span class="tag tag-pending">Waiting for Staff approval</span>
            <?php elseif ($existing['status'] === 'approved'): ?><span class="tag tag-active">Approved — you have left this school</span>
            <?php else: ?><span class="tag tag-blocked">Rejected</span><?php endif; ?>
        </p>
        <p><strong>Reason given:</strong> <?php echo htmlspecialchars($existing['reason'] ?: '-'); ?></p>
        <p class="help-text">Requested <?php echo htmlspecialchars($existing['requested_at']); ?></p>
        <?php if ($existing['status'] === 'approved'): ?>
            <p>You can now go to your new school and provide this Student ID to start a transfer: <strong><?php echo htmlspecialchars($student['student_no'] ?: 'ask your old school to assign one'); ?></strong></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$existing || $existing['status'] === 'rejected'): ?>
    <div class="card form-narrow reveal">
        <h2>Request to Leave</h2>
        <form method="post" action="leave-school.php">
            <input type="hidden" name="submit_leave" value="1">
            <div class="field"><label>Reason</label><textarea name="reason" required placeholder="e.g. moving to a school closer to home"></textarea></div>
            <button type="submit" class="btn btn-full">Send Request to Staff</button>
        </form>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
