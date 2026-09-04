<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['subadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($admin['full_name'] ?: 'Sub Admin'); ?></h1>
    <p>Keep families informed and help the school stay on top of student conduct.</p></div>

<div class="grid grid-3 stagger-list">
    <a href="news.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="15" rx="1.5"/><path d="M16 8.5h5v9a2 2 0 0 1-2 2h-3"/><path d="M6.5 9h6M6.5 12h6M6.5 15h4"/></svg></div>
        <h3>Post News</h3>
        <p>Share announcements to your school's student portal.</p>
    </a>
    <a href="conduct.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.3-7 9.5-4-1.2-7-4.9-7-9.5V6l7-3Z"/><path d="M9 12l2 2 4-4"/></svg></div>
        <h3>Conduct &amp; Warnings</h3>
        <p>Record a note, warning or signature for a student, and mark attendance.</p>
    </a>
    <a href="chat.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="9" r="2.6"/><path d="M15.5 14c2.6.3 4.5 2.6 4.5 5.4"/></svg></div>
        <h3>Contact a Family</h3>
        <p>Message a parent directly, or chat with your school's staff.</p>
    </a></div>

<?php include"includes/footer.php"; ?>
