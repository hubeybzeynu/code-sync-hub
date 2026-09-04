<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Staff Accounts";
$mySchool = (int)$admin['school_id'];

$error = "";
$success = "";
// Unlimited: subadmin, teacher. Capped at ONE per school: librarian, staff.
$CAPPED_ROLES = ['librarian', 'staff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['subadmin', 'teacher', 'librarian', 'staff'], true) ? $_POST['role'] : 'teacher';
    $grade = trim($_POST['grade'] ?? '') ?: null;
    $section = trim($_POST['section'] ?? '') ?: null;
    $regNo = trim($_POST['registration_no'] ?? '') ?: null;
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $fullName === '') {
        $error = "Email and full name are required.";
    } elseif (in_array($role, $CAPPED_ROLES, true)) {
        // This school may only ever have ONE librarian and ONE staff account.
        $chk = $pdo->prepare("SELECT id FROM admins WHERE role=? AND school_id=? AND id != ? ");
        $chk->execute([$role, $mySchool, $id ?: 0]);
        if ($chk->fetch()) {
            $error = ucfirst($role) . " is limited to one account per school, and this school already has one.";
        }
    }

    if (!$error) {
        if ($id) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, role=?, grade=?, section=?, registration_no=?, password_hash=? WHERE id=? AND school_id=? ");
                $stmt->execute([$email, $fullName, $role, $grade, $section, $regNo, $hash, $id, $mySchool]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, role=?, grade=?, section=?, registration_no=? WHERE id=? AND school_id=? ");
                $stmt->execute([$email, $fullName, $role, $grade, $section, $regNo, $id, $mySchool]);
            }
            $success = "Staff account updated.";
        } else {
            if ($password === '') {
                $error = "Password is required for a new account.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (email, full_name, role, school_id, grade, section, registration_no, password_hash) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$email, $fullName, $role, $mySchool, $grade, $section, $regNo, $hash]);
                $success = "Staff account created.";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId !== (int)$admin['id']) {
        $roleStmt = $pdo->prepare("SELECT role FROM admins WHERE id=? AND school_id=? ");
        $roleStmt->execute([$delId, $mySchool]);
        $deletedRole = $roleStmt->fetch();
        if ($deletedRole) {
            $chatStmt = $pdo->prepare("DELETE FROM chat_messages WHERE (sender_type=? AND sender_id=?) OR (receiver_type=? AND receiver_id=?)");
            $chatStmt->execute([$deletedRole['role'], $delId, $deletedRole['role'], $delId]);
        }
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id=? AND school_id=? ");
        $stmt->execute([$delId, $mySchool]);
    }
    header("Location: admins.php");
    exit;
}
if (isset($_GET['toggle_ban'])) {
    $stmt = $pdo->prepare("UPDATE admins SET is_banned = 1 - is_banned WHERE id=? AND school_id=? ");
    $stmt->execute([(int)$_GET['toggle_ban'], $mySchool]);
    header("Location: admins.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editRow = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=? AND school_id=? ");
    $stmt->execute([$editId, $mySchool]);
    $editRow = $stmt->fetch();
    if (!$editRow) { header("Location: admins.php"); exit; }
}

$existingRoles = $pdo->prepare("SELECT role, COUNT(*) c FROM admins WHERE school_id=? AND role IN ('librarian', 'staff') GROUP BY role");
$existingRoles->execute([$mySchool]);
$existingRoles = array_column($existingRoles->fetchAll(), 'c', 'role');
$hasLibrarian = !empty($existingRoles['librarian']);
$hasStaff = !empty($existingRoles['staff']);

$staffList = $pdo->query("SELECT * FROM admins WHERE school_id=$mySchool AND role IN ('subadmin', 'teacher', 'librarian', 'staff') ORDER BY FIELD(role, 'subadmin', 'teacher', 'librarian', 'staff'), full_name")->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error reveal in-view"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success reveal in-view"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($editId !== null): ?>
    <form method="post" action="admins.php">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">
        <div class="card reveal">
            <h2><?php echo $editRow ? "Edit" : "Add"; ?> Staff Account</h2>
            <div class="grid grid-2">
                <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($editRow['email'] ?? ''); ?>"></div>
                <div class="field"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($editRow['full_name'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-2">
                <div class="field">
                    <label for="password"><?php echo $editRow ? "New Password (leave blank to keep current)" : "Password"; ?></label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="subadmin" <?php echo (($editRow['role'] ?? '') === 'subadmin') ? 'selected' : ''; ?>>Sub Admin</option>
                        <option value="teacher" <?php echo (($editRow['role'] ?? 'teacher') === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        <option value="librarian" <?php echo ($hasLibrarian && (($editRow['role'] ?? '') !== 'librarian')) ? 'disabled' : ''; ?> <?php echo (($editRow['role'] ?? '') === 'librarian') ? 'selected' : ''; ?>>Librarian <?php echo $hasLibrarian ? '(already assigned)' : '(only 1 allowed)'; ?></option>
                        <option value="staff" <?php echo ($hasStaff && (($editRow['role'] ?? '') !== 'staff')) ? 'disabled' : ''; ?> <?php echo (($editRow['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff <?php echo $hasStaff ? '(already assigned)' : '(only 1 allowed)'; ?></option>
                    </select>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="field"><label for="grade">Grade (Teacher scope, optional)</label><input type="text" id="grade" name="grade" value="<?php echo htmlspecialchars($editRow['grade'] ?? ''); ?>"></div>
                <div class="field"><label for="section">Section (optional)</label><input type="text" id="section" name="section" value="<?php echo htmlspecialchars($editRow['section'] ?? ''); ?>"></div>
                <div class="field"><label for="registration_no">Registration No. (Teacher)</label><input type="text" id="registration_no" name="registration_no" value="<?php echo htmlspecialchars($editRow['registration_no'] ?? ''); ?>"></div>
            </div>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn">Save Staff Account</button>
            <a class="btn btn-outline" href="admins.php">Back to List</a>
        </div>
    </form>

<?php else: ?>
    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="admins.php?action=add">+ Add Staff Account</a>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Email</th><th>Name</th><th class="center">Role</th><th>Scope</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($staffList as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['email']); ?></td>
                    <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                    <td class="center"><?php echo roleBadge($a['role']); ?></td>
                    <td>
                        <?php echo $a['grade'] ? "Grade " . htmlspecialchars($a['grade']) : '-'; ?>
                        <?php echo $a['section'] ? ", Sec " . htmlspecialchars($a['section']) : ''; ?>
                        <?php echo $a['registration_no'] ? ' &middot; Reg# ' . htmlspecialchars($a['registration_no']) : ''; ?>
                    </td>
                    <td class="center"><?php echo $a['is_banned'] ? '<span class="tag tag-blocked">Banned</span>' : '<span class="tag tag-active">Active</span>'; ?></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="admins.php?edit=<?php echo $a['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="admins.php?delete=<?php echo $a['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$staffList): ?><tr><td colspan="6">No staff accounts yet.</td></tr><?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
