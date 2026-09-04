<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "School Admins";

$error = "";
$success = "";

// SuperAdmin's ONLY staff-management power: create/edit an Admin account
// for a school. Everything inside a school (library, students, teachers,
// conduct, etc.) is that school's Admin's job, not the SuperAdmin's.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $schoolId = (int)($_POST['school_id'] ?? 0);
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $fullName === '' || !$schoolId) {
        $error = "Email, full name, and a School are all required.";
    } else {
        if ($id) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, school_id=?, password_hash=? WHERE id=? AND role='admin'");
                $stmt->execute([$email, $fullName, $schoolId, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET email=?, full_name=?, school_id=? WHERE id=? AND role='admin'");
                $stmt->execute([$email, $fullName, $schoolId, $id]);
            }
            $success = "School Admin updated.";
        } else {
            if ($password === '') {
                $error = "Password is required for a new Admin account.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (email, full_name, role, school_id, password_hash) VALUES (?,?, 'admin',?,?)");
                $stmt->execute([$email, $fullName, $schoolId, $hash]);
                $success = "School Admin created.";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id=? AND role='admin'");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: admins.php");
    exit;
}
if (isset($_GET['toggle_ban'])) {
    $stmt = $pdo->prepare("UPDATE admins SET is_banned = 1 - is_banned WHERE id=? AND role='admin'");
    $stmt->execute([(int)$_GET['toggle_ban']]);
    header("Location: admins.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editRow = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=? AND role='admin'");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$schools = $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll();
$schoolAdmins = $pdo->query("
    SELECT a.*, sc.name AS school_name FROM admins a
    LEFT JOIN schools sc ON sc.id = a.school_id
    WHERE a.role = 'admin' ORDER BY sc.name, a.full_name
")->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($editId !== null): ?>
    <form method="post" action="admins.php">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">
        <div class="card reveal">
            <h2><?php echo $editRow ? "Edit" : "Assign New"; ?> School Admin</h2>
            <div class="grid grid-2">
                <div class="field"><label>Email</label><input type="email" name="email" required value="<?php echo htmlspecialchars($editRow['email'] ?? ''); ?>"></div>
                <div class="field"><label>Full Name</label><input type="text" name="full_name" required value="<?php echo htmlspecialchars($editRow['full_name'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-2">
                <div class="field">
                    <label>School</label>
                    <select name="school_id" required>
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (($editRow['school_id'] ?? '') == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label><?php echo $editRow ? "New Password (leave blank to keep)" : "Password"; ?></label>
                    <input type="password" name="password">
                </div>
            </div>
            <p class="help-text">This Admin will then be able to manage everything inside their own school — teachers, students, library, report cards, conduct — from the Admin site.</p>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn">Save</button>
            <a class="btn btn-outline" href="admins.php">Back to List</a>
        </div>
    </form>
<?php else: ?>
    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="admins.php?action=add">+ Assign a School Admin</a>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Email</th><th>Name</th><th>School</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($schoolAdmins as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['email']); ?></td>
                    <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['school_name'] ?? '-'); ?></td>
                    <td class="center"><?php echo $a['is_banned'] ? '<span class="tag tag-blocked">Banned</span>' : '<span class="tag tag-active">Active</span>'; ?></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="admins.php?edit=<?php echo $a['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-warn" href="admins.php?toggle_ban=<?php echo $a['id']; ?>"><?php echo $a['is_banned'] ? 'Unban' : 'Ban'; ?></a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="admins.php?delete=<?php echo $a['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$schoolAdmins): ?><tr><td colspan="5">No School Admins yet.</td></tr><?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
