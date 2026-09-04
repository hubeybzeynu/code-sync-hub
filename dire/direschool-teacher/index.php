<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['teacher']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($admin['full_name'] ?: 'Teacher'); ?></h1>
    <p>Record how your students are doing — conduct, attendance, and their quarterly marks.</p></div>

<div class="grid grid-2 stagger-list">
    <a href="conduct.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.3-7 9.5-4-1.2-7-4.9-7-9.5V6l7-3Z"/><path d="M9 12l2 2 4-4"/></svg></div>
        <h3>Conduct &amp; Attendance</h3>
        <p>Log a note, warning or signature for a student, and mark today's attendance. 3 signatures in a year flags a required family meeting.</p>
    </a>
    <a href="report-cards.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M12 20V4M20 20v-7"/><path d="M4 20h16"/></svg></div>
        <h3>Report Card Marks</h3>
        <p>Enter quarterly subject marks — averages, rank and promotion status are calculated for you.</p>
    </a>
    <a href="chat.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div>
        <h3>Messages</h3>
        <p>Chat with your school's Admin, other staff, or a student's parent.</p>
    </a></div>

<?php include"includes/footer.php"; ?>
