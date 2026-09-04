<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin', 'admin']);
$admin = currentAdmin($pdo);
$isSuper = isSuperAdmin($admin);
$myScopedSchool = (int)$admin['school_id'];

$SUBJECTS = [
    "Amharic", "English", "Mathematics", "General Science", "Social Studies",
    "Citizenship Education", "Performing & Visual Arts", "Information Technology",
    "Health & Physical Education", "Career & Technical Education",
];
$QUARTERS = ["1", "2", "3", "4"];

function safeNum($v) { return is_numeric($v) ? (float)$v : null; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = $id ? "Edit Report Card" : "New Report Card";
$error = "";
$success = "";

$card = null;
if ($id) {
    $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
    $stmt = $pdo->prepare("SELECT * FROM report_cards WHERE $cond");
    $stmt->execute([$id]);
    $card = $stmt->fetch();
    if (!$card) { header("Location: report-cards.php"); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)$_POST['student_id'];
    $schoolId = $isSuper ? (int)$_POST['school_id'] : $myScopedSchool;
    $grade = trim($_POST['grade']);
    $section = trim($_POST['section']);
    $schoolYear = trim($_POST['school_year']);
    $teacherName = trim($_POST['teacher_name'] ?? '');
    $sex = $_POST['sex'] ?: null;
    $age = $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $kebele = trim($_POST['kebele'] ?? '');
    $houseNo = trim($_POST['house_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $cardPassword = trim($_POST['card_password'] ?? '');

    // A school-scoped admin can only attach the card to a student in their own school.
    if (!$isSuper) {
        $chk = $pdo->prepare("SELECT id FROM students WHERE id=? AND school_id=? ");
        $chk->execute([$studentId, $myScopedSchool]);
        if (!$chk->fetch()) $studentId = 0;
    }

    if (!$studentId || !$schoolId || $grade === '' || $section === '' || $schoolYear === '') {
        $error = "Student, School, Grade, Section and School Year are required (and the student must belong to your school).";
    } else {
        // Build subjects / conduct / attendance JSON from the grid inputs
        $subjects = [];
        $conduct = [];
        foreach ($SUBJECTS as $s) {
            $key = str_replace([' ', '&'], ['_', 'and'], $s);
            $row = [];
            foreach ($QUARTERS as $q) {
                $val = $_POST["mark_{$key}_{$q}"] ?? '';
                $row[$q] = $val !== '' ? (float)$val : null;
            }
            $subjects[$s] = $row;
            $conduct[$s] = $_POST["conduct_{$key}"] ?? '';
        }
        $attendance = [];
        foreach (['days_present', 'days_absent', 'times_tardy', 'total_academic_days'] as $field) {
            $row = [];
            foreach ($QUARTERS as $q) {
                $val = $_POST["{$field}_{$q}"] ?? '';
                $row[$q] = $val !== '' ? (int)$val : null;
            }
            $attendance[$field] = $row;
        }

        // Work out promotion_status the same way the public portal does, so
        // the Super Admin's transfer-verification screen shows the truth:
        // complete only once all 10 subjects have all 4 quarters filled;
        // detained if 2+ subject averages fall below 60, else promoted.
        $complete = true;
        $fails = 0;
        foreach ($SUBJECTS as $s) {
            $row = $subjects[$s];
            $vals = array_filter([$row['1'], $row['2'], $row['3'], $row['4']], fn($v) => $v !== null && $v > 0);
            if (count($vals) < 4) { $complete = false; }
            $avg = count($vals) ? array_sum($vals) / count($vals) : null;
            if ($avg === null || $avg < 60) $fails++;
        }
        $promotionStatus = !$complete ? 'pending' : ($fails >= 2 ? 'detained' : 'promoted');

        $subjectsJson = json_encode($subjects);
        $conductJson = json_encode($conduct);
        $presentJson = json_encode($attendance['days_present']);
        $absentJson = json_encode($attendance['days_absent']);
        $tardyJson = json_encode($attendance['times_tardy']);
        $totalDaysJson = json_encode($attendance['total_academic_days']);

        if ($id) {
            $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
            $stmt = $pdo->prepare("UPDATE report_cards SET student_id=?, school_id=?, grade=?, section=?, school_year=?, teacher_name=?, sex=?, age=?, kebele=?, house_no=?, subjects=?, conduct=?, days_present=?, days_absent=?, times_tardy=?, total_academic_days=?, remarks=?, card_password=?, promotion_status=? WHERE $cond");
            $stmt->execute([$studentId, $schoolId, $grade, $section, $schoolYear, $teacherName, $sex, $age, $kebele, $houseNo, $subjectsJson, $conductJson, $presentJson, $absentJson, $tardyJson, $totalDaysJson, $remarks, $cardPassword, $promotionStatus, $id]);
            $success = "Report card updated.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO report_cards (student_id, school_id, grade, section, school_year, teacher_name, sex, age, kebele, house_no, subjects, conduct, days_present, days_absent, times_tardy, total_academic_days, remarks, card_password, promotion_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$studentId, $schoolId, $grade, $section, $schoolYear, $teacherName, $sex, $age, $kebele, $houseNo, $subjectsJson, $conductJson, $presentJson, $absentJson, $tardyJson, $totalDaysJson, $remarks, $cardPassword, $promotionStatus]);
            $id = $pdo->lastInsertId();
            $success = "Report card created.";
        }
        $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
        $stmt = $pdo->prepare("SELECT * FROM report_cards WHERE $cond");
        $stmt->execute([$id]);
        $card = $stmt->fetch();
    }
}

$schools = $isSuper ? $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll() : [];
$studentsSql = "SELECT id, full_name, student_no, school_id, grade, section FROM students";
if (!$isSuper) $studentsSql .= " WHERE school_id = $myScopedSchool";
$studentsSql .= " ORDER BY full_name";
$students = $pdo->query($studentsSql)->fetchAll();

$subjectsData = $card ? (json_decode($card['subjects'] ?? '{}', true) ?: []) : [];
$conductData = $card ? (json_decode($card['conduct'] ?? '{}', true) ?: []) : [];
$attendanceData = [
    'days_present' => $card ? (json_decode($card['days_present'] ?? '{}', true) ?: []) : [],
    'days_absent' => $card ? (json_decode($card['days_absent'] ?? '{}', true) ?: []) : [],
    'times_tardy' => $card ? (json_decode($card['times_tardy'] ?? '{}', true) ?: []) : [],
    'total_academic_days' => $card ? (json_decode($card['total_academic_days'] ?? '{}', true) ?: []) : [],
];

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($card && $card['promotion_status'] !== 'pending'): ?>
    <div class="alert alert-info">
        Current calculated result:
        <?php if ($card['promotion_status'] === 'promoted'): ?><span class="tag tag-promoted">Promoted</span>
        <?php else: ?><span class="tag tag-detained">Detained</span><?php endif; ?>
        — this is what the Super Admin will see if this student ever requests a transfer.
    </div>
<?php endif; ?>

<form method="post" action="report-card-edit.php<?php echo $id ? "?id=$id" : ""; ?>">
    <div class="card reveal">
        <h2>Student &amp; Class Info</h2>
        <div class="grid grid-2">
            <div class="field">
                <label for="school_id">School</label>
                <?php if ($isSuper): ?>
                    <select id="school_id" name="school_id" required>
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (($card['school_id'] ?? '') == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" value="Your school" disabled>
                    <input type="hidden" name="school_id" value="<?php echo $myScopedSchool; ?>">
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="student_id">Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?php echo $st['id']; ?>" <?php echo (($card['student_id'] ?? '') == $st['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['full_name']); ?> (<?php echo htmlspecialchars($st['student_no'] ?? '-'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-4">
            <div class="field"><label for="grade">Grade</label><input type="text" id="grade" name="grade" required value="<?php echo htmlspecialchars($card['grade'] ?? ''); ?>"></div>
            <div class="field"><label for="section">Section</label><input type="text" id="section" name="section" required value="<?php echo htmlspecialchars($card['section'] ?? ''); ?>"></div>
            <div class="field"><label for="school_year">School Year</label><input type="text" id="school_year" name="school_year" required placeholder="e.g. 2018" value="<?php echo htmlspecialchars($card['school_year'] ?? ''); ?>"></div>
            <div class="field"><label for="card_password">Unlock Password</label><input type="text" id="card_password" name="card_password" value="<?php echo htmlspecialchars($card['card_password'] ?? ''); ?>"></div>
        </div>
        <div class="grid grid-4">
            <div class="field"><label for="teacher_name">Teacher Name</label><input type="text" id="teacher_name" name="teacher_name" value="<?php echo htmlspecialchars($card['teacher_name'] ?? ''); ?>"></div>
            <div class="field"><label for="sex">Sex</label>
                <select id="sex" name="sex">
                    <option value="">--</option>
                    <option value="M" <?php echo (($card['sex'] ?? '') === 'M') ? 'selected' : ''; ?>>Male</option>
                    <option value="F" <?php echo (($card['sex'] ?? '') === 'F') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="field"><label for="age">Age</label><input type="number" id="age" name="age" value="<?php echo htmlspecialchars($card['age'] ?? ''); ?>"></div>
            <div class="field"><label for="kebele">Kebele</label><input type="text" id="kebele" name="kebele" value="<?php echo htmlspecialchars($card['kebele'] ?? ''); ?>"></div>
        </div>
        <div class="field" style="max-width:200px;"><label for="house_no">House No.</label><input type="text" id="house_no" name="house_no" value="<?php echo htmlspecialchars($card['house_no'] ?? ''); ?>"></div>
    </div>

    <div class="card table-scroll reveal">
        <h2>Quarterly Marks</h2>
        <table class="data-table marks-table">
            <tr>
                <th style="text-align:left;">Subject</th>
                <th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Average</th><th>Conduct</th>
            </tr>
            <?php foreach ($SUBJECTS as $s): ?>
                <?php
                    $key = str_replace([' ', '&'], ['_', 'and'], $s);
                    $row = $subjectsData[$s] ?? [];
                    $vals = array_filter([$row['1'] ?? null, $row['2'] ?? null, $row['3'] ?? null, $row['4'] ?? null], fn($v) => $v !== null && $v > 0);
                    $avg = count($vals) ? array_sum($vals) / count($vals) : null;
                ?>
                <tr>
                    <td class="subject-name" style="text-align:left; font-weight:bold;"><?php echo htmlspecialchars($s); ?></td>
                    <?php foreach (["1", "2", "3", "4"] as $q): ?>
                        <td><input type="number" step="0.1" min="0" max="100" data-quarter="<?php echo $q; ?>" name="mark_<?php echo $key; ?>_<?php echo $q; ?>" value="<?php echo htmlspecialchars($row[$q] ?? ''); ?>"></td>
                    <?php endforeach; ?>
                    <td class="row-average"><?php echo $avg !== null ? number_format($avg, 1) : '-'; ?></td>
                    <td>
                        <select name="conduct_<?php echo $key; ?>">
                            <option value="">--</option>
                            <?php foreach (['A', 'B', 'C', 'D'] as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo (($conductData[$s] ?? '') === $g) ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card table-scroll reveal">
        <h2>Attendance</h2>
        <table class="data-table marks-table">
            <tr><th style="text-align:left;">Field</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th></tr>
            <?php
            $attendanceLabels = [
                'days_present' => 'Days Present',
                'days_absent' => 'Days Absent',
                'times_tardy' => 'Times Tardy',
                'total_academic_days' => 'Total Academic Days',
            ];
            foreach ($attendanceLabels as $field => $label): ?>
                <tr>
                    <td style="text-align:left; font-weight:bold;"><?php echo $label; ?></td>
                    <?php foreach (["1", "2", "3", "4"] as $q): ?>
                        <td><input type="number" name="<?php echo $field; ?>_<?php echo $q; ?>" value="<?php echo htmlspecialchars($attendanceData[$field][$q] ?? ''); ?>"></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card reveal">
        <h2>Remarks</h2>
        <textarea name="remarks"><?php echo htmlspecialchars($card['remarks'] ?? ''); ?></textarea>
    </div>

    <div class="btn-row">
        <button type="submit" class="btn">Save Report Card</button>
        <a class="btn btn-outline" href="report-cards.php">Back to List</a>
    </div>
</form>

<?php include "includes/footer.php"; ?>
