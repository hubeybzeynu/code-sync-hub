<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin', 'admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Exam Results";
$isSuper = isSuperAdmin($admin);
$myScopedSchool = (int)$admin['school_id'];

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $schoolId = $isSuper ? ($_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null) : $myScopedSchool;
    $studentId = $_POST['student_id'] !== '' ? (int)$_POST['student_id'] : null;
    $studentNo = trim($_POST['student_no'] ?? '');
    $studentName = trim($_POST['student_name'] ?? '');
    $examType = in_array($_POST['exam_type'] ?? '', ['mid', 'final']) ? $_POST['exam_type'] : 'mid';
    $subject = trim($_POST['subject'] ?? '');
    $gradeGroup = trim($_POST['grade_group'] ?? '');
    $resultImageUrl = trim($_POST['result_image_url'] ?? '');
    $answerImageUrl = trim($_POST['answer_image_url'] ?? '');
    $studentPassword = trim($_POST['student_password'] ?? '');

    // A school-scoped admin can only link a student from their own school.
    if (!$isSuper && $studentId) {
        $chk = $pdo->prepare("SELECT id FROM students WHERE id=? AND school_id=? ");
        $chk->execute([$studentId, $myScopedSchool]);
        if (!$chk->fetch()) $studentId = null;
    }

    if ($studentNo === '' || $studentName === '') {
        $error = "Student ID and student name are required.";
    } else {
        if ($id) {
            $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
            $stmt = $pdo->prepare("UPDATE exam_results SET school_id=?, student_id=?, student_no=?, student_name=?, exam_type=?, subject=?, grade_group=?, result_image_url=?, answer_image_url=?, student_password=? WHERE $cond");
            $stmt->execute([$schoolId, $studentId, $studentNo, $studentName, $examType, $subject, $gradeGroup, $resultImageUrl ?: null, $answerImageUrl ?: null, $studentPassword, $id]);
            $success = "Exam result updated.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO exam_results (school_id, student_id, student_no, student_name, exam_type, subject, grade_group, result_image_url, answer_image_url, student_password) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$schoolId, $studentId, $studentNo, $studentName, $examType, $subject, $gradeGroup, $resultImageUrl ?: null, $answerImageUrl ?: null, $studentPassword]);
            $success = "Exam result added.";
        }
    }
}

if (isset($_GET['delete'])) {
    $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
    $stmt = $pdo->prepare("DELETE FROM exam_results WHERE $cond");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: exam-results.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editRow = null;
if ($editId) {
    $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
    $stmt = $pdo->prepare("SELECT * FROM exam_results WHERE $cond");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
    if (!$editRow) { header("Location: exam-results.php"); exit; }
}

$schools = $isSuper ? $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll() : [];
$studentsSql = "SELECT id, full_name, student_no FROM students";
if (!$isSuper) $studentsSql .= " WHERE school_id = $myScopedSchool";
$studentsSql .= " ORDER BY full_name";
$students = $pdo->query($studentsSql)->fetchAll();

$fType = trim($_GET['exam_type'] ?? '');
$sql = "SELECT er.*, sc.name AS school_name FROM exam_results er LEFT JOIN schools sc ON sc.id = er.school_id WHERE 1=1";
$params = [];
if (!$isSuper) { $sql .= " AND er.school_id = ? "; $params[] = $myScopedSchool; }
if ($fType !== '' && $editId === null) { $sql .= " AND er.exam_type = ? "; $params[] = $fType; }
$sql .= " ORDER BY er.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($editId !== null): ?>
    <form method="post" action="exam-results.php">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">
        <div class="card reveal">
            <h2><?php echo $editRow ? "Edit" : "Add"; ?> Exam Result</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label for="school_id">School</label>
                    <?php if ($isSuper): ?>
                        <select id="school_id" name="school_id">
                            <option value="">-- Optional --</option>
                            <?php foreach ($schools as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo (($editRow['school_id'] ?? '') == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" value="Your school" disabled>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="student_id">Linked Student (optional)</label>
                    <select id="student_id" name="student_id">
                        <option value="">-- Optional --</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php echo (($editRow['student_id'] ?? '') == $st['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st['full_name']); ?> (<?php echo htmlspecialchars($st['student_no']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="field"><label for="student_no">Student ID</label><input type="text" id="student_no" name="student_no" required value="<?php echo htmlspecialchars($editRow['student_no'] ?? ''); ?>"></div>
                <div class="field"><label for="student_name">Student Name</label><input type="text" id="student_name" name="student_name" required value="<?php echo htmlspecialchars($editRow['student_name'] ?? ''); ?>"></div>
                <div class="field">
                    <label for="exam_type">Exam Type</label>
                    <select id="exam_type" name="exam_type">
                        <option value="mid" <?php echo (($editRow['exam_type'] ?? 'mid') === 'mid') ? 'selected' : ''; ?>>Mid-Term</option>
                        <option value="final" <?php echo (($editRow['exam_type'] ?? '') === 'final') ? 'selected' : ''; ?>>Final-Term</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="field"><label for="subject">Subject</label><input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($editRow['subject'] ?? ''); ?>"></div>
                <div class="field"><label for="grade_group">Grade Group</label><input type="text" id="grade_group" name="grade_group" placeholder="e.g. Grade 9" value="<?php echo htmlspecialchars($editRow['grade_group'] ?? ''); ?>"></div>
            </div>
            <div class="grid grid-2">
                <div class="field"><label for="result_image_url">Result Image URL</label><input type="text" id="result_image_url" name="result_image_url" value="<?php echo htmlspecialchars($editRow['result_image_url'] ?? ''); ?>"></div>
                <div class="field"><label for="answer_image_url">Answer Sheet Image URL</label><input type="text" id="answer_image_url" name="answer_image_url" value="<?php echo htmlspecialchars($editRow['answer_image_url'] ?? ''); ?>"></div>
            </div>
            <div class="field" style="max-width:250px;"><label for="student_password">Student Password</label><input type="text" id="student_password" name="student_password" value="<?php echo htmlspecialchars($editRow['student_password'] ?? ''); ?>"></div>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn">Save</button>
            <a class="btn btn-outline" href="exam-results.php">Back to List</a>
        </div>
    </form>

<?php else: ?>
    <div class="card reveal">
        <form method="get" action="exam-results.php" class="grid grid-3">
            <div class="field">
                <label>Exam Type</label>
                <select name="exam_type" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="mid" <?php echo $fType === 'mid' ? 'selected' : ''; ?>>Mid-Term</option>
                    <option value="final" <?php echo $fType === 'final' ? 'selected' : ''; ?>>Final-Term</option>
                </select>
            </div>
        </form>
    </div>

    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="exam-results.php?action=add">+ Add Exam Result</a>
    </div>

    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Student ID</th><th>Name</th><th>School</th><th class="center">Type</th><th>Subject</th><th class="center">Password</th><th class="center">Actions</th></tr>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['student_no']); ?></td>
                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['school_name'] ?? '-'); ?></td>
                    <td class="center"><?php echo $r['exam_type'] === 'mid' ? 'Mid-Term' : 'Final-Term'; ?></td>
                    <td><?php echo htmlspecialchars($r['subject'] ?? '-'); ?></td>
                    <td class="center"><?php echo htmlspecialchars($r['student_password'] ?: '-'); ?></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="exam-results.php?edit=<?php echo $r['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="exam-results.php?delete=<?php echo $r['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$results): ?><tr><td colspan="7">No exam results yet.</td></tr><?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
