<?php
// school-site.php — opens the website of a single school.
// Clicking any school card on school.php lands here. The page is a fully
// standalone document (template design OR the school's pasted HTML), so it
// deliberately does NOT include the portal header/footer.

require "includes/db.php";
require "includes/school-helpers.php";
require "includes/school-templates.php";

schoolEnsureColumns($pdo);

$slug = trim($_GET['slug'] ?? '');
$school = null;
if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE slug = ? AND status = 'active'");
    $stmt->execute([$slug]);
    $school = $stmt->fetch();
}

if (!$school) {
    header("Location: school.php");
    exit;
}

// Fresh installs / older databases: ensure the helper columns exist before
// the template touches them.
schoolEnsureColumns($pdo);
$stmt = $pdo->prepare("SELECT * FROM schools WHERE slug = ?");
$stmt->execute([$slug]);
$school = $stmt->fetch();

// Optional ?preview=templateKey — used by the admin website builder to show
// a design before it is saved. Renders that template with live school data.
$previewKey = trim($_GET['preview'] ?? '');
if ($previewKey !== '' && isset(schoolTemplates()[$previewKey])) {
    renderSchoolTemplate($pdo, $school, $previewKey);
    exit;
}

renderSchoolSite($pdo, $school);