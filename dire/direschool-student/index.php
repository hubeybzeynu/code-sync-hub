<?php
$pageTitle = "Dashboard";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$school = $pdo->prepare("SELECT * FROM schools WHERE id = ? ");
$school->execute([$student['school_id']]);
$school = $school->fetch();

$sigCount = $pdo->prepare("SELECT COUNT(*) n FROM student_conduct WHERE student_id=? AND type='signature' AND school_year=? ");
$sigCount->execute([$student['id'], date('Y')]);
$sigCount = $sigCount->fetch()['n'];

// Grade is analyzed right here so Grade 8-only features (the Ministry
// Result button) only ever appear for a student who is actually Grade 8.
$isGrade8 = $student['grade'] === '8';

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h1>
    <p><?php echo htmlspecialchars($school['name'] ?? ''); ?> &middot; Grade <?php echo htmlspecialchars($student['grade']); ?> / <?php echo htmlspecialchars($student['section']); ?></p></div>

<?php if ($sigCount >= 3): ?>
    <div class="alert alert-error reveal in-view"> You have <?php echo $sigCount; ?> conduct signatures this year. Your family has been asked to meet with the school.</div><?php endif; ?>

<div class="grid grid-4 stagger-list">
    <a href="classmates.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div><h3>My School</h3><p>See your classmates and browse other grades/sections.</p></a>
    <a href="textbooks.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5C4 4.7 4.7 4 5.5 4H12v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 0 0 1.5-1.5v-13Z"/></svg></div><h3>Text Books</h3><p>Browse and preview/download your grade's textbooks.</p></a>
    <a href="report-card.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M12 20V4M20 20v-7"/><path d="M4 20h16"/></svg></div><h3>Report Card</h3><p>Marks, conduct and attendance from your teacher.</p></a>
    <?php if ($isGrade8): ?>
        <a href="ministry-result.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 21V10M20 21V10M2 10l10-6 10 6M7 10v7M11 10v7M15 10v7M19 10v7"/></svg></div><h3>Ministry Result</h3><p>Your Grade 8 national exam result.</p></a>
    <?php endif; ?>
    <a href="news.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="15" rx="1.5"/><path d="M16 8.5h5v9a2 2 0 0 1-2 2h-3"/><path d="M6.5 9h6M6.5 12h6M6.5 15h4"/></svg></div><h3>News</h3><p>Announcements from your school.</p></a>
    <a href="settings.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.4M12 18.6V21M21 12h-2.4M5.4 12H3M18 6l-1.7 1.7M7.7 16.3 6 18M18 18l-1.7-1.7M7.7 7.7 6 6"/></svg></div><h3>Settings</h3><p>Control whether others can see your age.</p></a>
    <a href="leave-school.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></div><h3>Leave School</h3><p>Moving schools? Request approval before you transfer.</p></a></div>

<?php include"includes/footer.php"; ?>
