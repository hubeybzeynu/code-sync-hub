<?php
$pageTitle = "About";
include"includes/db.php";
include"includes/header.php";

$aboutImage = '';
try { $set = $pdo->query("SELECT * FROM app_settings WHERE id=1")->fetch(); $aboutImage = $set['about_image'] ?? ''; }
catch (Exception $e) { /* ignore */ }
?>

<div class="welcome-hero" style="padding-top:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/><path d="M22 8v6"/></svg></div>
    <h1>About /direschool</h1>
    <p>A shared platform connecting schools, staff, students and families across Dire Dawa.</p></div>

<?php if ($aboutImage): ?>
<div class="card reveal" style="padding:0; overflow:hidden; margin-top:6px;">
    <img src="<?php echo htmlspecialchars(assetUrl($aboutImage)); ?>" alt="Dire Dawa Schools" style="width:100%; display:block; max-height:340px; object-fit:cover;">
</div>
<?php endif; ?>

<div class="card reveal">
    <h2>What We Do</h2>
    <p>/direschool brings every school in Dire Dawa onto one connected system: students and parents can check
       results and stay informed online, while school staff manage admissions, attendance, conduct, report cards
       and the library from purpose-built tools for their role.</p>
    <p>The platform is built around one important safeguard: when a student moves between schools, the receiving
       school cannot simply re-register them as new. A transfer must be verified by the system administrator, who
       can see the student's full promotion history first — protecting the integrity of every school's records.</p></div>

<div class="card reveal">
    <h2>Who Runs It</h2>
    <p>/direschool is built in partnership with the Dire Dawa Administration Education Bureau to support schools
       across the city with consistent, transparent record-keeping.</p></div>

<?php include"includes/footer.php"; ?>
