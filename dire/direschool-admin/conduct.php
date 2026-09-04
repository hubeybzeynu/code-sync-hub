<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['admin', 'subadmin', 'teacher']);
$admin = currentAdmin($pdo);
$pageTitle = "Conduct & Attendance";
$schoolId = (int)$admin['school_id'];

$error = "";
$success = "";
$SCHOOL_YEAR = date('Y'); // simple default; a real build would let staff pick the active year

// ---- Record a conduct entry (note / warning / signature) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_conduct'])) {
    $studentId = (int)$_POST['student_id'];
    $type = in_array($_POST['type'] ?? '', ['note', 'warning', 'signature']) ? $_POST['type'] : 'note';
    $note = trim($_POST['note'] ?? '');
    $stmt = $pdo->prepare("INSERT INTO student_conduct (student_id, school_id, school_year, type, note, recorded_by) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$studentId, $schoolId, $SCHOOL_YEAR, $type, $note, $admin['id']]);

    // Count signatures this school year — 3 or more forces a family meeting.
    $c = $pdo->prepare("SELECT COUNT(*) n FROM student_conduct WHERE student_id=? AND school_year=? AND type='signature'");
    $c->execute([$studentId, $SCHOOL_YEAR]);
    $sigCount = $c->fetch()['n'];
    if ($type === 'signature' && $sigCount >= 3) {
        $success = "Signature recorded. This student has reached $sigCount signatures this year — the family MUST be called in.";
    } else {
        $success = "Conduct entry recorded.";
    }
}

// ---- Record daily attendance for a student ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_student_attendance'])) {
    $studentId = (int)$_POST['student_id'];
    $status = $_POST['status'] === 'absent' ? 'absent' : 'present';
    $stmt = $pdo->prepare("
        INSERT INTO student_attendance (student_id, school_id, school_year, quarter, attend_date, status, recorded_by)
        VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_by = VALUES(recorded_by)
    ");
    $quarter = (string) (intdiv(date('n') - 1, 3) % 4 + 1);
    $stmt->execute([$studentId, $schoolId, $SCHOOL_YEAR, $quarter, $status, $admin['id']]);
    $success = "Attendance recorded for today.";
}

// ---- Ban a student (admin/subadmin only) + report to Super Admin ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_student']) && in_array($admin['role'], ['admin', 'subadmin'], true)) {
    $studentId = (int)$_POST['student_id'];
    $reason = trim($_POST['ban_reason'] ?? '');
    if ($reason === '') {
        $error = "A reason is required to ban a student.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET reg_status='banned', ban_reason=?, banned_at=NOW() WHERE id=? AND school_id=? ");
        $stmt->execute([$reason, $studentId, $schoolId]);
        $ins = $pdo->prepare("INSERT INTO student_ban_reports (student_id, school_id, reason, banned_by) VALUES (?,?,?,?)");
        $ins->execute([$studentId, $schoolId, $reason, $admin['id']]);
        $success = "Student banned and reported to the Super Admin.";
    }
}

$students = $pdo->query("SELECT * FROM students WHERE school_id=$schoolId AND reg_status='active' ORDER BY grade, section, full_name")->fetchAll();

// Signature counts per student (for the warning badge in the list)
$sigCounts = [];
if ($students) {
    $ids = implode(', ', array_map('intval', array_column($students, 'id')));
    $rows = $pdo->query("SELECT student_id, COUNT(*) n FROM student_conduct WHERE type='signature' AND school_year='$SCHOOL_YEAR' AND student_id IN ($ids) GROUP BY student_id")->fetchAll();
    foreach ($rows as $r) $sigCounts[$r['student_id']] = $r['n'];
}

include"includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card table-scroll reveal">
    <h2>Students</h2>
    <table class="data-table">
        <tr><th>Name</th><th class="center">Grade/Section</th><th class="center">Signatures (<?php echo $SCHOOL_YEAR; ?>)</th><th>Record Conduct</th><th>Attendance Today</th><th class="center">Actions</th></tr>
        <?php foreach ($students as $s): ?>
            <?php $sig = $sigCounts[$s['id']] ?? 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                <td class="center"><?php echo htmlspecialchars($s['grade']) .'/' . htmlspecialchars($s['section']); ?></td>
                <td class="center">
                    <span class="tag tag-<?php echo $sig >= 3 ? 'detained' : ($sig > 0 ? 'waiting' : 'active'); ?>"><?php echo $sig; ?></span>
                    <?php if ($sig >= 3): ?><br><span class="help-text" style="color:#A5222A;">Family meeting required</span><?php endif; ?>
                </td>
                <td>
                    <form method="post" action="conduct.php" class="btn-row">
                        <input type="hidden" name="add_conduct" value="1">
                        <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                        <select name="type" style="width:auto; margin:0;">
                            <option value="note">Note</option>
                            <option value="warning">Warning</option>
                            <option value="signature">Signature</option>
                        </select>
                        <input type="text" name="note" placeholder="detail" style="width:160px; margin:0;">
                        <button type="submit" class="btn btn-sm">Save</button>
                    </form>
                </td>
                <td>
                    <form method="post" action="conduct.php" class="btn-row">
                        <input type="hidden" name="mark_student_attendance" value="1">
                        <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="status" value="present" class="btn btn-sm">Present</button>
                        <button type="submit" name="status" value="absent" class="btn btn-sm btn-outline">Absent</button>
                    </form>
                </td>
                <td class="center">
                    <?php if (in_array($admin['role'], ['admin', 'subadmin'], true)): ?>
                        <form method="post" action="conduct.php" onsubmit="return confirm('Ban this student? This must then be reported to the Super Admin.');">
                            <input type="hidden" name="ban_student" value="1">
                            <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                            <input type="text" name="ban_reason" placeholder="ban reason" required style="width:120px; display:inline-block; margin:0 4px 0 0;">
                            <button type="submit" class="btn btn-sm btn-danger">Ban</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="6">No active students found.</td></tr><?php endif; ?>
    </table></div>

<?php include"includes/footer.php"; ?>
