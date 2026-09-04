<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['admin', 'staff']);
$admin = currentAdmin($pdo);
$pageTitle = "Registrations";
$schoolId = (int)$admin['school_id'];

$error = "";
$success = "";

// ---- Approve a new registration's details (pending_review -> pending_payment) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_review'])) {
    $studentId = (int)$_POST['student_id'];
    $grade = trim($_POST['grade'] ?? '');
    $section = trim($_POST['section'] ?? '');
    if ($grade === '' || $section === '') {
        $error = "Please assign a Grade and Section before approving.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET grade=?, section=?, reg_status='pending_payment' WHERE id=? AND school_id=? ");
        $stmt->execute([$grade, $section, $studentId, $schoolId]);
        $ins = $pdo->prepare("INSERT INTO payments (student_id, status) VALUES (?, 'pending')");
        $ins->execute([$studentId]);
        $success = "Registration approved — now waiting on payment confirmation.";
    }
}

// ---- Reject a new registration ----
if (isset($_GET['reject'])) {
    $stmt = $pdo->prepare("UPDATE students SET reg_status='rejected' WHERE id=? AND school_id=? ");
    $stmt->execute([(int)$_GET['reject'], $schoolId]);
    header("Location: registrations.php");
    exit;
}

// ---- Confirm payment (pending_payment -> active) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $studentId = (int)$_POST['student_id'];
    $note = trim($_POST['note'] ?? '');
    $find = $pdo->prepare("SELECT id FROM payments WHERE student_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
    $find->execute([$studentId]);
    $pay = $find->fetch();
    if ($pay) {
        $u = $pdo->prepare("UPDATE payments SET status='confirmed', confirmed_by=?, confirmed_at=NOW(), note=? WHERE id=? ");
        $u->execute([$admin['id'], $note, $pay['id']]);
    }
    $s = $pdo->prepare("UPDATE students SET reg_status='active' WHERE id=? AND school_id=? ");
    $s->execute([$studentId, $schoolId]);
    $success = "Payment confirmed — student can now log in.";
}

// ---- Request a transfer for an existing student from another school ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_transfer'])) {
    $studentNo = trim($_POST['transfer_student_no'] ?? '');
    $reason = trim($_POST['transfer_reason'] ?? '');
    $find = $pdo->prepare("SELECT * FROM students WHERE student_no = ? LIMIT 1");
    $find->execute([$studentNo]);
    $found = $find->fetch();
    if (!$found) {
        $error = "No student with that Student ID was found anywhere in the system.";
    } elseif ((int)$found['school_id'] === $schoolId) {
        $error = "That student is already at this school.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM school_transfer_requests WHERE student_id=? AND status='pending'");
        $chk->execute([$found['id']]);
        if ($chk->fetch()) {
            $error = "There is already a pending transfer request for this student.";
        } else {
            $ins = $pdo->prepare("INSERT INTO school_transfer_requests (student_id, from_school_id, to_school_id, reason, requested_by) VALUES (?,?,?,?,?)");
            $ins->execute([$found['id'], $found['school_id'], $schoolId, $reason, $admin['id']]);
            $success = "Transfer request submitted. The Super Admin must verify this student's promotion/detention history before the transfer completes — because their profile already exists in the system, you won't need to re-enter their personal details once it's approved, only confirm grade/section and payment.";
        }
    }
}

$newRegistrations = $pdo->query("SELECT * FROM students WHERE school_id=$schoolId AND reg_status='pending_review' ORDER BY created_at")->fetchAll();
$pendingPayment = $pdo->query("SELECT * FROM students WHERE school_id=$schoolId AND reg_status='pending_payment' ORDER BY created_at")->fetchAll();
$incomingTransfers = $pdo->query("
    SELECT t.*, s.full_name, s.student_no, fs.name AS from_school
    FROM school_transfer_requests t
    JOIN students s ON s.id = t.student_id
    JOIN schools fs ON fs.id = t.from_school_id
    WHERE t.to_school_id = $schoolId AND t.status = 'pending'
    ORDER BY t.requested_at DESC")->fetchAll();
$sections = $pdo->query("SELECT DISTINCT grade, section FROM sections WHERE school_id=$schoolId ORDER BY grade, section")->fetchAll();

include"includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<h2>New Registrations Awaiting Review (<?php echo count($newRegistrations); ?>)</h2><?php foreach ($newRegistrations as $s): ?>
    <div class="card reveal">
        <div class="grid grid-2">
            <div>
                <h3 style="margin-top:0;"><?php echo htmlspecialchars($s['full_name']); ?></h3>
                <p>English name: <?php echo htmlspecialchars($s['english_name'] ?: '-'); ?> &middot; Gender: <?php echo htmlspecialchars($s['gender'] ?: '-'); ?> &middot; Age: <?php echo htmlspecialchars($s['age'] ?: '-'); ?></p>
                <p>Kebele: <?php echo htmlspecialchars($s['kebele'] ?: '-'); ?> &middot; House No.: <?php echo htmlspecialchars($s['house_no'] ?: '-'); ?></p>
                <p class="help-text">Registered <?php echo htmlspecialchars($s['created_at']); ?></p>
            </div>
            <form method="post" action="registrations.php">
                <input type="hidden" name="approve_review" value="1">
                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                <div class="grid grid-2">
                    <div class="field"><label>Grade</label><input type="text" name="grade" required value="<?php echo htmlspecialchars($s['grade']); ?>"></div>
                    <div class="field"><label>Section</label><input type="text" name="section" required value="<?php echo htmlspecialchars($s['section']); ?>"></div>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn">Approve &rarr; Ask for Payment</button>
                    <a class="btn btn-danger confirm-delete" href="registrations.php?reject=<?php echo $s['id']; ?>">Reject</a>
                </div>
            </form>
        </div>
    </div><?php endforeach; ?><?php if (!$newRegistrations): ?><div class="card reveal">No new registrations waiting.</div><?php endif; ?>

<h2>Waiting on Payment (<?php echo count($pendingPayment); ?>)</h2><?php foreach ($pendingPayment as $s): ?>
    <div class="card reveal">
        <div class="grid grid-2">
            <div>
                <h3 style="margin-top:0;"><?php echo htmlspecialchars($s['full_name']); ?> — Grade <?php echo htmlspecialchars($s['grade']); ?>/<?php echo htmlspecialchars($s['section']); ?></h3>
                <p class="help-text">Tell the family to send the registration fee, then confirm it here once received.</p>
            </div>
            <form method="post" action="registrations.php">
                <input type="hidden" name="confirm_payment" value="1">
                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                <div class="field"><label>Note (optional)</label><input type="text" name="note" placeholder="e.g. paid in cash at office"></div>
                <button type="submit" class="btn"> Confirm Payment Received &amp; Activate Login</button>
            </form>
        </div>
    </div><?php endforeach; ?><?php if (!$pendingPayment): ?><div class="card reveal">No one waiting on payment.</div><?php endif; ?>

<h2>Incoming Transfers Awaiting Super Admin Verification (<?php echo count($incomingTransfers); ?>)</h2><div class="card reveal">
    <?php if (!$incomingTransfers): ?>
        <p>None right now.</p>
    <?php else: ?>
        <table class="data-table">
            <tr><th>Student</th><th>From School</th><th>Reason</th><th class="center">Status</th></tr>
            <?php foreach ($incomingTransfers as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['full_name']); ?> (<?php echo htmlspecialchars($t['student_no'] ?? '-'); ?>)</td>
                    <td><?php echo htmlspecialchars($t['from_school']); ?></td>
                    <td><?php echo htmlspecialchars($t['reason'] ?: '-'); ?></td>
                    <td class="center"><span class="tag tag-pending">Pending Super Admin</span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?></div>

<h2>Request a Transfer (Student Already in the System)</h2><div class="card form-narrow reveal">
    <p class="help-text">If a student says they're coming from another school already using this system, don't re-collect their full details — just submit this and wait for Super Admin verification. This is how we stop a detained student from quietly re-registering as new at a different school.</p>
    <form method="post" action="registrations.php">
        <input type="hidden" name="request_transfer" value="1">
        <div class="field"><label>Student ID (from their old school)</label><input type="text" name="transfer_student_no" required></div>
        <div class="field"><label>Reason for transfer</label><input type="text" name="transfer_reason" required></div>
        <button type="submit" class="btn btn-full">Submit Transfer Request</button>
    </form></div>

<?php include"includes/footer.php"; ?>
