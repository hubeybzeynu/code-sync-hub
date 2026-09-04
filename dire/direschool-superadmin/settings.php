<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Site Settings";

$error = "";
$success = "";

appSettingsEnsureColumns($pdo);
$settings = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch();
if (!$settings) {
    $pdo->exec("INSERT INTO app_settings (id, site_name, logo_path) VALUES (1, '/direschool', NULL)");
    $settings = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch();
}

// Shared-uploads lives one folder above the per-role site folders, at the
// htdocs root, so every one of the 9 sites can read the same file via the
// single absolute path /shared-uploads/<file>.
$sharedDir = __DIR__ . "/../shared-uploads";
if (!is_dir($sharedDir)) @mkdir($sharedDir, 0777, true);

// Upload one image into shared-uploads with a fixed name. Leaves the
// current stored path untouched if nothing was uploaded for this field.
function saveHomeImage($file, $fieldPrefix, $sharedDir, &$error) {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
    if (!in_array($ext, $allowed, true)) { $error = "Only PNG, JPG, SVG or WEBP images are allowed."; return null; }
    if ($file['size'] > 4 * 1024 * 1024) { $error = "Please keep each image under 4MB."; return null; }
    $destFile = $fieldPrefix . "." . $ext;
    $destPath = $sharedDir . "/" . $destFile;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return "/shared-uploads/" . $destFile;
    }
    $error = "Could not save the uploaded file. Check that /shared-uploads is writable.";
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $siteName = trim($_POST['site_name'] ?? '') ?: '/direschool';
    $logoPath = $settings['logo_path'];
    if (!empty($_FILES['logo']['name'])) {
        $p = saveHomeImage($_FILES['logo'], "logo", $sharedDir, $error);
        if ($p) $logoPath = $p;
    }

    if (!$error) {
        $heroTagline = trim($_POST['hero_tagline'] ?? '');
        $heroImage = $settings['hero_image'];
        $aboutImage = $settings['about_image'];
        $ctaImage   = $settings['cta_image'];

        // Map each DB column to its matching variable + shared-uploads prefix.
        $imgMap = [
            'hero_image'  => ['var' => 'heroImage',  'prefix' => 'hero'],
            'about_image' => ['var' => 'aboutImage', 'prefix' => 'about'],
            'cta_image'   => ['var' => 'ctaImage',   'prefix' => 'cta'],
        ];
        foreach ($imgMap as $col => $cfg) {
            if (!empty($_FILES[$col]['name'])) {
                $p = saveHomeImage($_FILES[$col], $cfg['prefix'], $sharedDir, $error);
                if ($error) break;
                if ($p) ${$cfg['var']} = $p;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare(
                "UPDATE app_settings SET site_name=?, logo_path=?, hero_image=?, about_image=?, cta_image=?, hero_tagline=? WHERE id=1"
            );
            $stmt->execute([$siteName, $logoPath, $heroImage, $aboutImage, $ctaImage, $heroTagline]);
            $success = "Settings saved.";
            $settings = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch();
        }
    }
}

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:10px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 8 0 1 0 0 16c1.1 0 2-.8 2-1.8 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-1 .8-1.8 1.8-1.8H17a4 4 0 0 0 4-4c0-4-4-6-9-6Z"/><circle cx="7.5" cy="10.5" r="1" fill="currentColor"/><circle cx="11" cy="7.5" r="1" fill="currentColor"/><circle cx="15.5" cy="9" r="1" fill="currentColor"/></svg></div>
    <h1>Site Branding &amp; Home Images</h1>
    <p>This logo and the home page images appear across every /direschool site — Super Admin, Admin, Teacher, Librarian, Sub Admin, Staff, Student and Parent — since they all read them from the same database.</p></div>

<?php if ($error): ?><div class="alert alert-error reveal in-view"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success reveal in-view"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<form method="post" action="settings.php" enctype="multipart/form-data">
    <input type="hidden" name="save" value="1">

    <div class="card reveal">
        <h2>Logo &amp; Site Name</h2>
        <div style="display:flex; justify-content:center; margin-bottom:18px;">
            <?php if ($settings['logo_path']): ?>
                <img src="<?php echo htmlspecialchars(assetUrl($settings['logo_path'])); ?>" alt="Current logo" style="width:120px; height:120px; object-fit:contain; border-radius:16px; box-shadow:var(--shadow);">
            <?php else: ?>
                <div class="stat-box" style="width:120px;">No logo set</div>
            <?php endif; ?>
        </div>
        <div class="field">
            <label>Site Name</label>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
        </div>
        <div class="field">
            <label>Upload New Logo</label>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp">
        </div>
    </div>

    <div class="card reveal">
        <h2>Home Page Hero</h2>
        <p class="help-text" style="margin-top:-8px;">This wide banner image appears at the top of the public home page, with soft fading edges on its top and bottom.</p>
        <?php if ($settings['hero_image']): ?>
            <div style="margin:6px 0 16px; border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; max-width:760px;">
                <img src="<?php echo htmlspecialchars(assetUrl($settings['hero_image'])); ?>" alt="Hero image" style="width:100%; display:block;">
            </div>
        <?php else: ?>
            <div class="stat-box" style="margin-bottom:16px;">No hero image set</div>
        <?php endif; ?>
        <div class="field">
            <label>Hero Tagline (small label above the headline)</label>
            <input type="text" name="hero_tagline" value="<?php echo htmlspecialchars($settings['hero_tagline'] ?? ''); ?>" placeholder="Admissions · Open for the new academic year">
        </div>
        <div class="field">
            <label>Upload Hero Image <span style="font-weight:400;color:var(--mute);">(wide, landscape, ~1600px wide recommended)</span></label>
            <input type="file" name="hero_image" accept=".png,.jpg,.jpeg,.webp">
        </div>
    </div>

    <div class="card reveal">
        <h2>About Section Image</h2>
        <div style="margin:6px 0 16px;">
            <?php if ($settings['about_image']): ?>
                <img src="<?php echo htmlspecialchars(assetUrl($settings['about_image'])); ?>" alt="About image" style="max-height:160px; border-radius:var(--radius); box-shadow:var(--shadow);">
            <?php else: ?>
                <div class="stat-box" style="max-width:240px;">No about image set</div>
            <?php endif; ?>
        </div>
        <div class="field">
            <label>Upload About Image</label>
            <input type="file" name="about_image" accept=".png,.jpg,.jpeg,.webp">
        </div>
    </div>

    <div class="card reveal">
        <h2>CTA / Banner Image</h2>
        <div style="margin:6px 0 16px;">
            <?php if ($settings['cta_image']): ?>
                <img src="<?php echo htmlspecialchars(assetUrl($settings['cta_image'])); ?>" alt="CTA image" style="max-height:160px; border-radius:var(--radius); box-shadow:var(--shadow);">
            <?php else: ?>
                <div class="stat-box" style="max-width:240px;">No CTA image set</div>
            <?php endif; ?>
        </div>
        <div class="field">
            <label>Upload CTA Image</label>
            <input type="file" name="cta_image" accept=".png,.jpg,.jpeg,.webp">
        </div>
    </div>

    <button type="submit" class="btn btn-full">Save Branding &amp; Images</button>
</form>

<?php include"includes/footer.php"; ?>
