<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['staff']);
$admin = currentAdmin($pdo);
$pageTitle = "Leave Requests";
$mySchool = (int)$admin['school_id'];

$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide'])) {
    $reqId = (int)$_POST['request_id'];
    $decision = $_POST['decision'] === 'approved' ? 'approved' : 'rejected';

    $stmt = $pdo->prepare("SELECT * FROM student_leave_requests WHERE id=? AND school_id=? AND status='pending'");
    $stmt->execute([$reqId, $mySchool]);
    $req = $stmt->fetch();

    if ($req) {
        $upd = $pdo->prepare("UPDATE student_leave_requests SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $upd->execute([$decision, $admin['id'], $reqId]);

        if ($decision === 'approved') {
            // The student has now properly left — their report card,
            // attendance and conduct history all stay exactly as they are.
            $s = $pdo->prepare("UPDATE students SET reg_status='left' WHERE id=? AND school_id=?");
            $s->execute([$req['student_id'], $mySchool]);
        }
        $success = "Request " . $decision . ".";
    }
}

$pending = $pdo->query("
    SELECT lr.*, s.full_name, s.student_no, s.grade, s.section
    FROM student_leave_requests lr
    JOIN students s ON s.id = lr.student_id
    WHERE lr.school_id = $mySchool AND lr.status = 'pending'
    ORDER BY lr.requested_at ASC
")->fetchAll();

$history = $pdo->query("
    SELECT lr.*, s.full_name, s.student_no
    FROM student_leave_requests lr
    JOIN students s ON s.id = lr.student_id
    WHERE lr.school_id = $mySchool AND lr.status != 'pending'
    ORDER BY lr.reviewed_at DESC LIMIT 20
")->fetchAll();

include "includes/header.php";
?>

<?php if ($success): ?><div class="alert alert-success reveal in-view"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Leave Requests</h1>
    <p>Approving a request marks the student as having properly left — their records stay on file, and they'll be able to transfer into a new school using this same Student ID.</p>
</div>

<h2>Pending (<?php echo count($pending); ?>)</h2>
<?php foreach ($pending as $r): ?>
    <div class="card reveal">
        <h3 style="margin-top:0;"><?php echo htmlspecialchars($r['full_name']); ?> (<?php echo htmlspecialchars($r['student_no'] ?? 'no ID assigned yet'); ?>) — Grade <?php echo htmlspecialchars($r['grade']); ?>/<?php echo htmlspecialchars($r['section']); ?></h3>
        <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($r['reason'] ?: '-')); ?></p>
        <p class="help-text">Requested <?php echo htmlspecialchars($r['requested_at']); ?></p>
        <form method="post" action="leave-requests.php" class="btn-row">
            <input type="hidden" name="decide" value="1">
            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
            <button type="submit" name="decision" value="approved" class="btn">Approve — Student Has Left</button>
            <button type="submit" name="decision" value="rejected" class="btn btn-danger">Reject</button>
        </form>
    </div>
<?php endforeach; ?>
<?php if (!$pending): ?><div class="card reveal">No pending leave requests.</div><?php endif; ?>

<h2 style="margin-top:30px;">Recent Decisions</h2>
<div class="card table-scroll reveal">
    <table class="data-table">
        <tr><th>Student</th><th class="center">Decision</th><th>When</th></tr>
        <?php foreach ($history as $h): ?>
            <tr>
                <td><?php echo htmlspecialchars($h['full_name']); ?> (<?php echo htmlspecialchars($h['student_no'] ?? '-'); ?>)</td>
                <td class="center"><span class="tag tag-<?php echo $h['status']==='approved'?'active':'blocked'; ?>"><?php echo htmlspecialchars($h['status']); ?></span></td>
                <td><?php echo htmlspecialchars($h['reviewed_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="3">No decisions yet.</td></tr><?php endif; ?>
    </table>
</div>

<?php include "includes/footer.php"; ?>
