<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";
$mySchool = (int)$admin['school_id'];

$schoolName = $pdo->prepare("SELECT name FROM schools WHERE id=? ");
$schoolName->execute([$mySchool]);
$schoolName = $schoolName->fetch()['name'] ?? 'Your School';

$counts = [
    'students' => $pdo->query("SELECT COUNT(*) c FROM students WHERE school_id=$mySchool")->fetch()['c'],
    'teachers' => $pdo->query("SELECT COUNT(*) c FROM admins WHERE role='teacher' AND school_id=$mySchool")->fetch()['c'],
    'pending_reg' => $pdo->query("SELECT COUNT(*) c FROM students WHERE school_id=$mySchool AND reg_status IN ('pending_review', 'pending_payment')")->fetch()['c'],
    'reportcards' => $pdo->query("SELECT COUNT(*) c FROM report_cards WHERE school_id=$mySchool")->fetch()['c'],
];

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome to <?php echo htmlspecialchars($schoolName); ?></h1>
    <p>Run every part of your school from here: registrations, staff, students, report cards, conduct and the library.</p></div>

<div class="grid grid-4">
    <a href="students.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['students']; ?></div><div class="stat-label">Students</div></a>
    <a href="admins.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['teachers']; ?></div><div class="stat-label">Teachers</div></a>
    <a href="registrations.php" class="stat-box reveal"><div class="stat-value" style="color:#C7860B;"><?php echo $counts['pending_reg']; ?></div><div class="stat-label">Pending Registrations</div></a>
    <a href="report-cards.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['reportcards']; ?></div><div class="stat-label">Report Cards</div></a></div>

<h2 style="margin-top:34px;">What you can do here</h2><div class="grid grid-3 stagger-list">
    <a href="registrations.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5"/><path d="M3 12.5h18"/></svg></div><h3>Registrations</h3><p>Review new sign-ups, assign a section, confirm payment, or request a transfer for a student already in the system.</p></a>
    <a href="students.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9 12 4l10 5-10 5L2 9Z"/><path d="M6 11.5V17c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-5.5"/><path d="M22 9v6.5"/></svg></div><h3>Students</h3><p>Add, edit and manage every student at your school.</p></a>
    <a href="admins.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="9" r="2.6"/><path d="M15.5 14c2.6.3 4.5 2.6 4.5 5.4"/></svg></div><h3>Staff Accounts</h3><p>Add a Sub Admin or Teacher (unlimited), plus one Librarian and one Staff account.</p></a>
    <a href="conduct.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.3-7 9.5-4-1.2-7-4.9-7-9.5V6l7-3Z"/><path d="M9 12l2 2 4-4"/></svg></div><h3>Conduct &amp; Attendance</h3><p>Record notes, warnings and signatures — 3 signatures flags a family meeting.</p></a>
    <a href="report-cards.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M12 20V4M20 20v-7"/><path d="M4 20h16"/></svg></div><h3>Report Cards</h3><p>Enter quarterly marks; promotion status is calculated automatically.</p></a>
    <a href="library.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5C4 4.7 4.7 4 5.5 4H12v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 0 0 1.5-1.5v-13Z"/></svg></div><h3>Library</h3><p>Organize textbooks into grade folders.</p></a>
    <a href="news.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="15" rx="1.5"/><path d="M16 8.5h5v9a2 2 0 0 1-2 2h-3"/><path d="M6.5 9h6M6.5 12h6M6.5 15h4"/></svg></div><h3>News</h3><p>Post announcements to your students' portal.</p></a>
    <a href="send-message.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div><h3>Message Super Admin</h3><p>Reach the Super Admin directly.</p></a>
    <a href="chat.php" class="feature-card reveal"><div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div><h3>Messages</h3><p>Chat with your staff and linked parents.</p></a></div>

<?php include"includes/footer.php"; ?>
