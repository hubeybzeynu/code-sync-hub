<?php
$pageTitle = "Schools";
require "includes/db.php";
require "includes/school-helpers.php";

schoolEnsureColumns($pdo);

$schools = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.reg_status='active') AS student_count FROM schools s WHERE s.status='active' ORDER BY s.name")->fetchAll();

$heroImage = '';
try {
    $heroImage = $pdo->query("SELECT hero_image FROM app_settings WHERE id=1")->fetch()['hero_image'] ?? '';
} catch (Exception $e) { /* ignore */ }

include "includes/header.php";
?>

<?php if ($heroImage): ?>
<section class="schools-hero" style="background-image:linear-gradient(180deg, rgba(11,58,102,.62), rgba(11,58,102,.86)), url('<?php echo htmlspecialchars($heroImage); ?>');">
<?php else: ?>
<section class="schools-hero" style="background:linear-gradient(135deg, #0B3A66 0%, #0082C8 100%);">
<?php endif; ?>
    <div class="schools-hero-inner">
        <span class="kicker">The network</span>
        <h1 class="display-lg">Find your school</h1>
        <p class="schools-hero-sub">Browse every active school in Dire Dawa, read what makes each one special, and open its own website — then apply online in a few clicks.</p>
        <div class="schools-hero-stats">
            <div class="schools-hero-stat"><b><?php echo count($schools); ?></b><span>Active schools</span></div>
            <div class="schools-hero-stat"><b>Online</b><span>Report cards</span></div>
            <div class="schools-hero-stat"><b>Free</b><span>To browse</span></div>
        </div>
    </div>
</section>

<div class="schools-intro reveal">
    <span class="kicker">Explore the schools</span>
    <h2>Choose a school to open its website</h2>
</div>

<?php if (!$schools): ?>
    <div class="alert alert-info reveal in-view">No schools have been added yet. Please check back later.</div>
<?php else: ?>
    <div class="school-grid stagger-list">
        <?php foreach ($schools as $s):
            $cover = trim($s['cover_image'] ?? '');
            $logo  = trim($s['logo_url'] ?? '');
            $desc  = trim($s['description'] ?? '');
            if ($desc === '') $desc = schoolFallbackDescription($s);
            $students = (int)$s['student_count'];
        ?>
        <a class="school-card reveal" href="school-site.php?slug=<?php echo urlencode($s['slug']); ?>" title="Open <?php echo htmlspecialchars($s['name']); ?>'s website">
            <div class="school-card-cover"<?php if ($cover): ?> style="background-image:linear-gradient(180deg, rgba(11,58,102,.05) 0%, rgba(11,58,102,.55) 100%), url('<?php echo htmlspecialchars(assetUrl($cover)); ?>');"<?php endif; ?>>
                <?php if (!$cover): ?><span class="school-card-watermark"><?php echo htmlspecialchars(schoolInitials($s)); ?></span><?php endif; ?>
                <span class="school-card-badge">Active</span>
                <div class="school-card-logo">
                    <?php if ($logo): ?>
                        <img src="<?php echo htmlspecialchars(assetUrl($logo)); ?>" alt="<?php echo htmlspecialchars($s['name']); ?> logo">
                    <?php else: ?>
                        <span class="school-card-logo-fallback"><?php echo htmlspecialchars(schoolInitials($s)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="school-card-body">
                <h3><?php echo htmlspecialchars($s['name']); ?></h3>
                <p class="school-card-desc"><?php echo htmlspecialchars(mb_strimwidth($desc, 0, 160, '…')); ?></p>
                <div class="school-card-meta">
                    <span class="school-card-stat"><b><?php echo $students; ?></b> students enrolled</span>
                    <span class="school-card-go">Visit website &rarr;</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card reveal" style="text-align:center; margin-top:44px;">
    <h2 style="margin-top:0;">New here?</h2>
    <p>Create a student account, then your school reviews and approves it before you can log in.</p>
    <div class="btn-row">
        <a class="btn" href="direschool-student/register.php">Register as a Student</a>
        <a class="btn btn-outline" href="direschool-parent/register.php">Register as a Parent</a>
    </div>
</div>

<?php include "includes/footer.php"; ?>