<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Transfer Requests";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide'])) {
    $reqId = (int)$_POST['request_id'];
    $decision = $_POST['decision'] === 'verified' ? 'verified' : 'rejected';
    $note = trim($_POST['review_note'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM school_transfer_requests WHERE id = ? ");
    $stmt->execute([$reqId]);
    $reqRow = $stmt->fetch();

    if ($reqRow && $reqRow['status'] === 'pending') {
        $upd = $pdo->prepare("UPDATE school_transfer_requests SET status=?, reviewed_by=?, review_note=?, reviewed_at=NOW() WHERE id=? ");
        $upd->execute([$decision, $admin['id'], $note, $reqId]);

        if ($decision === 'verified') {
            // Actually move the student to the new school
            // Moving schools also reactivates the account (it was 'left'
            // while the transfer was pending) — and, since they're joining
            // a new school, their grade/section need reassigning, so drop
            // them to '-' until this school's Staff/Admin sets it properly.
            $moveStmt = $pdo->prepare("UPDATE students SET school_id = ?, reg_status = 'active', section = '-' WHERE id = ?");
            $moveStmt->execute([$reqRow['to_school_id'], $reqRow['student_id']]);
        }
        $success = "Transfer request" . $decision .".";
    }
}

$pending = $pdo->query("
    SELECT t.*, s.full_name, s.student_no, s.grade, s.section,
           fs.name AS from_school, ts.name AS to_school
    FROM school_transfer_requests t
    JOIN students s ON s.id = t.student_id
    JOIN schools fs ON fs.id = t.from_school_id
    JOIN schools ts ON ts.id = t.to_school_id
    WHERE t.status = 'pending'
    ORDER BY t.requested_at ASC")->fetchAll();

// For each pending request, pull the student's report card history (promotion/detention)
$historyByStudent = [];
foreach ($pending as $p) {
    $h = $pdo->prepare("SELECT school_year, grade, section, promotion_status FROM report_cards WHERE student_id = ? ORDER BY school_year DESC");
    $h->execute([$p['student_id']]);
    $historyByStudent[$p['student_id']] = $h->fetchAll();
}

$history = $pdo->query("
    SELECT t.*, s.full_name, s.student_no, fs.name AS from_school, ts.name AS to_school
    FROM school_transfer_requests t
    JOIN students s ON s.id = t.student_id
    JOIN schools fs ON fs.id = t.from_school_id
    JOIN schools ts ON ts.id = t.to_school_id
    WHERE t.status != 'pending'
    ORDER BY t.reviewed_at DESC LIMIT 25")->fetchAll();

include"includes/header.php";
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="alert alert-info">
    Why this matters: a student who was <b>Detained</b> can try to transfer to another school to hide that record and get a fake"Promoted" report card. Check the promotion history below before verifying any transfer.
</div>

<h2>Pending Requests (<?php echo count($pending); ?>)</h2><?php if (!$pending): ?>
    <div class="card reveal">No pending transfer requests.</div><?php endif; ?>

<?php foreach ($pending as $p): ?>
    <div class="card reveal">
        <div class="grid grid-2">
            <div>
                <h3 style="margin-top:0;"><?php echo htmlspecialchars($p['full_name']); ?> (<?php echo htmlspecialchars($p['student_no'] ?? '-'); ?>)</h3>
                <p><strong>From:</strong><?php echo htmlspecialchars($p['from_school']); ?> &rarr; <strong>To:</strong><?php echo htmlspecialchars($p['to_school']); ?></p>
                <p><strong>Current Grade/Section:</strong><?php echo htmlspecialchars($p['grade']); ?> / <?php echo htmlspecialchars($p['section']); ?></p>
                <p><strong>Reason given:</strong><?php echo nl2br(htmlspecialchars($p['reason'] ?: '-')); ?></p>
                <?php if (!empty($p['staff_note'])): ?><p><strong>Receiving school's note:</strong> <?php echo nl2br(htmlspecialchars($p['staff_note'])); ?></p><?php endif; ?>
                <p class="help-text">Requested <?php echo htmlspecialchars($p['requested_at']); ?></p>
            </div>
            <div>
                <h3 style="margin-top:0;">Promotion / Detention History</h3>
                <?php $hist = $historyByStudent[$p['student_id']] ?? []; ?>
                <?php if (!$hist): ?>
                    <p class="help-text">No report card history found for this student.</p>
                <?php else: ?>
                    <table class="data-table">
                        <tr><th>Year</th><th>Grade/Section</th><th>Result</th></tr>
                        <?php foreach ($hist as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['school_year']); ?></td>
                                <td><?php echo htmlspecialchars($h['grade']) .'/' . htmlspecialchars($h['section']); ?></td>
                                <td>
                                    <?php if ($h['promotion_status'] === 'detained'): ?>
                                        <span class="tag tag-detained">Detained</span>
                                    <?php elseif ($h['promotion_status'] === 'promoted'): ?>
                                        <span class="tag tag-promoted">Promoted</span>
                                    <?php else: ?>
                                        <span class="tag tag-waiting">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="transfers.php" style="margin-top:16px;">
            <input type="hidden" name="decide" value="1">
            <input type="hidden" name="request_id" value="<?php echo $p['id']; ?>">
            <div class="field"><label>Review note (optional)</label><input type="text" name="review_note"></div>
            <div class="btn-row">
                <button type="submit" name="decision" value="verified" class="btn"> Verify &amp; Approve Transfer</button>
                <button type="submit" name="decision" value="rejected" class="btn btn-danger"> Reject</button>
            </div>
        </form>
    </div><?php endforeach; ?>

<h2>Recent Decisions</h2><div class="card table-scroll reveal">
    <table class="data-table">
        <tr><th>Student</th><th>From &rarr; To</th><th class="center">Decision</th><th>Note</th></tr>
        <?php foreach ($history as $h): ?>
            <tr>
                <td><?php echo htmlspecialchars($h['full_name']); ?> (<?php echo htmlspecialchars($h['student_no'] ?? '-'); ?>)</td>
                <td><?php echo htmlspecialchars($h['from_school']); ?> &rarr; <?php echo htmlspecialchars($h['to_school']); ?></td>
                <td class="center"><span class="tag tag-<?php echo $h['status']==='verified'? 'promoted':'detained'; ?>"><?php echo htmlspecialchars($h['status']); ?></span></td>
                <td><?php echo htmlspecialchars($h['review_note'] ?: '-'); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="4">No decisions yet.</td></tr><?php endif; ?>
    </table></div>

<?php include"includes/footer.php"; ?>
