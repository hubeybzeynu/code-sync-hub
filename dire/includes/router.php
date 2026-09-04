<?php
// router.php — Unified front controller
// All requests go through index.php which includes this file.
// URL patterns:
//   /dire/                    -> public home
//   /dire/school.php          -> public pages
//   /dire/login.php           -> staff/student/parent login (role-aware)
//   /dire/{role}/             -> role-specific dashboards
//   /dire/{role}/{page}.php   -> role-specific feature pages
//
// We keep the original file-per-page structure for compatibility, but all
// shared includes (db, auth, header, footer, assets) now live in ONE place.

// Determine the current "area" from the request path.
// e.g. direschool-admin/students.php -> area = 'admin', page = 'students.php'
$__scriptName = basename($_SERVER['SCRIPT_NAME']);
$__scriptDir = dirname($_SERVER['SCRIPT_NAME']);

// Map old folder names to clean role keys
$__folderToRole = [
    'direschool-superadmin' => 'superadmin',
    'direschool-admin'      => 'admin',
    'direschool-teacher'    => 'teacher',
    'direschool-librarian'  => 'librarian',
    'direschool-subadmin'   => 'subadmin',
    'direschool-staff'      => 'staff',
    'direschool-student'    => 'student',
    'direschool-parent'     => 'parent',
];

$__baseFolder = basename(rtrim($__scriptDir, '/\\'));
$__area = $__folderToRole[$__baseFolder] ?? 'public';

// Store for pages to read
$GLOBALS['__area'] = $__area;

// Helper to build URLs from the unified root
function url($path = '') {
    $base = '/dire';
    if ($path !== '' && strpos($path, '/') === 0) {
        return $base . $path;
    }
    return $base . '/' . $path;
}
