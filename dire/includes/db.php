<?php
// includes/db.php
// Shared database connection for the whole Dire Dawa Schools platform.
// Default XAMPP settings: host=localhost, user=root, password="" (empty)

$DB_HOST = "localhost";
$DB_NAME = "direschool_db";
$DB_USER = "root";
$DB_PASS = "";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Make sure XAMPP's MySQL is running and you imported database/schema.sql. (" . $e->getMessage() . ")");
}

// Turns a stored image path into a URL that actually works, no matter
// which folder the whole project was installed under.
//
// The upload helpers save paths as root-relative, e.g. "/shared-uploads/logo.png".
// That only loads correctly if this project sits at the web server's
// document root (e.g. htdocs/direschool-portal). If it was instead placed
// in a subfolder (e.g. htdocs/dire/direschool-portal), a path starting
// with "/" always points at the server root and skips right over that
// subfolder — so the browser requests a URL that doesn't exist and the
// image looks "broken" after every upload. This rebuilds the path using
// the actual folder the current site is running from, so it resolves
// correctly either way. Full URLs typed in by an admin (http://, https://)
// are left untouched.
function assetUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^([a-z][a-z0-9+.\-]*:)?//#i', $path)) return $path;
    if ($path[0] !== '/') return $path;

    static $base = null;
    if ($base === null) {
        // The requested script always lives directly inside one of the
        // /direschool-* site folders, which are siblings of /shared-uploads.
        // That folder's parent is wherever the project was installed —
        // the document root itself, or a subfolder of it.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = rtrim(dirname($scriptDir), '/');
        if ($base === '.' || $base === '\\') $base = '';
    }
    return $base . $path;
}

// Adds any missing home-page image columns to app_settings (idempotent, keeps existing data).
function appSettingsEnsureColumns($pdo) {
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM app_settings") as $r) {
        $cols[$r['Field']] = true;
    }
    $wanted = [
        'hero_image'   => "ALTER TABLE app_settings ADD COLUMN hero_image VARCHAR(255) DEFAULT NULL",
        'about_image'  => "ALTER TABLE app_settings ADD COLUMN about_image VARCHAR(255) DEFAULT NULL",
        'cta_image'    => "ALTER TABLE app_settings ADD COLUMN cta_image VARCHAR(255) DEFAULT NULL",
        'hero_tagline' => "ALTER TABLE app_settings ADD COLUMN hero_tagline VARCHAR(255) DEFAULT NULL",
    ];
    foreach ($wanted as $col => $sql) {
        if (!isset($cols[$col])) { $pdo->exec($sql); }
    }
    return $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch();
}
