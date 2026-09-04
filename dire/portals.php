<?php
$pageTitle = "Login Portals";
include"includes/db.php";
include"includes/header.php";

$portals = [
    ['name' => 'Super Admin', 'icon' => '', 'url' => 'direschool-superadmin/login.php', 'color' => '#D9A94E', 'desc' => 'Manage every school, verify transfers, ban/unblock schools, Ministry results.'],
    ['name' => 'Admin', 'icon' => '', 'url' => 'direschool-admin/login.php', 'color' => '#4F7CFF', 'desc' => 'Run one school: registrations, teachers, students, report cards, conduct.'],
    ['name' => 'Teacher', 'icon' => '', 'url' => 'direschool-teacher/login.php', 'color' => '#8B5CF6', 'desc' => 'Record conduct, attendance, and enter report card marks.'],
    ['name' => 'Librarian', 'icon' => '', 'url' => 'direschool-librarian/login.php', 'color' => '#EC4899', 'desc' => 'Manage textbook folders and books by grade.'],
    ['name' => 'Sub Admin', 'icon' => '', 'url' => 'direschool-subadmin/login.php', 'color' => '#28C7C7', 'desc' => 'Post news, record conduct/warnings, contact families.'],
    ['name' => 'Staff', 'icon' => '', 'url' => 'direschool-staff/login.php', 'color' => '#64748B', 'desc' => 'Approve registrations and payments, chat with staff.'],
    ['name' => 'Student', 'icon' => '', 'url' => 'direschool-student/login.php', 'color' => '#F59E0B', 'desc' => 'Register, view textbooks, report card, and Ministry results.'],
    ['name' => 'Parent', 'icon' => '', 'url' => 'direschool-parent/login.php', 'color' => '#22C55E', 'desc' => "Link a child and follow their attendance, conduct and report card."],
];
?>

<h1>Login Portals</h1><p class="breadcrumb">Every role in the Dire Dawa Schools system has its own dedicated site â€” all connected to the same shared database.</p>

<div class="grid grid-4">
    <?php foreach ($portals as $p): ?>
        <a href="<?php echo $p['url']; ?>" class="list-item" style="border-top:4px solid <?php echo $p['color']; ?>;">
            <h3><?php echo $p['icon']; ?><?php echo htmlspecialchars($p['name']); ?></h3>
            <p><?php echo htmlspecialchars($p['desc']); ?></p>
        </a>
    <?php endforeach; ?></div>

<?php include"includes/footer.php"; ?>
