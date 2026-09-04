<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin', 'admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Report Cards";
$isSuper = isSuperAdmin($admin);
$myScopedSchool = (int)$admin['school_id'];

if (isset($_GET['delete'])) {
    $cond = $isSuper ? "id=? " : "id=? AND school_id=" . $myScopedSchool;
    $stmt = $pdo->prepare("DELETE FROM report_cards WHERE $cond");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: report-cards.php");
    exit;
}

$fSchool = $isSuper ? (isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0) : $myScopedSchool;
$fGrade = trim($_GET['grade'] ?? '');
$fSection = trim($_GET['section'] ?? '');
$fYear = trim($_GET['school_year'] ?? '');

$schools = $isSuper ? $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll() : [];

$sql = "SELECT rc.*, st.full_name, st.student_no, sc.name AS school_name
        FROM report_cards rc
        JOIN students st ON st.id = rc.student_id
        JOIN schools sc ON sc.id = rc.school_id
        WHERE 1=1";
$params = [];
if ($fSchool) { $sql .= " AND rc.school_id = ? "; $params[] = $fSchool; }
if ($fGrade !== '') { $sql .= " AND rc.grade = ? "; $params[] = $fGrade; }
if ($fSection !== '') { $sql .= " AND rc.section = ? "; $params[] = $fSection; }
if ($fYear !== '') { $sql .= " AND rc.school_year = ? "; $params[] = $fYear; }
$sql .= " ORDER BY sc.name, rc.grade, rc.section, st.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();

include "includes/header.php";
?>

<div class="card reveal">
    <h2>Filter</h2>
    <form method="get" action="report-cards.php" class="grid grid-4">
        <?php if ($isSuper): ?>
            <div class="field">
                <label>School</label>
                <select name="school_id" onchange="this.form.submit()">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $fSchool == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field"><label>Grade</label><input type="text" name="grade" value="<?php echo htmlspecialchars($fGrade); ?>"></div>
        <div class="field"><label>Section</label><input type="text" name="section" value="<?php echo htmlspecialchars($fSection); ?>"></div>
        <div class="field"><label>School Year</label><input type="text" name="school_year" value="<?php echo htmlspecialchars($fYear); ?>"></div>
        <div class="field" style="grid-column: 1 / -1;">
            <button type="submit" class="btn">Apply Filters</button>
            <a class="btn btn-outline" href="report-cards.php">Reset</a>
        </div>
    </form>
</div>

<div class="btn-row" style="margin-bottom:18px;">
    <a class="btn" href="report-card-edit.php">+ New Report Card</a>
</div>

<div class="card table-scroll reveal">
    <table class="data-table">
        <tr>
            <th>Student</th><th>School</th><th class="center">Grade</th><th class="center">Section</th><th class="center">Year</th><th class="center">Password</th><th class="center">Actions</th>
        </tr>
        <?php foreach ($cards as $c): ?>
            <tr>
                <td><?php echo htmlspecialchars($c['full_name']); ?> (<?php echo htmlspecialchars($c['student_no'] ?? '-'); ?>)</td>
                <td><?php echo htmlspecialchars($c['school_name']); ?></td>
                <td class="center"><?php echo htmlspecialchars($c['grade']); ?></td>
                <td class="center"><?php echo htmlspecialchars($c['section']); ?></td>
                <td class="center"><?php echo htmlspecialchars($c['school_year']); ?></td>
                <td class="center"><?php echo htmlspecialchars($c['card_password'] ?: '-'); ?></td>
                <td class="center">
                    <div class="table-actions">
                        <a class="btn btn-sm btn-outline" href="report-card-edit.php?id=<?php echo $c['id']; ?>">Edit</a>
                        <a class="btn btn-sm btn-danger confirm-delete" href="report-cards.php?delete=<?php echo $c['id']; ?>">Delete</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$cards): ?>
            <tr><td colspan="7">No report cards found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include "includes/footer.php"; ?>
