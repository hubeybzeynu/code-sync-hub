<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Ministry Results";

$SUBJECTS = ["Amharic", "English", "Mathematics", "General Science", "Social Studies", "HPE & Arts"];

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $schoolId = $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;
    $studentId = $_POST['student_id'] !== '' ? (int)$_POST['student_id'] : null;
    $studentNo = trim($_POST['student_no'] ?? '');
    $studentName = trim($_POST['student_name'] ?? '');
    $schoolYear = trim($_POST['school_year'] ?? '');
    $promotionStatus = $_POST['promotion_status'] ?? 'promoted';
    $promotionLabel = trim($_POST['promotion_label'] ?? '');
    $photoUrl = trim($_POST['photo_url'] ?? '');

    if ($studentNo === '' || $studentName === '') {
        $error = "Registration number and student name are required.";
    } else {
        $subjects = [];
        $total = 0;
        $count = 0;
        foreach ($SUBJECTS as $s) {
            $key = str_replace([' ', '&'], ['_', 'and'], $s);
            $val = $_POST["subject_$key"] ?? '';
            if ($val !== '') {
                $subjects[$s] = (float)$val;
                $total += (float)$val;
                $count++;
            }
        }
        $average = $count ? $total / $count : null;
        $subjectsJson = json_encode($subjects);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE ministry_results SET school_id=?, student_id=?, student_no=?, student_name=?, school_year=?, subjects=?, total=?, average=?, promotion_status=?, promotion_label=?, photo_url=? WHERE id=? ");
            $stmt->execute([$schoolId, $studentId, $studentNo, $studentName, $schoolYear, $subjectsJson, $total, $average, $promotionStatus, $promotionLabel, $photoUrl ?: null, $id]);
            $success = "Ministry result updated.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO ministry_results (school_id, student_id, student_no, student_name, grade, school_year, subjects, total, average, promotion_status, promotion_label, photo_url) VALUES (?,?,?,?, '8',?,?,?,?,?,?,?)");
            $stmt->execute([$schoolId, $studentId, $studentNo, $studentName, $schoolYear, $subjectsJson, $total, $average, $promotionStatus, $promotionLabel, $photoUrl ?: null]);
            $success = "Ministry result added.";
        }
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ministry_results WHERE id = ? ");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: ministry-results.php");
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editRow = null;
$editSubjects = [];
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM ministry_results WHERE id = ? ");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
    if ($editRow) $editSubjects = json_decode($editRow['subjects'] ?? '{}', true) ?: [];
}

$schools = $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll();
$students = $pdo->query("SELECT id, full_name, student_no FROM students WHERE grade = '8' ORDER BY full_name")->fetchAll();
$results = $pdo->query("
    SELECT mr.*, sc.name AS school_name FROM ministry_results mr
    LEFT JOIN schools sc ON sc.id = mr.school_id
    ORDER BY mr.created_at DESC")->fetchAll();

include"includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<?php if ($editId !== null): ?>
    <form method="post" action="ministry-results.php">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">
        <div class="card reveal">
            <h2><?php echo $editRow ? "Edit" : "Add"; ?> Ministry Result (Grade 8)</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label for="school_id">School</label>
                    <select id="school_id" name="school_id">
                        <option value="">-- Optional --</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (($editRow['school_id'] ?? '') == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                <div class="field"><label for="student_no">Registration Number</label><input type="text" id="student_no" name="student_no" required value="<?php echo htmlspecialchars($editRow['student_no'] ?? ''); ?>"></div>
                <div class="field"><label for="student_name">Student Name</label><input type="text" id="student_name" name="student_name" required value="<?php echo htmlspecialchars($editRow['student_name'] ?? ''); ?>"></div>
                <div class="field"><label for="school_year">School Year</label><input type="text" id="school_year" name="school_year" value="<?php echo htmlspecialchars($editRow['school_year'] ?? ''); ?>"></div>
            </div>
        </div>

        <div class="card reveal">
            <h2>Subject Scores</h2>
            <div class="grid grid-3">
                <?php foreach ($SUBJECTS as $s): ?>
                    <?php $key = str_replace([' ', '&'], ['_', 'and'], $s); ?>
                    <div class="field">
                        <label for="subject_<?php echo $key; ?>"><?php echo htmlspecialchars($s); ?></label>
                        <input type="number" step="0.1" min="0" max="100" id="subject_<?php echo $key; ?>" name="subject_<?php echo $key; ?>" value="<?php echo htmlspecialchars($editSubjects[$s] ?? ''); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card reveal">
            <h2>Result</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label for="promotion_status">Promotion Status</label>
                    <select id="promotion_status" name="promotion_status">
                        <option value="promoted" <?php echo (($editRow['promotion_status'] ?? '') === 'promoted') ? 'selected' : ''; ?>>Promoted</option>
                        <option value="not_promoted" <?php echo (($editRow['promotion_status'] ?? '') === 'not_promoted') ? 'selected' : ''; ?>>Not Promoted</option>
                    </select>
                </div>
                <div class="field"><label for="promotion_label">Promotion Label (shown to student)</label><input type="text" id="promotion_label" name="promotion_label" placeholder="e.g. Promoted to Grade 9" value="<?php echo htmlspecialchars($editRow['promotion_label'] ?? ''); ?>"></div>
            </div>
            <div class="field"><label for="photo_url">Photo URL (optional)</label><input type="text" id="photo_url" name="photo_url" value="<?php echo htmlspecialchars($editRow['photo_url'] ?? ''); ?>"></div>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn">Save Result</button>
            <a class="btn btn-outline" href="ministry-results.php">Back to List</a>
        </div>
    </form>

<?php else: ?>
    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="ministry-results.php?action=add">+ Add Ministry Result</a>
        <a class="btn btn-outline" href="ministry-import.php"> Import from CSV (counting machine)</a>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Reg. No.</th><th>Name</th><th>School</th><th class="center">Total</th><th class="center">Average</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['student_no']); ?></td>
                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['school_name'] ?? '-'); ?></td>
                    <td class="center"><?php echo $r['total'] !== null ? $r['total'] : '-'; ?></td>
                    <td class="center"><?php echo $r['average'] !== null ? number_format($r['average'], 1) : '-'; ?></td>
                    <td class="center">
                        <?php if ($r['promotion_status'] === 'promoted'): ?>
                            <span class="tag tag-promoted">Promoted</span>
                        <?php else: ?>
                            <span class="tag tag-detained">Not Promoted</span>
                        <?php endif; ?>
                    </td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="ministry-results.php?edit=<?php echo $r['id']; ?>">Edit</a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="ministry-results.php?delete=<?php echo $r['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$results): ?><tr><td colspan="7">No ministry results yet.</td></tr><?php endif; ?>
        </table>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
