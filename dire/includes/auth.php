<?php
// includes/auth.php
// Unified session-based authentication for ALL roles.
// Each role's pages call: requireStaffAuth($sessionName) or requireStudentAuth() / requireParentAuth().

// ---- Staff auth (superadmin, admin, subadmin, teacher, librarian, staff) ----
function initStaffSession($sessionName = 'dire_staff') {
    if (session_status() === PHP_SESSION_NONE) {
        session_name($sessionName);
        session_start();
    }
}

function staffLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireStaffLogin() {
    if (!staffLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireStaffRole($roles) {
    requireStaffLogin();
    global $pdo;
    $admin = currentAdmin($pdo);
    if (!$admin || !in_array($admin['role'], (array)$roles, true)) {
        header("Location: index.php?denied=1");
        exit;
    }
}

function currentAdmin($pdo) {
    if (!staffLoggedIn()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $cached = $stmt->fetch();
    return $cached;
}

function isSuperAdmin($admin) {
    return $admin && $admin['role'] === 'superadmin';
}

function canManage($admin, $schoolId = null, $grade = null, $section = null) {
    if (!$admin) return false;
    if (isSuperAdmin($admin)) return true;
    if ($admin['school_id'] !== null && $schoolId !== null && (int)$admin['school_id'] !== (int)$schoolId) return false;
    if ($admin['grade'] !== null && $grade !== null && $admin['grade'] !== $grade) return false;
    if ($admin['section'] !== null && $section !== null && $admin['section'] !== $section) return false;
    return true;
}

function roleLabel($role) {
    $map = [
        'superadmin' => 'Super Admin', 'admin' => 'Admin', 'subadmin' => 'Sub Admin',
        'teacher' => 'Teacher', 'librarian' => 'Librarian', 'staff' => 'Staff',
    ];
    return $map[$role] ?? ucfirst($role);
}

function roleBadge($role) {
    return '<span class="role-badge role-' . htmlspecialchars($role) . '">' . htmlspecialchars(roleLabel($role)) . '</span>';
}

// Backward-compatible aliases (old page code used these names)
function isLoggedIn() { return staffLoggedIn(); }
function requireLogin() { requireStaffLogin(); }
function requireRole($roles) { requireStaffRole($roles); }

// ---- Student auth ----
function initStudentSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('dire_student');
        session_start();
    }
}

function studentLoggedIn() {
    return isset($_SESSION['student_id']);
}

function requireStudentLogin() {
    if (!studentLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function currentStudent($pdo) {
    if (!studentLoggedIn()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $cached = $stmt->fetch();
    return $cached;
}

// ---- Parent auth ----
function initParentSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('dire_parent');
        session_start();
    }
}

function parentLoggedIn() {
    return isset($_SESSION['parent_id']);
}

function requireParentLogin() {
    if (!parentLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function currentParent($pdo) {
    if (!parentLoggedIn()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare("SELECT * FROM parents WHERE id = ?");
    $stmt->execute([$_SESSION['parent_id']]);
    $cached = $stmt->fetch();
    return $cached;
}

// ---- Navigation links for staff roles ----
function navLinksForRole($role) {
    $all = [
        'index.php'            => ['label' => 'Dashboard', 'roles' => ['superadmin', 'admin', 'subadmin', 'teacher', 'librarian', 'staff']],
        'schools.php'          => ['label' => 'Schools', 'roles' => ['superadmin']],
        'transfers.php'        => ['label' => 'Transfer Requests', 'roles' => ['superadmin']],
        'school-messages.php'  => ['label' => 'Messages', 'roles' => ['superadmin']],
        'registrations.php'    => ['label' => 'Registrations', 'roles' => ['admin', 'staff']],
        'students.php'         => ['label' => 'Students', 'roles' => ['superadmin', 'admin']],
        'teachers.php'         => ['label' => 'Teachers', 'roles' => ['admin']],
        'conduct.php'          => ['label' => 'Conduct & Attendance', 'roles' => ['admin', 'subadmin', 'teacher']],
        'report-cards.php'     => ['label' => 'Report Cards', 'roles' => ['superadmin', 'admin', 'teacher']],
        'ministry-results.php' => ['label' => 'Ministry Results', 'roles' => ['superadmin']],
        'exam-results.php'     => ['label' => 'Exam Results', 'roles' => ['superadmin', 'admin']],
        'library.php'          => ['label' => 'Library', 'roles' => ['librarian', 'admin']],
        'news.php'             => ['label' => 'News', 'roles' => ['admin', 'subadmin', 'superadmin']],
        'send-message.php'     => ['label' => 'Message Super Admin', 'roles' => ['admin', 'subadmin', 'staff']],
        'chat.php'             => ['label' => 'Messages / Chat', 'roles' => ['admin', 'subadmin', 'teacher', 'librarian', 'staff']],
        'admins.php'           => ['label' => 'Staff Accounts', 'roles' => ['superadmin', 'admin']],
        'settings.php'         => ['label' => 'Site Settings', 'roles' => ['superadmin']],
        'school-website.php'   => ['label' => 'School Profile & Website', 'roles' => ['admin']],
    ];
    $out = [];
    foreach ($all as $file => $item) {
        if (in_array($role, $item['roles'], true)) $out[$file] = $item['label'];
    }
    return $out;
}
