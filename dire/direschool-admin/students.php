<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Students";
$mySchool = (int)$admin['school_id'];

$error = "";
$success = "";

// ---- Save (add/edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $id = (int)($_POST['id'] ?? 0);
    $studentNo = trim($_POST['student_no'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $englishName = trim($_POST['english_name'] ?? '');
    $gender = $_POST['gender'] ?: null;
    $age = $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $grade = trim($_POST['grade'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $kebele = trim($_POST['kebele'] ?? '');
    $houseNo = trim($_POST['house_no'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($fullName === '' || $grade === '' || $section === '') {
        $error = "Full Name, Grade and Section are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE students SET student_no=?, full_name=?, english_name=?, gender=?, age=?, grade=?, section=?, kebele=?, house_no=?, image_url=? WHERE id=? AND school_id=? ");
            $stmt->execute([$studentNo, $fullName, $englishName, $gender, $age, $grade, $section, $kebele, $houseNo, $imageUrl ?: null, $id, $mySchool]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO students (school_id, student_no, full_name, english_name, gender, age, grade, section, kebele, house_no, image_url) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$mySchool, $studentNo, $fullName, $englishName, $gender, $age, $grade, $section, $kebele, $houseNo, $imageUrl ?: null]);
        }
        header("Location: students.php?saved=1");
        exit;
    }
}

// ---- Delete ----
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $chatStmt = $pdo->prepare("DELETE FROM chat_messages WHERE (sender_type='student' AND sender_id=?) OR (receiver_type='student' AND receiver_id=?)");
    $chatStmt->execute([$delId, $delId]);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id=? AND school_id=? ");
    $stmt->execute([$delId, $mySchool]);
    header("Location: students.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editStudent = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id=? AND school_id=? ");
    $stmt->execute([$editId, $mySchool]);
    $editStudent = $stmt->fetch();
    if (!$editStudent) { header("Location: students.php"); exit; }
}

$fGrade = trim($_GET['grade'] ?? '');
$fSection = trim($_GET['section'] ?? '');
$fSearch = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM students WHERE school_id = ? ";
$params = [$mySchool];
if ($fGrade !== '') { $sql .= " AND grade = ? "; $params[] = $fGrade; }
if ($fSection !== '') { $sql .= " AND section = ? "; $params[] = $fSection; }
if ($fSearch !== '') { $sql .= " AND (full_name LIKE ? OR student_no LIKE ?)"; $params[] = "%$fSearch%"; $params[] = "%$fSearch%"; }
$sql .= " ORDER BY grade, section, full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Student saved.</div><?php endif; ?>

<?php if ($editId !== null): ?>
    <div class="card reveal">
        <h2><?php echo $editStudent ? "Edit Student" : "Add Student"; ?></h2>
        <form method="post" action="students.php">
            <input type="hidden" name="save_student" value="1">
            <input type="hidden" name="id" value="<?php echo $editStudent['id'] ?? ''; ?>">
            <div class="grid grid-2">
                <div class="field"><label for="student_no">Student ID</label><input type="text" id="student_no" name="student_no" value="<?php echo htmlspecialchars($editStudent['student_no'] ?? ''); ?>"></div>
                <div class="field"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($editStudent['full_name'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-2">
                <div class="field"><label for="english_name">English Name</label><input type="text" id="english_name" name="english_name" value="<?php echo htmlspecialchars($editStudent['english_name'] ?? ''); ?>"></div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">--</option>
                        <option value="M" <?php echo (($editStudent['gender'] ?? '') === 'M') ? 'selected' : ''; ?>>Male</option>
                        <option value="F" <?php echo (($editStudent['gender'] ?? '') === 'F') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-4">
                <div class="field"><label for="age">Age</label><input type="number" id="age" name="age" value="<?php echo htmlspecialchars($editStudent['age'] ?? ''); ?>"></div>
                <div class="field"><label for="grade">Grade</label><input type="text" id="grade" name="grade" required value="<?php echo htmlspecialchars($editStudent['grade'] ?? ''); ?>"></div>
                <div class="field"><label for="section">Section</label><input type="text" id="section" name="section" required value="<?php echo htmlspecialchars($editStudent['section'] ?? ''); ?>"></div>
                <div class="field"><label for="house_no">House No.</label><input type="text" id="house_no" name="house_no" value="<?php echo htmlspecialchars($editStudent['house_no'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-2">
                <div class="field"><label for="kebele">Kebele</label><input type="text" id="kebele" name="kebele" value="<?php echo htmlspecialchars($editStudent['kebele'] ?? ''); ?>"></div>
                <div class="field"><label for="image_url">Photo URL (optional)</label><input type="text" id="image_url" name="image_url" value="<?php echo htmlspecialchars($editStudent['image_url'] ?? ''); ?>"></div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Save Student</button>
                <a class="btn btn-outline" href="students.php">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="card reveal">
        <h2>Filter</h2>
        <form method="get" action="students.php" class="grid grid-3">
            <div class="field"><label>Grade</label><input type="text" name="grade" value="<?php echo htmlspecialchars($fGrade); ?>" placeholder="e.g. 9"></div>
            <div class="field"><label>Section</label><input type="text" name="section" value="<?php echo htmlspecialchars($fSection); ?>" placeholder="e.g. A"></div>
            <div class="field"><label>Search Name/ID</label><input type="text" name="q" value="<?php echo htmlspecialchars($fSearch); ?>"></div>
            <div class="field" style="grid-column: 1 / -1;">
                <button type="submit" class="btn">Apply Filters</button>
                <a class="btn btn-outline" href="students.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="students.php?action=add">+ Add Student</a>
    </div>

    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Student ID</th><th>Full Name</th><th class="center">Grade</th><th class="center">Section</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($students as $st): ?>
                <tr>
                    <td><?php echo htmlspecialchars($st['student_no'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($st['full_name']); ?></td>
                    <td class="center"><?php echo htmlspecialchars($st['grade']); ?></td>
                    <td class="center"><?php echo htmlspecialchars($st['section']); ?></td>
                    <td class="center"><span class="tag tag-<?php echo $st['reg_status']==='active'? 'active':($st['reg_status']==='banned'? 'blocked':'pending'); ?>"><?php echo htmlspecialchars($st['reg_status']); ?></span></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="students.php?edit=<?php echo $st['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="students.php?delete=<?php echo $st['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="6">No students found.</td></tr><?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
