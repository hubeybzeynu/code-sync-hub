<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['staff']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";
$mySchool = (int)$admin['school_id'];

$counts = [
    'pending_reg' => $pdo->query("SELECT COUNT(*) c FROM students WHERE school_id=$mySchool AND reg_status IN ('pending_review', 'pending_payment')")->fetch()['c'],
];

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($admin['full_name'] ?: 'Staff'); ?></h1>
    <p>Help new students get set up, and stay connected with the rest of the school.</p></div>

<div class="grid grid-2">
    <a href="registrations.php" class="stat-box reveal"><div class="stat-value" style="color:#C7860B;"><?php echo $counts['pending_reg']; ?></div><div class="stat-label">Pending Registrations</div></a></div>

<h2 style="margin-top:34px;">What you can do here</h2><div class="grid grid-3 stagger-list">
    <a href="registrations.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5"/><path d="M3 12.5h18"/></svg></div>
        <h3>Registrations</h3>
        <p>Review new sign-ups and confirm payment before an account is activated.</p>
    </a>
    <a href="leave-requests.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></div>
        <h3>Leave Requests</h3>
        <p>Approve a student leaving your school before they transfer elsewhere.</p>
    </a>
    <a href="chat.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div>
        <h3>Messages</h3>
        <p>Chat with teachers and admins at your school.</p>
    </a></div>

<?php include"includes/footer.php"; ?>
