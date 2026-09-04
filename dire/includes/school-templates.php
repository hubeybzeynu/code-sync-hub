<?php
// includes/school-templates.php
// Renders a school's standalone website. Two modes are supported:
//   - template: one of the built-in designs from schoolTemplates()
//   - html:     raw HTML the school admin pasted (output as-is)
// Every template is a complete, self-contained HTML document — its own
// <head>, CSS and footer — so each school site looks like its own website,
// not another page of the portal.

// Builds the inline background style for a school hero/cover.
function schoolCoverStyle($school) {
    $cover = trim($school['cover_image'] ?? '');
    if ($cover !== '') {
        return "background-image:linear-gradient(180deg, rgba(11,58,102,.55), rgba(11,58,102,.82)), url('" . htmlspecialchars(assetUrl($cover), ENT_QUOTES) . "');background-size:cover;background-position:center;";
    }
    return "background:linear-gradient(135deg, #0B3A66 0%, #0082C8 100%);";
}

// Fetches the small data set a template needs (counts + latest announcements).
function schoolSiteData($pdo, $school) {
    $data = ['students' => 0, 'sections' => 0, 'news' => []];
    try {
        $st = $pdo->prepare("SELECT COUNT(*) c FROM students WHERE school_id=? AND reg_status='active'");
        $st->execute([$school['id']]);
        $data['students'] = (int)$st->fetch()['c'];
        $sc = $pdo->prepare("SELECT COUNT(*) c FROM sections WHERE school_id=?");
        $sc->execute([$school['id']]);
        $data['sections'] = (int)$sc->fetch()['c'];
        $n = $pdo->prepare("SELECT * FROM news WHERE school_id=? ORDER BY created_at DESC LIMIT 3");
        $n->execute([$school['id']]);
        $data['news'] = $n->fetchAll();
    } catch (Exception $e) { /* DB may be partially empty; keep rendering */ }
    return $data;
}

// Renders the school's website as a standalone HTML document.
function renderSchoolTemplate($pdo, $school) {
    $key = trim($school['website_template'] ?? '');
    $templates = schoolTemplates();
    if (!isset($templates[$key])) $key = 'classic';
    $data = schoolSiteData($pdo, $school);

    $name = htmlspecialchars($school['name'] ?? 'School');
    $logo = trim($school['logo_url'] ?? '');
    $logoImg = $logo ? "<img class=\"sw-logo\" src=\"" . htmlspecialchars(assetUrl($logo), ENT_QUOTES) . "\" alt=\"" . $name . " logo\">" : "<span class=\"sw-logo sw-logo-fallback\">" . htmlspecialchars(schoolInitials($school)) . "</span>";
    $desc = htmlspecialchars($school['description'] ?? schoolFallbackDescription($school));
    $coverStyle = schoolCoverStyle($school);
    $year = date('Y');
    $students = (int)$data['students'];

    $newsHtml = '';
    foreach ($data['news'] as $i => $n) {
        $snippet = htmlspecialchars(mb_strimwidth(strip_tags($n['body'] ?? ''), 0, 150, '…'));
        $newsHtml .= "<article class=\"sw-news-card\"><span class=\"sw-news-date\">" . date('d M Y', strtotime($n['created_at'])) . "</span><h3>" . htmlspecialchars($n['title']) . "</h3><p>" . $snippet . "</p></article>";
    }
    if ($newsHtml === '') {
        $newsHtml = '<p class="sw-empty">Announcements from ' . $name . ' will appear here as they are posted.</p>';
    }

    $theme = '';
    if ($key === 'modern') {
        $theme = 'sw-theme-modern';
    } elseif ($key === 'minimal') {
        $theme = 'sw-theme-minimal';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $name; ?> &middot; Official School Website</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@600;700;800;900&family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{
    --sw-navy:#0B3A66; --sw-blue:#0082C8; --sw-blue-2:#1A92D1; --sw-gold:#FCE119; --sw-gold-2:#DFC400;
    --sw-ink:#14181F; --sw-gray:#5F6B76; --sw-mute:#7A8791; --sw-line:#E4E8EC; --sw-paper:#FFFFFF; --sw-paper-2:#F6F8FA;
    --sw-radius:10px; --sw-shadow:0 18px 44px rgba(11,58,102,.14);
    --sw-font-display:"Roboto Slab", Georgia, serif; --sw-font-sans:"DM Sans","Segoe UI",Arial,sans-serif; --sw-font-mono:"Space Grotesk",Arial,sans-serif;
}
*{box-sizing:border-box}
body{margin:0;font-family:var(--sw-font-sans);background:var(--sw-paper);color:var(--sw-ink);font-size:16px;line-height:1.65;-webkit-font-smoothing:antialiased}
a{color:var(--sw-blue);text-decoration:none}
h1,h2,h3,h4{font-family:var(--sw-font-display);font-weight:800;line-height:1.1;letter-spacing:-.5px;margin:0 0 10px}
img{max-width:100%;display:block}
.wrap{max-width:1120px;margin:0 auto;padding:0 26px}
.btn{display:inline-block;background:var(--sw-blue);color:#fff;border:1px solid var(--sw-blue);padding:13px 26px;font-size:14px;font-weight:700;border-radius:6px;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease,background .15s ease}
.btn:hover{transform:translateY(-2px);box-shadow:var(--sw-shadow);color:#fff}
.btn-outline{background:transparent;color:#fff;border-color:rgba(255,255,255,.5)}
.btn-outline:hover{color:#fff;border-color:#fff}
.btn-gold{background:var(--sw-gold);border-color:var(--sw-gold);color:#1A1A1A}
.btn-gold:hover{background:var(--sw-gold-2);color:#1A1A1A}
.btn-row{display:flex;flex-wrap:wrap;gap:12px}
.kicker{font-family:var(--sw-font-mono);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--sw-gray);display:inline-flex;align-items:center;gap:10px}
.kicker::before{content:"";width:26px;height:2px;background:var(--sw-gold);display:inline-block}

/* ---- top bar ---- */
.sw-top{background:var(--sw-paper);border-bottom:1px solid var(--sw-line);position:sticky;top:0;z-index:20}
.sw-top-inner{max-width:1120px;margin:0 auto;padding:14px 26px;display:flex;align-items:center;gap:14px}
.sw-top .sw-logo{width:46px;height:46px;border-radius:8px;object-fit:contain;flex:0 0 46px}
.sw-top-name{font-family:var(--sw-font-display);font-weight:900;font-size:17px;letter-spacing:-.02em;color:var(--sw-ink)}
.sw-top .sw-portal-link{margin-left:auto;font-family:var(--sw-font-mono);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--sw-mute);white-space:nowrap}
.sw-top .sw-portal-link:hover{color:var(--sw-blue)}

/* ---- hero ---- */
.sw-hero{position:relative;color:#fff;<?php echo $coverStyle; ?>}
.sw-hero::after{content:"";position:absolute;inset:auto 0 0 0;height:90px;background:linear-gradient(180deg,transparent,var(--sw-paper));pointer-events:none}
.sw-hero-inner{position:relative;z-index:2;padding:96px 0 120px;max-width:820px}
.sw-hero .kicker{color:#CFE6F7}
.sw-hero .kicker::before{background:var(--sw-gold)}
.sw-hero h1{font-size:clamp(34px,5vw,62px);font-weight:900;margin:14px 0 12px;color:#fff}
.sw-hero p{font-size:17px;color:#E3F0FA;max-width:620px;margin:0 0 26px}
.sw-hero .sw-logo{width:74px;height:74px;border-radius:12px;object-fit:contain;background:#fff;padding:8px;box-shadow:var(--sw-shadow)}
.sw-hero .sw-logo-fallback{width:74px;height:74px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:var(--sw-gold);color:var(--sw-navy);font-family:var(--sw-font-display);font-weight:900;font-size:26px}
.sw-hero-stats{display:flex;flex-wrap:wrap;gap:34px;margin-top:34px;padding-top:22px;border-top:1px solid rgba(255,255,255,.25)}
.sw-hero-stat b{font-family:var(--sw-font-display);font-size:26px;font-weight:900;display:block;color:#fff}
.sw-hero-stat span{font-family:var(--sw-font-mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#C4DDF0}

/* ---- sections ---- */
.sw-section{padding:64px 0}
.sw-section.alt{background:var(--sw-paper-2)}
.sw-section-head{max-width:680px;margin-bottom:30px}
.sw-section-head h2{font-size:clamp(24px,3vw,36px);font-weight:900}
.sw-about{display:grid;grid-template-columns:1.4fr 1fr;gap:40px;align-items:start}
.sw-about p{margin:0;color:var(--sw-gray);font-size:15.5px}
.sw-facts{background:var(--sw-paper);border:1px solid var(--sw-line);border-top:3px solid var(--sw-blue);border-radius:var(--sw-radius);padding:22px 24px;box-shadow:var(--sw-shadow)}
.sw-facts h4{margin:0 0 14px;font-size:15px}
.sw-fact{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px dashed var(--sw-line);font-size:14px}
.sw-fact:last-child{border-bottom:none}
.sw-fact b{font-weight:800}
.sw-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.sw-value{border-top:3px solid var(--sw-gold);background:var(--sw-paper);border-radius:var(--sw-radius);padding:22px}
.sw-value h3{font-size:17px;margin-bottom:6px}
.sw-value p{margin:0;color:var(--sw-gray);font-size:13.5px}
.sw-news-card{border:1px solid var(--sw-line);border-top:3px solid var(--sw-blue);background:var(--sw-paper);border-radius:var(--sw-radius);padding:20px}
.sw-news-date{font-family:var(--sw-font-mono);font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--sw-gold-2);font-weight:700}
.sw-news-card h3{font-size:16px;margin:6px 0}
.sw-news-card p{margin:0;color:var(--sw-gray);font-size:13.5px}
.sw-empty{color:var(--sw-mute)}
.sw-cta{background:linear-gradient(135deg,var(--sw-navy) 0%,var(--sw-blue-2) 100%);color:#fff;border-radius:var(--sw-radius);padding:46px 40px;text-align:center;box-shadow:var(--sw-shadow)}
.sw-cta h2{color:#fff;font-size:clamp(22px,3vw,32px)}
.sw-cta p{color:#D7EAF9;max-width:520px;margin:0 auto 22px}

/* ---- footer ---- */
.sw-footer{background:var(--sw-navy);color:#CBE4F5;margin-top:64px}
.sw-footer-inner{max-width:1120px;margin:0 auto;padding:38px 26px 24px;display:flex;flex-wrap:wrap;gap:26px;align-items:flex-start}
.sw-footer-name{font-family:var(--sw-font-display);font-weight:800;font-size:18px;color:#fff;display:flex;align-items:center;gap:10px}
.sw-footer-name img,.sw-footer-name .sw-logo-fallback{width:32px;height:32px;border-radius:6px;object-fit:contain;flex:0 0 32px;font-size:13px;display:inline-flex;align-items:center;justify-content:center;background:var(--sw-gold);color:var(--sw-navy)}
.sw-footer-links{margin-left:auto;display:flex;gap:26px;flex-wrap:wrap;font-size:13.5px}
.sw-footer-links a{color:#CBE4F5}
.sw-footer-links a:hover{color:#fff}
.sw-footer-bottom{max-width:1120px;margin:0 auto;padding:14px 26px 26px;border-top:1px solid rgba(255,255,255,.14);font-size:12.5px;color:#8FB6D6}

/* ===== template: MODERN ===== */
.sw-theme-modern{--sw-navy:#0E1420;--sw-blue:#F4D93B;--sw-blue-2:#FFD966;--sw-gold:#F4D93B;--sw-gold-2:#E0C21E;--sw-paper-2:#F2F4F7;--sw-line:#DDE3EA}
.sw-theme-modern .sw-top-name{color:var(--sw-navy)}
.sw-theme-modern .sw-hero .btn{background:var(--sw-blue);border-color:var(--sw-blue);color:var(--sw-navy)}
.sw-theme-modern .sw-hero .btn-outline{color:#fff;border-color:rgba(255,255,255,.55)}
.sw-theme-modern .sw-hero .sw-logo-fallback{background:var(--sw-navy);color:var(--sw-blue)}
.sw-theme-modern .sw-facts{border-top-color:var(--sw-blue)}
.sw-theme-modern .sw-value{border-top-color:var(--sw-blue)}
.sw-theme-modern .sw-cta{background:linear-gradient(135deg,#0E1420 0%,#232B3B 100%);border-top:4px solid var(--sw-blue)}
.sw-theme-modern .sw-footer{background:#0E1420}
.sw-theme-modern .sw-footer-bottom{color:#8A93A8}

/* ===== template: MINIMAL ===== */
.sw-theme-minimal{--sw-blue:#0B3A66;--sw-blue-2:#33618F;--sw-paper-2:#FFFFFF;--sw-line:#E9E6E0}
.sw-theme-minimal h1,.sw-theme-minimal h2,.sw-theme-minimal h3{font-family:Georgia,"Times New Roman",serif;font-weight:700}
.sw-theme-minimal .sw-hero{border-bottom:1px solid var(--sw-line)}
.sw-theme-minimal .sw-hero-inner{padding:88px 0 96px}
.sw-theme-minimal .sw-section{padding:56px 0}
.sw-theme-minimal .sw-section-head{text-align:center;margin-left:auto;margin-right:auto}
.sw-theme-minimal .sw-section-head .kicker::before{display:none}
.sw-theme-minimal .sw-about{grid-template-columns:1fr}
.sw-theme-minimal .sw-facts{box-shadow:none;border:1px solid var(--sw-line);border-top:none;border-left:3px solid var(--sw-blue)}
.sw-theme-minimal .sw-grid{grid-template-columns:1fr;gap:0}
.sw-theme-minimal .sw-value{border:none;border-top:1px solid var(--sw-line);border-radius:0;padding:20px 4px}
.sw-theme-minimal .sw-cta{background:var(--sw-paper);border:1px solid var(--sw-line);border-top:3px solid var(--sw-blue);color:var(--sw-ink);box-shadow:none;border-radius:0}
.sw-theme-minimal .sw-cta h2{color:var(--sw-ink)}
.sw-theme-minimal .sw-cta p{color:var(--sw-gray)}
.sw-theme-minimal .sw-cta .btn{background:var(--sw-blue)}
.sw-theme-minimal .sw-footer{background:var(--sw-ink)}
.sw-theme-minimal .sw-footer-bottom{color:#9AA4AD}

@media (max-width:860px){
    .sw-about{grid-template-columns:1fr}
    .sw-grid{grid-template-columns:1fr}
    .sw-footer-links{margin-left:0}
    .sw-hero-inner{padding:64px 0 96px}
}
</style>
</head>
<body class="<?php echo $theme; ?>">
<header class="sw-top">
    <div class="sw-top-inner">
        <?php echo $logoImg; ?>
        <span class="sw-top-name"><?php echo $name; ?></span>
        <a class="sw-portal-link" href="school.php">&#8592; Back to all schools</a>
    </div>
</header>

<section class="sw-hero">
    <div class="wrap sw-hero-inner">
        <?php echo $logoImg; ?>
        <h1><?php echo $name; ?></h1>
        <p><?php echo $desc; ?></p>
        <div class="btn-row">
            <a class="btn" href="direschool-student/register.php">Apply to this School</a>
            <a class="btn btn-outline" href="school.php">Browse More Schools</a>
        </div>
        <div class="sw-hero-stats">
            <div class="sw-hero-stat"><b><?php echo $students; ?></b><span>Students enrolled</span></div>
            <div class="sw-hero-stat"><b><?php echo (int)$data['sections']; ?></b><span>Class sections</span></div>
            <div class="sw-hero-stat"><b><?php echo $year; ?></b><span>Academic year</span></div>
            <div class="sw-hero-stat"><b>Dire Dawa</b><span>Ethiopia</span></div>
        </div>
    </div>
</section>

<section class="sw-section">
    <div class="wrap">
        <div class="sw-section-head">
            <span class="kicker">About</span>
            <h2>Welcome to <?php echo $name; ?></h2>
        </div>
        <div class="sw-about">
            <p><?php echo $desc; ?></p>
            <div class="sw-facts">
                <h4>Quick facts</h4>
                <div class="sw-fact"><span>Network</span><b>Dire Dawa Schools</b></div>
                <div class="sw-fact"><span>Students</span><b><?php echo $students; ?></b></div>
                <div class="sw-fact"><span>Sections</span><b><?php echo (int)$data['sections']; ?></b></div>
                <div class="sw-fact"><span>Year</span><b><?php echo $year; ?></b></div>
                <div class="sw-fact"><span>Report cards</span><b>Online &amp; verified</b></div>
            </div>
        </div>
    </div>
</section>

<section class="sw-section alt">
    <div class="wrap">
        <div class="sw-section-head">
            <span class="kicker">Why choose us</span>
            <h2>What makes <?php echo $name; ?> special</h2>
        </div>
        <div class="sw-grid">
            <div class="sw-value"><h3>Caring teachers</h3><p>Experienced staff who know every child by name and track progress every term.</p></div>
            <div class="sw-value"><h3>Verified records</h3><p>Attendance, conduct and report cards recorded centrally — nothing gets lost.</p></div>
            <div class="sw-value"><h3>One step ahead</h3><p>Parents can follow their child's progress online, and transfers are verified safely.</p></div>
        </div>
    </div>
</section>

<section class="sw-section">
    <div class="wrap">
        <div class="sw-section-head">
            <span class="kicker">Latest announcements</span>
            <h2>News from the school</h2>
        </div>
        <div class="sw-grid"><?php echo $newsHtml; ?></div>
    </div>
</section>

<section class="sw-section">
    <div class="wrap">
        <div class="sw-cta">
            <h2>Ready to join <?php echo $name; ?>?</h2>
            <p>Start a student registration online — the school reviews and approves it before you can log in.</p>
            <div class="btn-row" style="justify-content:center;">
                <a class="btn btn-gold" href="direschool-student/register.php">Apply Now</a>
                <a class="btn btn-outline" href="report-card.php">Check a Report Card</a>
            </div>
        </div>
    </div>
</section>

<footer class="sw-footer">
    <div class="sw-footer-inner">
        <span class="sw-footer-name"><?php echo $logoImg; ?><?php echo $name; ?></span>
        <div class="sw-footer-links">
            <a href="school.php">Browse schools</a>
            <a href="portals.php">Login portals</a>
            <a href="report-card.php">Report card lookup</a>
            <a href="ministry-result.php">Grade 8 results</a>
        </div>
    </div>
    <div class="sw-footer-bottom">&copy; <?php echo $year; ?> <?php echo $name; ?> &middot; Dire Dawa Administration Education Bureau. All rights reserved.</div>
</footer>
</body>
</html>
<?php
}

// Renders the school site for a given school row.
// 'html' mode outputs the admin's pasted document (full doc as-is, or a
// fragment wrapped in a minimal document); anything else uses a template.
function renderSchoolSite($pdo, $school) {
    $mode = trim($school['website_mode'] ?? 'none');
    if ($mode === 'html') {
        $html = trim($school['website_html'] ?? '');
        if ($html === '') {
            // Stored mode is html but nothing saved yet — fall back to template.
            renderSchoolTemplate($pdo, $school);
            return;
        }
        if (stripos($html, '<html') !== false) {
            echo $html;
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($school['name'] ?? 'School Website') . '</title></head><body>' . $html . '</body></html>';
        }
        return;
    }
    renderSchoolTemplate($pdo, $school);
}