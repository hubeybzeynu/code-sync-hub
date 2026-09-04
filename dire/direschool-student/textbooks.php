<?php
$pageTitle = "Text Books";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$grade = $_GET['grade'] ?? $student['grade'];

$grades = $pdo->prepare("SELECT DISTINCT grade FROM textbook_folders WHERE school_id=? ORDER BY grade");
$grades->execute([$student['school_id']]);
$grades = array_column($grades->fetchAll(), 'grade');

$books = $pdo->prepare("SELECT * FROM textbooks WHERE school_id=? AND grade=? ORDER BY subject");
$books->execute([$student['school_id'], $grade]);
$books = $books->fetchAll();

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5C4 4.7 4.7 4 5.5 4H12v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 0 0 1.5-1.5v-13Z"/></svg></div>
    <h1>Text Books</h1>
    <p>Browse and preview or download your grade's textbooks.</p></div><p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Text Books</p>

<div class="card reveal">
    <form method="get" action="textbooks.php" class="grid grid-2">
        <div class="field">
            <label>Grade</label>
            <select name="grade" onchange="this.form.submit()">
                <?php foreach ($grades as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $g === $grade ? 'selected' : ''; ?>>Grade <?php echo htmlspecialchars($g); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Find content in a book</label>
            <input type="text" placeholder="e.g. 'Unit 3 exercise 2' (coming soon)" disabled>
        </div>
    </form></div>

<div class="stagger-list"><?php foreach ($books as $b): ?>
    <div class="list-item reveal">
        <h3><?php echo htmlspecialchars($b['name']); ?></h3>
        <p><?php echo htmlspecialchars($b['subject'] ?: 'General'); ?> &middot; Grade <?php echo htmlspecialchars($b['grade']); ?></p>
        <div class="btn-row" style="justify-content:flex-start; margin-top:10px;">
            <?php if ($b['drive_link']): ?>
                <a class="btn btn-sm" href="<?php echo htmlspecialchars($b['drive_link']); ?>" target="_blank">Preview</a>
                <a class="btn btn-sm btn-outline" href="<?php echo htmlspecialchars($b['drive_link']); ?>" target="_blank">Download</a>
            <?php else: ?>
                <span class="help-text">No soft copy yet — ask the library for a physical copy.</span>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline" disabled title="Coming soon"> Find Exercise / Unit / Page</button>
        </div>
    </div><?php endforeach; ?></div><?php if (!$books): ?><div class="card reveal">No textbooks uploaded yet for this grade.</div><?php endif; ?>

<?php include"includes/footer.php"; ?>
