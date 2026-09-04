<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";

$counts = [
    'schools' => $pdo->query("SELECT COUNT(*) c FROM schools")->fetch()['c'],
    'blocked' => $pdo->query("SELECT COUNT(*) c FROM schools WHERE status='blocked'")->fetch()['c'],
    'transfers' => $pdo->query("SELECT COUNT(*) c FROM school_transfer_requests WHERE status='pending'")->fetch()['c'],
    'messages' => $pdo->query("SELECT COUNT(*) c FROM school_messages WHERE status='unread'")->fetch()['c'],
];

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome to /direschool Super Admin</h1>
    <p>You oversee every school in the system: create schools, assign their Admin, verify transfers, and keep an eye on the whole network — without reaching into any single school's day-to-day.</p></div>

<?php if (isset($_GET['denied'])): ?>
    <div class="alert alert-error reveal in-view">You don't have access to that page.</div><?php endif; ?>

<div class="grid grid-4">
    <a href="schools.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['schools']; ?></div><div class="stat-label">Schools</div></a>
    <a href="schools.php?status=blocked" class="stat-box reveal"><div class="stat-value" style="color:#E5484D;"><?php echo $counts['blocked']; ?></div><div class="stat-label">Blocked Schools</div></a>
    <a href="transfers.php" class="stat-box reveal"><div class="stat-value" style="color:#C7860B;"><?php echo $counts['transfers']; ?></div><div class="stat-label">Pending Transfers</div></a>
    <a href="school-messages.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['messages']; ?></div><div class="stat-label">Unread Messages</div></a></div>

<h2 style="margin-top:34px;">What you can do here</h2><div class="grid grid-3 stagger-list">
    <a href="schools.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div>
        <h3>Schools</h3>
        <p>Create schools, manage their grade/section list, and ban or unblock a school with a written reason.</p>
    </a>
    <a href="admins.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div>
        <h3>School Admins</h3>
        <p>The only staff accounts you create — assign one Admin to each school. That Admin then builds their own team.</p>
    </a>
    <a href="transfers.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></div>
        <h3>Transfer Requests</h3>
        <p>Verify a student moving between schools — their promotion/detention history is shown before you decide.</p>
    </a>
    <a href="school-messages.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div>
        <h3>Messages</h3>
        <p>Read messages schools send you directly.</p>
    </a>
    <a href="ministry-results.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 21V10M20 21V10M2 10l10-6 10 6M7 10v7M11 10v7M15 10v7M19 10v7"/></svg></div>
        <h3>Ministry Results</h3>
        <p>Enter or import Grade 8 national exam results.</p>
    </a>
    <a href="news.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="15" rx="1.5"/><path d="M16 8.5h5v9a2 2 0 0 1-2 2h-3"/><path d="M6.5 9h6M6.5 12h6M6.5 15h4"/></svg></div>
        <h3>News</h3>
        <p>Post a system-wide announcement to every school's portal.</p>
    </a>
    <a href="settings.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 8 0 1 0 0 16c1.1 0 2-.8 2-1.8 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-1 .8-1.8 1.8-1.8H17a4 4 0 0 0 4-4c0-4-4-6-9-6Z"/><circle cx="7.5" cy="10.5" r="1" fill="currentColor"/><circle cx="11" cy="7.5" r="1" fill="currentColor"/><circle cx="15.5" cy="9" r="1" fill="currentColor"/></svg></div>
        <h3>Site Branding</h3>
        <p>Change the logo shown across all 8 /direschool sites.</p>
    </a></div>

<?php include"includes/footer.php"; ?>
