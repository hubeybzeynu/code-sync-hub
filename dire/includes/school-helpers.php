<?php
// includes/school-helpers.php
// Shared helpers for school profiles & the school website builder.
// Loaded by the portal (root), the Super Admin site and the School Admin site,
// all of which delegate their includes to the shared root includes/.

// Adds any missing school-profile / website columns to the schools table
// (idempotent, keeps existing data) — same pattern as appSettingsEnsureColumns().
function schoolEnsureColumns($pdo) {
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM schools") as $r) {
        $cols[$r['Field']] = true;
    }
    $wanted = [
        'description'      => "ALTER TABLE schools ADD COLUMN description TEXT DEFAULT NULL",
        'cover_image'      => "ALTER TABLE schools ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL",
        'website_mode'     => "ALTER TABLE schools ADD COLUMN website_mode ENUM('none','template','html') NOT NULL DEFAULT 'none'",
        'website_template' => "ALTER TABLE schools ADD COLUMN website_template VARCHAR(50) DEFAULT NULL",
        'website_html'     => "ALTER TABLE schools ADD COLUMN website_html LONGTEXT DEFAULT NULL",
    ];
    foreach ($wanted as $col => $sql) {
        if (!isset($cols[$col])) { $pdo->exec($sql); }
    }
}

// Folder that stores one school's uploaded images, e.g.
// /shared-uploads/schools/<id>/logo.png and /shared-uploads/schools/<id>/cover.jpg
function schoolImagesDir($schoolId) {
    $dir = dirname(__DIR__) . "/shared-uploads/schools/" . (int)$schoolId;
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return $dir;
}

// Saves an uploaded file as the school's logo or cover image.
// $kind is 'logo' or 'cover'. Returns the public URL path, or null on
// failure (a message is written to $err).
function saveSchoolImage($file, $schoolId, $kind, &$err) {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
    if (!in_array($ext, $allowed, true)) { $err = "Only PNG, JPG, SVG or WEBP images are allowed."; return null; }
    if ($file['size'] > 4 * 1024 * 1024) { $err = "Please keep each image under 4MB."; return null; }
    $dir = schoolImagesDir($schoolId);
    $destFile = $kind . "." . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . "/" . $destFile)) {
        return "/shared-uploads/schools/" . (int)$schoolId . "/" . $destFile;
    }
    $err = "Could not save the uploaded file. Check that /shared-uploads/schools is writable.";
    return null;
}

// The built-in website templates a school can pick from.
function schoolTemplates() {
    return [
        'classic' => [
            'name' => 'Classic',
            'desc' => 'Navy & white with a full-width cover photo, about section, latest announcements and clear calls to action.',
        ],
        'modern' => [
            'name' => 'Modern',
            'desc' => 'Bold dark hero with floating stat cards, gold accents and a confident contemporary feel.',
        ],
        'minimal' => [
            'name' => 'Minimal',
            'desc' => 'White space-first editorial layout with serif headings, thin rules and a calm, focused tone.',
        ],
    ];
}

// A short, safe fallback description for schools that never wrote one.
function schoolFallbackDescription($school) {
    $name = trim($school['name'] ?? 'This school');
    return $name . " is part of the Dire Dawa Schools network. Visit the portal to browse its sections, news and enrollment, or apply online to start a registration.";
}

// Initials for the logo chip when a school has no logo file yet.
function schoolInitials($school) {
    $words = preg_split('/\s+/', trim($school['name'] ?? 'S'));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials !== '' ? $initials : 'S';
}