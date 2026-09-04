<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['librarian']);
$admin = currentAdmin($pdo);
$pageTitle = "Dashboard";
$mySchool = (int)$admin['school_id'];

$counts = [
    'folders' => $pdo->query("SELECT COUNT(*) c FROM textbook_folders WHERE school_id=$mySchool")->fetch()['c'],
    'books' => $pdo->query("SELECT COUNT(*) c FROM textbooks WHERE school_id=$mySchool")->fetch()['c'],
];

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($admin['full_name'] ?: 'Librarian'); ?></h1>
    <p>Keep your school's textbook library organized so students and staff can always find what they need.</p></div>

<div class="grid grid-2">
    <a href="library.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['folders']; ?></div><div class="stat-label">Grade Folders</div></a>
    <a href="library.php" class="stat-box reveal"><div class="stat-value"><?php echo $counts['books']; ?></div><div class="stat-label">Books</div></a></div>

<h2 style="margin-top:34px;">What you can do here</h2><div class="grid grid-2 stagger-list">
    <a href="library.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
        <h3>Grade Folders &amp; Books</h3>
        <p>Create a folder per grade, then add books inside — grade and type carry over automatically from the folder. Attach a Google Drive link so students can preview or download.</p>
    </a>
    <a href="chat.php" class="feature-card reveal">
        <div class="feature-icon"><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg></div>
        <h3>Messages</h3>
        <p>Chat with your school's Admin or other staff.</p>
    </a></div>

<?php include"includes/footer.php"; ?>
