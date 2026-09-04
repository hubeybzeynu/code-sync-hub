<?php
// includes/header.php — Unified header for all layouts (editorial theme)
// Variables expected (set BEFORE including this file):
//   $pageTitle     — page title text
//   $admin         — current admin row (auto-detected for staff layout)
//   $student       — current student row (auto-detected for student layout)
//   $parent        — current parent row (auto-detected for parent layout)
//   No layout var = portal (public) layout

$__brand = null;
try { $__brand = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch(); } catch (Exception $e) { $__brand = null; }
$__siteName = ($__brand['site_name'] ?? '') ?: 'Dire Dawa Schools';
$__logoPath = $__brand['logo_path'] ?? '';
$__isAdmin = isset($admin) && $admin;
$__isStudent = isset($student) && $student;
$__isParent = isset($parent) && $parent;
$__isStaff = $__isAdmin;

// Detect the current area from the URL's folder path.
$__scriptDirName = basename(dirname($_SERVER['SCRIPT_NAME']));
$__areaFromDir = [
    'direschool-admin' => 'admin', 'direschool-superadmin' => 'superadmin',
    'direschool-teacher' => 'teacher', 'direschool-librarian' => 'librarian',
    'direschool-subadmin' => 'subadmin', 'direschool-staff' => 'staff',
    'direschool-student' => 'student', 'direschool-parent' => 'parent',
];
$__detectedArea = $__areaFromDir[$__scriptDirName] ?? 'public';
if (isset($__area) && $__area && $__area !== 'public') $__detectedArea = $__area;
if ($__detectedArea === 'student') { $__isStudent = true; $__isParent = false; }
elseif ($__detectedArea === 'parent') { $__isParent = true; $__isStudent = false; }
$__currentFile = basename($_SERVER['SCRIPT_NAME']);
$__nav = $__isAdmin ? navLinksForRole($admin['role']) : [];
if ($__isAdmin && $__nav && $__detectedArea !== 'public') {
    $__roleDir = str_replace('\\', '/', dirname(__DIR__)) . '/direschool-' . $__detectedArea;
    $__filtered = [];
    foreach ($__nav as $__file => $__label) {
        if (file_exists($__roleDir . '/' . $__file)) $__filtered[$__file] = $__label;
    }
    $__nav = $__filtered;
}
$__role = $__isAdmin ? $admin['role'] : ($__role ?? '');
$__assetBase = $__assetBase ?? '';
$__css = ($__assetBase ? rtrim($__assetBase, '/') . '/' : '') . 'assets/css/style.css';
$__js = ($__assetBase ? rtrim($__assetBase, '/') . '/' : '') . 'assets/js/main.js';
// Cache-bust: append the file's last-modified time so the browser always
// fetches the latest CSS/JS after an update (no stale footer/theme).
$__webRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
function __assetVersion($webPath, $webRoot) {
    $clean = preg_replace('#^(\.\./)+#', '', $webPath);
    $file = $webRoot . '/' . $clean;
    return is_file($file) ? filemtime($file) : '1';
}
$__css = $__css . '?v=' . __assetVersion($__css, $__webRoot);
$__js = $__js . '?v=' . __assetVersion($__js, $__webRoot);
?><!DOCTYPE html><html lang="en"><head>
    <script>
    (function () {
        try {
            var t = localStorage.getItem("dire-theme");
            if (t === "dark") document.documentElement.setAttribute("data-theme", "dark");
        } catch (e) {}
    })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - " . htmlspecialchars($__siteName) : htmlspecialchars($__siteName); ?></title>
    <link rel="stylesheet" href="<?php echo $__css; ?>">
    <noscript><style>.reveal{opacity:1!important;transform:none!important;} .stagger-list .list-item,.stagger-list .feature-card{opacity:1!important;animation:none!important;}</style></noscript></head><body>
<?php if ($__isStaff): ?>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon"><?php if ($__logoPath): ?><img src="<?php echo htmlspecialchars(assetUrl($__logoPath)); ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:4px;"><?php else: ?><svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg><?php endif; ?></span>
                <span><?php echo htmlspecialchars(roleLabel($__role)); ?></span>
            </div>
            <div class="sidebar-role"><?php echo roleBadge($__role); ?></div>
            <nav class="sidebar-nav">
                <?php foreach ($__nav as $file => $label): ?>
                    <a href="<?php echo $file; ?>" class="<?php echo $__currentFile === $file ? 'active' : ''; ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="who">
                    <div class="who-name"><?php echo htmlspecialchars($admin['full_name'] ?: $admin['email']); ?></div>
                    <div class="who-role"><?php echo isSuperAdmin($admin) ? 'All Schools' : ''; ?></div>
                </div>
                <a class="logout-link" href="logout.php">Log out</a>
            </div>
        </aside>
        <div class="main-area">
            <header class="topbar">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"></button>
                <h1><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : "Dashboard"; ?></h1>
                <button type="button" class="theme-toggle" onclick="toggleSiteTheme()" style="margin-left:auto;"><span class="theme-toggle-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg></span></button>
            </header>
            <main class="content">
<?php else: ?>
    <?php if ($__detectedArea === 'student' || $__detectedArea === 'parent'): /* minimal portal header for student/parent sites */ ?>
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <span class="brand-icon"><?php if ($__logoPath): ?><img src="<?php echo htmlspecialchars(assetUrl($__logoPath)); ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:4px;"><?php else: ?><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg><?php endif; ?></span>
                <span><?php echo htmlspecialchars($__siteName); ?></span>
            </a>
            <nav class="main-nav">
                <?php if ($__isStudent && $student): ?>
                    <a href="index.php">Dashboard</a>
                    <a href="classmates.php">Classmates</a>
                    <a href="textbooks.php">Textbooks</a>
                    <a href="report-card.php">Report Card</a>
                    <a href="news.php">News</a>
                    <a href="settings.php">Settings</a>
                    <a href="leave-school.php">Leave School</a>
                    <a href="logout.php">Log out</a>
                <?php elseif ($__isStudent): ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php elseif ($__isParent && $parent): ?>
                    <a href="index.php">Dashboard</a>
                    <a href="chat.php">Messages</a>
                    <a href="logout.php">Log out</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
                <button type="button" class="theme-toggle" onclick="toggleSiteTheme()"><span class="theme-toggle-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg></span></button>
            </nav>
        </div>
    </header>
    <main class="page-content">
    <?php else: /* Editorial public portal header */ ?>
    <div class="utility-strip"><div class="utility-inner">
        <a href="school.php">Schools</a>
        <a href="report-card.php">Report Card</a>
        <a href="exam-result.php">Exam Result</a>
        <a href="ministry-result.php">Grade 8 Result</a>
        <a href="portals.php">Login Portals</a>
    </div></div>
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <span class="brand-icon"><?php if ($__logoPath): ?><img src="<?php echo htmlspecialchars(assetUrl($__logoPath)); ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:4px;"><?php else: ?><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg><?php endif; ?></span>
                <span><?php echo htmlspecialchars($__siteName); ?></span>
            </a>
            <div class="header-actions">
                <button class="menu-trigger" id="menuTrigger" type="button" aria-label="Open menu">
                    <span class="mt-lines"><span></span><span></span></span> Menu
                </button>
            </div>
        </div>
    </header>
    <div class="mega-menu" id="megaMenu" aria-hidden="true">
        <div class="mm-inner">
            <div class="mm-top">
                <span class="mm-brand"><?php echo htmlspecialchars($__siteName); ?></span>
                <button class="mm-close" id="mmClose" type="button" aria-label="Close menu">&times;</button>
            </div>
            <div class="mm-grid">
                <div class="mm-col">
                    <h4>Platform</h4>
                    <a href="index.php"><span class="idx">01</span> Home</a>
                    <a href="school.php"><span class="idx">02</span> Browse Schools</a>
                    <a href="portals.php"><span class="idx">03</span> Login Portals</a>
                </div>
                <div class="mm-col">
                    <h4>Lookup</h4>
                    <a href="report-card.php"><span class="idx">04</span> Report Card</a>
                    <a href="exam-result.php"><span class="idx">05</span> Exam Result</a>
                    <a href="ministry-result.php"><span class="idx">06</span> Grade 8 (Ministry)</a>
                </div>
                <div class="mm-col">
                    <h4>Roles</h4>
                    <a href="direschool-superadmin/login.php"><span class="idx">A</span> Super Admin</a>
                    <a href="direschool-admin/login.php"><span class="idx">B</span> Admin</a>
                    <a href="direschool-teacher/login.php"><span class="idx">C</span> Teacher</a>
                    <a href="direschool-student/login.php"><span class="idx">D</span> Student</a>
                    <a href="direschool-parent/login.php"><span class="idx">E</span> Parent</a>
                </div>
                <div class="mm-col">
                    <h4>Connect</h4>
                    <a href="about.php"><span class="idx">07</span> About</a>
                    <a href="contact.php"><span class="idx">08</span> Contact</a>
                    <a href="faq.php"><span class="idx">09</span> FAQ</a>
                </div>
            </div>
            <div class="mm-bottom">
                <span class="theme-toggle" onclick="toggleSiteTheme()"><span class="theme-toggle-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg></span> Toggle theme</span>
            </div>
        </div>
    </div>
    <main class="page-content">
    <?php endif; ?>
<?php endif; ?>
