<?php
require"includes/db.php";
require"includes/auth.php";
require dirname(__DIR__) . "/includes/school-helpers.php";
requireRole(['admin']);
$admin = currentAdmin($pdo);
$pageTitle = "School Profile & Website";

schoolEnsureColumns($pdo);
$templates = schoolTemplates();

$schoolId = (int)$admin['school_id'];
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id=?");
$stmt->execute([$schoolId]);
$school = $stmt->fetch();
if (!$school) {
    die("No school is linked to this admin account.");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $description = trim($_POST['description'] ?? '');
    $logo = trim($_POST['logo_url'] ?? '');
    $websiteMode = in_array($_POST['website_mode'] ?? 'none', ['none', 'template', 'html'], true) ? $_POST['website_mode'] : 'none';
    $websiteTemplate = isset(schoolTemplates()[$_POST['website_template'] ?? '']) ? $_POST['website_template'] : null;
    $websiteHtml = $_POST['website_html'] ?? '';

    $cover = $school['cover_image'];
    if (!empty($_FILES['cover_image']['name'])) {
        $p = saveSchoolImage($_FILES['cover_image'], $schoolId, 'cover', $error);
        if ($p) $cover = $p;
    }
    if (!empty($_FILES['logo_file']['name'])) {
        $p = saveSchoolImage($_FILES['logo_file'], $schoolId, 'logo', $error);
        if ($p) $logo = $p;
    }

    if (!$error) {
        $stmt = $pdo->prepare(
            "UPDATE schools SET description=?, logo_url=?, cover_image=?, website_mode=?, website_template=?, website_html=? WHERE id=?"
        );
        $stmt->execute([$description ?: null, $logo ?: null, $cover, $websiteMode, $websiteTemplate, $websiteHtml ?: null, $schoolId]);
        $success = "School profile and website saved.";
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id=?");
        $stmt->execute([$schoolId]);
        $school = $stmt->fetch();
    }
}

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:10px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div>
    <h1><?php echo htmlspecialchars($school['name']); ?></h1>
    <p>Set how your school looks on the public listing and build the website visitors see when they click your card.</p></div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<form method="post" action="school-website.php" enctype="multipart/form-data">
    <input type="hidden" name="save_profile" value="1">

    <div class="card">
        <h2>1. School Card on the Public Listing</h2>
        <p style="color:var(--text-mute); font-size:14px; margin-top:-8px;">The card shows your cover photo, logo, name and description. Clicking the card opens the website below.</p>

        <div class="field">
            <label>Description (shown on the card &amp; website)</label>
            <textarea name="description" rows="4" placeholder="A short welcome paragraph about your school — what makes it special, grades taught, location…"><?php echo htmlspecialchars($school['description'] ?? ''); ?></textarea>
        </div>

        <div class="grid grid-2">
            <div class="field">
                <label>Logo</label>
                <div style="margin:0 0 12px;">
                    <?php if (!empty($school['logo_url'])): ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($school['logo_url'])); ?>" alt="Current logo" style="height:84px; border-radius:10px; box-shadow:var(--shadow); background:#fff; padding:6px;">
                    <?php else: ?>
                        <div class="stat-box" style="max-width:200px;">No logo set</div>
                    <?php endif; ?>
                </div>
                <label style="font-weight:600;">Logo URL</label>
                <input type="text" name="logo_url" value="<?php echo htmlspecialchars($school['logo_url'] ?? ''); ?>" placeholder="https://… or /shared-uploads/schools/<?php echo $schoolId; ?>/logo.png">
                <label style="font-weight:600;">…or upload a logo file (PNG/JPG/SVG/WEBP, max 4MB)</label>
                <input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.svg,.webp">
            </div>
            <div class="field">
                <label>Cover / Hero Image (wide photo — used on the card and as the website hero)</label>
                <div style="margin:0 0 12px;">
                    <?php if (!empty($school['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($school['cover_image'])); ?>" alt="Current cover" style="width:100%; max-height:150px; object-fit:cover; border-radius:10px; box-shadow:var(--shadow);">
                    <?php else: ?>
                        <div class="stat-box" style="max-width:200px;">No cover image set — a blue gradient is used</div>
                    <?php endif; ?>
                </div>
                <input type="file" name="cover_image" accept=".png,.jpg,.jpeg,.webp">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>2. School Website</h2>
        <p style="color:var(--text-mute); font-size:14px; margin-top:-8px;">This is what opens when anyone clicks your card on the public schools page. Either pick a ready-made template, or paste your own HTML document.</p>

        <div class="grid grid-2">
            <div class="field">
                <label for="website_mode">Website mode</label>
                <select id="website_mode" name="website_mode" onchange="toggleWebsiteMode(this.value)">
                    <option value="none"<?php echo ($school['website_mode'] ?? 'none') === 'none' ? ' selected' : ''; ?>>No website (card links to the portal only)</option>
                    <option value="template"<?php echo ($school['website_mode'] ?? '') === 'template' ? ' selected' : ''; ?>>Use a built-in template</option>
                    <option value="html"<?php echo ($school['website_mode'] ?? '') === 'html' ? ' selected' : ''; ?>>Paste my own HTML document</option>
                </select>
            </div>
            <div class="field" id="field_template">
                <label for="website_template">Template</label>
                <select id="website_template" name="website_template">
                    <?php foreach ($templates as $key => $t): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($school['website_template'] ?? '') === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?> — <?php echo htmlspecialchars($t['desc']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:4px;">
                    <?php foreach ($templates as $key => $t): ?>
                        <a class="btn btn-sm" style="background:var(--paper-2, #F5F7FB); color:var(--text); border:1px solid var(--border);" href="/school-site.php?slug=<?php echo urlencode($school['slug']); ?>&preview=<?php echo htmlspecialchars($key); ?>" target="_blank">Preview <?php echo htmlspecialchars($t['name']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="field" id="field_html" style="<?php echo ($school['website_mode'] ?? 'none') === 'html' ? '' : 'display:none;'; ?>">
            <label for="website_html">Paste your full HTML document</label>
            <textarea id="website_html" name="website_html" rows="16" style="font-family:Consolas, Menlo, monospace; font-size:13px; line-height:1.5;" placeholder="<!DOCTYPE html>&#10;<html>&#10;<head>…</head>&#10;<body>…</body>&#10;</html>"><?php echo htmlspecialchars($school['website_html'] ?? ''); ?></textarea>
            <p style="color:var(--text-mute); font-size:13px;">Paste a complete page or just a snippet. It is shown exactly as written when your card is clicked. Tip: build a page in any editor, copy it, and paste it here.</p>
        </div>

        <div class="btn-row" style="margin-top:10px;">
            <button type="submit" class="btn">Save Profile &amp; Website</button>
            <?php if (!empty($school['slug'])): ?>
                <a class="btn" style="background:var(--success); border-color:var(--success);" href="/school-site.php?slug=<?php echo urlencode($school['slug']); ?>" target="_blank">Open My Website &rarr;</a>
                <a class="btn btn-sm" style="background:transparent; color:var(--text); border:1px solid var(--border);" href="/school.php" target="_blank">See It on the Schools Page</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
function toggleWebsiteMode(mode) {
    var t = document.getElementById('field_template');
    var h = document.getElementById('field_html');
    if (t) t.style.display = (mode === 'template') ? '' : 'none';
    if (h) h.style.display = (mode === 'html') ? '' : 'none';
}
</script>

<?php include"includes/footer.php"; ?>