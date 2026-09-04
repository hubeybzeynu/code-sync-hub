<?php
$pageTitle = "My Grade & Section";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$grade = $_GET['grade'] ?? $student['grade'];
$section = $_GET['section'] ?? $student['section'];

$grades = $pdo->prepare("SELECT DISTINCT grade FROM sections WHERE school_id=? ORDER BY grade");
$grades->execute([$student['school_id']]);
$grades = array_column($grades->fetchAll(), 'grade');

$sections = $pdo->prepare("SELECT section FROM sections WHERE school_id=? AND grade=? ORDER BY section");
$sections->execute([$student['school_id'], $grade]);
$sections = array_column($sections->fetchAll(), 'section');

$classmates = $pdo->prepare("SELECT * FROM students WHERE school_id=? AND grade=? AND section=? AND reg_status='active' ORDER BY full_name");
$classmates->execute([$student['school_id'], $grade, $section]);
$classmates = $classmates->fetchAll();

$school = $pdo->prepare("SELECT * FROM schools WHERE id = ? ");
$school->execute([$student['school_id']]);
$school = $school->fetch();

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:20px; padding-bottom:26px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome to <?php echo htmlspecialchars($school['name'] ?? 'your school'); ?></h1>
    <p>Discover your grade and section, and see who's in class with you.</p></div>

<div class="card reveal">
    <form method="get" action="classmates.php" class="grid grid-2">
        <div class="field">
            <label>Grade</label>
            <select name="grade" onchange="this.form.submit()">
                <?php foreach ($grades as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $g === $grade ? 'selected' : ''; ?>>Grade <?php echo htmlspecialchars($g); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Section</label>
            <select name="section" onchange="this.form.submit()">
                <?php foreach ($sections as $sec): ?>
                    <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $sec === $section ? 'selected' : ''; ?>>Section <?php echo htmlspecialchars($sec); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form></div>

<table class="data-table reveal">
    <tr><th>Name</th><th>Gender</th><th>Age</th></tr>
    <?php foreach ($classmates as $c): ?>
        <tr>
            <td style="text-align:left;"><?php echo htmlspecialchars($c['full_name']); ?><?php echo (int)$c['id'] === (int)$student['id'] ? ' (You)' : ''; ?></td>
            <td><?php echo htmlspecialchars($c['gender'] ?? '-'); ?></td>
            <td><?php echo ($c['hide_age'] && (int)$c['id'] !== (int)$student['id']) ? 'Hidden' : htmlspecialchars($c['age'] ?? '-'); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$classmates): ?><tr><td colspan="3">No students found in this grade/section.</td></tr><?php endif; ?></table>

<?php include"includes/footer.php"; ?>
