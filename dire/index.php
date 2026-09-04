<?php
$pageTitle = "Home";
require "includes/db.php";
require "includes/router.php";

$schoolCount = 0; $studentCount = 0; $teacherCount = 0; $newsList = [];
try {
    $schoolCount = (int)$pdo->query("SELECT COUNT(*) c FROM schools WHERE status='active'")->fetch()['c'];
    $studentCount = (int)$pdo->query("SELECT COUNT(*) c FROM students WHERE reg_status='active'")->fetch()['c'];
    $teacherCount = (int)$pdo->query("SELECT COUNT(*) c FROM admins WHERE role='teacher'")->fetch()['c'];
    $newsList = $pdo->query("SELECT n.*, s.name AS school_name FROM news n LEFT JOIN schools s ON s.id=n.school_id ORDER BY n.created_at DESC LIMIT 5")->fetchAll();
} catch (Exception $e) { /* DB may be empty; keep site rendering */ }
$newsList = $newsList ?: [];

$heroImage = ''; $heroTagline = ''; $ctaImage = '';
try {
    $set = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch();
    $heroImage = $set['hero_image'] ?? '';
    $heroTagline = $set['hero_tagline'] ?? '';
    $ctaImage = $set['cta_image'] ?? '';
} catch (Exception $e) { /* ignore */ }

include "includes/header.php";
?>

<!-- HERO -->
<section class="hero<?php echo $heroImage ? ' has-image' : ''; ?>"<?php if ($heroImage): ?> style="background-image:url('<?php echo htmlspecialchars(assetUrl($heroImage)); ?>'); --fade-start:var(--paper); --fade-end:var(--paper);"<?php endif; ?>>
    <?php if ($heroImage): ?><span class="hero-veil"></span><?php endif; ?>
    <div class="hero-billboard">
        <span class="kicker"><?php echo htmlspecialchars($heroTagline ?: 'Admissions &middot; Open for the new academic year'); ?></span>
        <h1 class="display-xl">Where education, creativity &amp; the future intersect.</h1>
        <p class="strap">One connected platform for every school, student, teacher and family in Dire Dawa.</p>
        <p class="lede">Admissions, attendance, exams, report cards and communication — managed centrally, lived by everyone.</p>
        <div class="hero-cta">
            <a class="btn btn-lg" href="direschool-student/register.php">Apply to a School</a>
            <a class="btn btn-lg btn-outline" href="portals.php">Access the Portals</a>
        </div>
        <div class="hero-meta">
            <div class="meta-item"><span class="meta-top">Schools</span><div class="meta-main"><?php echo $schoolCount; ?> connected</div></div>
            <div class="meta-item"><span class="meta-top">Students</span><div class="meta-main"><?php echo $studentCount; ?>+ enrolled</div></div>
            <div class="meta-item"><span class="meta-top">Teachers</span><div class="meta-main"><?php echo $teacherCount; ?> staffed</div></div>
            <div class="meta-item"><span class="meta-top">Results</span><div class="meta-main">Live &amp; shared</div></div>
        </div>
    </div>
</section>

<!-- STAT BAND -->
<div class="stat-band reveal">
    <div class="stat-cell"><span class="giant-num"><?php echo $schoolCount; ?></span><span class="stat-caption">Active Schools</span></div>
    <div class="stat-cell"><span class="giant-num"><?php echo $studentCount; ?>+</span><span class="stat-caption">Students</span></div>
    <div class="stat-cell"><span class="giant-num"><?php echo $teacherCount; ?></span><span class="stat-caption">Educators</span></div>
    <div class="stat-cell"><span class="giant-num">8</span><span class="stat-caption">Role-Specific Sites</span></div>
</div>

<!-- EDITORIAL GRID -->
<section class="section">
    <div class="section-head">
        <span class="kicker">Amazing things happen here</span>
        <h2 class="section-title">Outcomes first,<br>from enrollment to graduation.</h2>
    </div>
    <div class="editorial-grid reveal">
        <a href="school.php" class="editorial-card span-8">
            <div class="art">
                <div class="tile-band"><svg class="tile-svg" viewBox="0 0 100 60" preserveAspectRatio="none"><path d="M0 0h25v25H0zM25 0h25v25H25zM50 0h25v25H50zM75 0h25v25H75zM0 25h25v25H0zM25 25h25v25H25zM50 25h25v25H50zM75 25h25v25H75z" fill="rgba(11,11,11,0.05)"/></svg></div>
                <span class="badge-date">Network</span>
            </div>
            <div class="body">
                <span class="cat">Explore</span>
                <h3>Browse every school in the district — find the right one for your child.</h3>
                <p>Compare active schools, see live enrollment, and start a registration in a few clicks.</p>
                <span class="more">View Schools &rarr;</span>
            </div>
        </a>
        <a href="report-card.php" class="editorial-card span-4">
            <div class="art">
                <span class="badge-date">Live</span>
            </div>
            <div class="body">
                <span class="cat">Results</span>
                <h3>Report cards</h3>
                <p>Quarterly marks, averages and promotion, calculated the same way every time.</p>
                <span class="more">Lookup &rarr;</span>
            </div>
        </a>
        <a href="direschool-student/register.php" class="editorial-card span-4">
            <div class="art"><span class="badge-date">Open</span></div>
            <div class="body">
                <span class="cat">Admissions</span>
                <h3>New student registration</h3>
                <p>Apply online — a school reviews and approves before you can start.</p>
                <span class="more">Apply now &rarr;</span>
            </div>
        </a>
        <a href="ministry-result.php" class="editorial-card span-4">
            <div class="art"><span class="badge-date">National</span></div>
            <div class="body">
                <span class="cat">Exams</span>
                <h3>Grade 8 ministry results</h3>
                <p>National exam results, entered centrally and published per pupil.</p>
                <span class="more">Check result &rarr;</span>
            </div>
        </a>
        <a href="portals.php" class="editorial-card span-4">
            <div class="art"><span class="badge-date">8 Portals</span></div>
            <div class="body">
                <span class="cat">Access</span>
                <h3>One system, eight role sites</h3>
                <p>A dedicated site for super admins, schools, teachers, families and students.</p>
                <span class="more">Open a portal &rarr;</span>
            </div>
        </a>
    </div>
</section>

<!-- TICKER -->
<div class="ticker-band">
    <div class="ticker-track">
        <?php
        $ticks = ['Recognised by the Dire Dawa Education Bureau', 'Termly results published on time', 'Verified school-to-school transfers', 'Real-time attendance &amp; conduct', 'National Grade 8 results centralised'];
        for ($i = 0; $i < 2; $i++): foreach ($ticks as $t): ?>
            <span class="ticker-item"><span class="dot"></span><span><span class="tiny">Update</span> <?php echo $t; ?></span></span>
        <?php endforeach; endfor; ?>
    </div>
</div>

<!-- EVENTS -->
<section class="section">
    <div class="section-head">
        <span class="kicker">Calendar</span>
        <h2 class="section-title">Upcoming dates</h2>
    </div>
    <div class="events-track">
        <div class="event-card"><div class="ev-date">September<b>04</b></div><h3>Term begins</h3><p>All schools open for the new academic year.</p></div>
        <div class="event-card"><div class="ev-date">September<b>21</b></div><h3>First term assessments</h3><p>Teachers begin recording attendance and conduct.</p></div>
        <div class="event-card"><div class="ev-date">October<b>12</b></div><h3>Report card submission</h3><p>Quarterly marks due from every school.</p></div>
        <div class="event-card"><div class="ev-date">November<b>03</b></div><h3>Parents' meetings</h3><p>Family meetings for any conduct follow-up required.</p></div>
        <div class="event-card"><div class="ev-date">January<b>08</b></div><h3>Mid-year results</h3><p>Promotion status reviewed and published.</p></div>
    </div>
</section>

<!-- NEW FROM SCHOOLS -->
<section class="section section-tight">
    <div class="section-head">
        <span class="kicker">From the network</span>
        <h2 class="section-title">Latest announcements</h2>
    </div>
    <?php if ($newsList): ?>
    <div class="hrule"></div>
    <div class="editorial-grid">
        <?php foreach ($newsList as $i => $n): ?>
        <div class="editorial-card <?php echo $i % 3 === 0 ? 'span-8' : 'span-4'; ?>">
            <div class="art"><span class="badge-date"><?php echo date('d M', strtotime($n['created_at'])); ?></span></div>
            <div class="body">
                <span class="cat"><?php echo htmlspecialchars($n['school_name'] ?: 'Dire Dawa Bureau'); ?></span>
                <h3><?php echo htmlspecialchars($n['title']); ?></h3>
                <p><?php echo htmlspecialchars(mb_strimwidth(strip_tags($n['content'] ?? ''), 0, 140, '…')); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:var(--mute);">Announcements from schools will appear here as they are posted.</p>
    <?php endif; ?>
</section>

<!-- ALUMNI / SPOTLIGHT -->
<section class="section">
    <div class="section-head">
        <span class="kicker">We make pioneers</span>
        <h2 class="section-title">Spotlight</h2>
    </div>
    <div class="spot-track">
        <div class="spot-card">
            <div class="spot-photo"><span class="mono">AL</span></div>
            <div class="spot-body"><h3 class="spot-name">Alem Tesfaye</h3><div class="spot-role">Medic &middot; Class of 2021</div><p class="spot-bio">From Dire Dawa's schools to a national medical college — proving early starts become big futures.</p></div>
        </div>
        <div class="spot-card">
            <div class="spot-photo"><span class="mono">BH</span></div>
            <div class="spot-body"><h3 class="spot-name">Bereket Hailu</h3><div class="spot-role">Engineer &middot; Class of 2019</div><p class="spot-bio">A school transfer verified centrally gave her the clean record to pursue engineering with confidence.</p></div>
        </div>
        <div class="spot-card">
            <div class="spot-photo"><span class="mono">SD</span></div>
            <div class="spot-body"><h3 class="spot-name">Sara Dereje</h3><div class="spot-role">Teacher &middot; Now leading a section</div><p class="spot-bio">From student to staff — she uses the same platform to run attendance, conduct and report cards.</p></div>
        </div>
        <div class="spot-card">
            <div class="spot-photo"><span class="mono">KM</span></div>
            <div class="spot-body"><h3 class="spot-name">Kemal Mohammed</h3><div class="spot-role">Entrepreneur &middot; Class of 2020</div><p class="spot-bio">Graduated with a verified record and the support of a connected school community behind him.</p></div>
        </div>
    </div>
</section>

<!-- ACCREDITATION / PARTNER WALL -->
<section class="section section-tight">
    <div class="section-head center">
        <span class="kicker">Accredited &amp; connected</span>
        <h2 class="section-title">Serving the city's education network</h2>
    </div>
    <div class="accredit-wall">
        <div class="accredit-track">
            <span class="accredit-item">Dire Dawa Education Bureau</span>
            <span class="accredit-item">Public Schools Network</span>
            <span class="accredit-item">Verified Transfers</span>
            <span class="accredit-item">National Grade 8 Exams</span>
            <span class="accredit-item">Shared Report Cards</span>
            <span class="accredit-item">Digital Admissions</span>
            <span class="accredit-item">Staff Development</span>
            <span class="accredit-item">Community Access</span>
            <span class="accredit-item">Dire Dawa Education Bureau</span>
            <span class="accredit-item">Public Schools Network</span>
            <span class="accredit-item">Verified Transfers</span>
            <span class="accredit-item">National Grade 8 Exams</span>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="card" style="border-top:3px solid var(--gold);<?php if ($ctaImage): ?> background-image:linear-gradient(135deg, rgba(0,130,200,.88), rgba(11,58,102,.94)), url('<?php echo htmlspecialchars(assetUrl($ctaImage)); ?>'); background-size:cover; background-position:center;<?php else: ?> background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);<?php endif; ?> color:#fff; text-align:center; padding:60px 30px; margin:0 auto; max-width:920px;">
        <span class="kicker" style="color:var(--gold);">Get started</span>
        <h2 class="section-title" style="color:#fff;">Ready to check a result, apply, or sign in?</h2>
        <div class="btn-row" style="margin-top:24px;">
            <a class="btn btn-gold btn-lg" href="report-card.php">Check a Report Card</a>
            <a class="btn btn-lg btn-outline" href="portals.php" style="color:#fff; border-color:rgba(255,255,255,.4);">Go to Login Portals</a>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
