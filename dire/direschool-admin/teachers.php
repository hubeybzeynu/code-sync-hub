<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Teachers";
$schoolId = (int)$admin['school_id'];

$error = "";
$success = "";

// ---- Add / edit teacher ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_teacher'])) {
    $id = (int)($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $regNo = trim($_POST['registration_no'] ?? '');
    $grade = trim($_POST['grade'] ?? '') ?: null;
    $section = trim($_POST['section'] ?? '') ?: null;
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $fullName === '' || $regNo === '') {
        $error = "Email, full name and registration number are required.";
    } else {
        if ($id) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, registration_no=?, grade=?, section=?, password_hash=? WHERE id=? AND school_id=? AND role='teacher'");
                $stmt->execute([$email, $fullName, $regNo, $grade, $section, $hash, $id, $schoolId]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, registration_no=?, grade=?, section=? WHERE id=? AND school_id=? AND role='teacher'");
                $stmt->execute([$email, $fullName, $regNo, $grade, $section, $id, $schoolId]);
            }
            $success = "Teacher updated.";
        } else {
            if ($password === '') {
                $error = "Password is required for a new teacher account.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (email, full_name, role, school_id, grade, section, registration_no, password_hash) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$email, $fullName, 'teacher', $schoolId, $grade, $section, $regNo, $hash]);
                $success = "Teacher added.";
            }
        }
    }
}

// ---- Mark today's attendance ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $teacherId = (int)$_POST['teacher_id'];
    $status = $_POST['status'] === 'absent' ? 'absent' : 'present';
    $stmt = $pdo->prepare("
        INSERT INTO staff_attendance (staff_id, school_id, attend_date, status, recorded_by)
        VALUES (?, ?, CURDATE(), ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_by = VALUES(recorded_by)
    ");
    $stmt->execute([$teacherId, $schoolId, $status, $admin['id']]);
    $success = "Attendance recorded for today.";
}

// ---- Ban / unban ----
if (isset($_GET['toggle_ban'])) {
    $stmt = $pdo->prepare("UPDATE admins SET is_banned = 1 - is_banned WHERE id=? AND school_id=? AND role='teacher'");
    $stmt->execute([(int)$_GET['toggle_ban'], $schoolId]);
    header("Location: teachers.php");
    exit;
}
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id=? AND school_id=? AND role='teacher'");
    $stmt->execute([(int)$_GET['delete'], $schoolId]);
    header("Location: teachers.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editRow = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=? AND school_id=? AND role='teacher'");
    $stmt->execute([$editId, $schoolId]);
    $editRow = $stmt->fetch();
}

$teachers = $pdo->query("
    SELECT a.*,
        (SELECT status FROM staff_attendance sa WHERE sa.staff_id = a.id AND sa.attend_date = CURDATE()) AS today_status
    FROM admins a WHERE a.role='teacher' AND a.school_id = $schoolId ORDER BY a.full_name
")->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($editId !== null): ?>
    <div class="card reveal">
        <h2><?php echo $editRow ? "Edit" : "Add"; ?> Teacher</h2>
        <form method="post" action="teachers.php">
            <input type="hidden" name="save_teacher" value="1">
            <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">
            <div class="grid grid-2">
                <div class="field"><label>Email</label><input type="email" name="email" required value="<?php echo htmlspecialchars($editRow['email'] ?? ''); ?>"></div>
                <div class="field"><label>Full Name</label><input type="text" name="full_name" required value="<?php echo htmlspecialchars($editRow['full_name'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-3">
                <div class="field"><label>Registration Number</label><input type="text" name="registration_no" required value="<?php echo htmlspecialchars($editRow['registration_no'] ?? ''); ?>"></div>
                <div class="field"><label>Grade (optional)</label><input type="text" name="grade" value="<?php echo htmlspecialchars($editRow['grade'] ?? ''); ?>"></div>
                <div class="field"><label>Section (optional)</label><input type="text" name="section" value="<?php echo htmlspecialchars($editRow['section'] ?? ''); ?>"></div>
            </div>
            <div class="field"><label><?php echo $editRow ? "New Password (leave blank to keep)" : "Password"; ?></label><input type="password" name="password"></div>
            <div class="btn-row">
                <button type="submit" class="btn">Save Teacher</button>
                <a class="btn btn-outline" href="teachers.php">Back to List</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="teachers.php?action=add">+ Add Teacher</a>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Name</th><th>Reg#</th><th>Scope</th><th class="center">Today</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($teachers as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['full_name']); ?><br><span class="help-text"><?php echo htmlspecialchars($t['email']); ?></span></td>
                    <td><?php echo htmlspecialchars($t['registration_no'] ?: '-'); ?></td>
                    <td><?php echo $t['grade'] ? 'Grade ' . htmlspecialchars($t['grade']) : 'All grades'; ?><?php echo $t['section'] ? ' / ' . htmlspecialchars($t['section']) : ''; ?></td>
                    <td class="center">
                        <form method="post" action="teachers.php" style="display:inline;">
                            <input type="hidden" name="mark_attendance" value="1">
                            <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                            <?php if ($t['today_status']): ?>
                                <span class="tag tag-<?php echo $t['today_status']==='present'? 'active':'blocked'; ?>"><?php echo htmlspecialchars($t['today_status']); ?></span>
                            <?php else: ?>
                                <button type="submit" name="status" value="present" class="btn btn-sm">Present</button>
                                <button type="submit" name="status" value="absent" class="btn btn-sm btn-outline">Absent</button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td class="center"><?php echo $t['is_banned'] ? '<span class="tag tag-blocked">Banned</span>' : '<span class="tag tag-active">Active</span>'; ?></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="teachers.php?edit=<?php echo $t['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-warn" href="teachers.php?toggle_ban=<?php echo $t['id']; ?>"><?php echo $t['is_banned'] ? 'Unban' : 'Ban'; ?></a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="teachers.php?delete=<?php echo $t['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$teachers): ?><tr><td colspan="6">No teachers yet.</td></tr><?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
