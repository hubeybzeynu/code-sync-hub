<?php
$pageTitle = "FAQ";
include"includes/db.php";
include"includes/header.php";

$faqs = [
    ["I forgot my Student ID or password.", "Contact your school's office — they can look up your Student ID and reset your password from the Admin site."],
    ["Why do I need a password just to look up a report card? ", "Report cards and exam results contain personal academic information, so a Student ID plus password keeps them private."],
    ["I'm transferring to a new school. What happens to my old records? ", "Your new school submits a transfer request instead of registering you as new. The Super Admin reviews your promotion/detention history before approving it, so your record follows you honestly."],
    ["When can I check my Grade 8 Ministry result? ", "The Ministry Result page and button only appear for Grade 8 students, once your school has entered or imported the result."],
    ["Can I hide my age from classmates? ", "Yes — in your Student site under Settings, toggle \"Hide my age from classmates.\""],
    ["How do I pay the registration fee? ", "After your school reviews your registration, they'll tell you how to send the fee. Once received, they confirm it and your account activates."],
];
?>

<div class="welcome-hero" style="padding-top:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.3 9.3a2.7 2.7 0 1 1 3.9 2.4c-.9.5-1.2 1-1.2 2"/><circle cx="12" cy="16.6" r="0.4" fill="currentColor"/></svg></div>
    <h1>Frequently Asked Questions</h1></div>

<div class="grid grid-1 stagger-list" style="grid-template-columns: 1fr;">
    <?php foreach ($faqs as $f): ?>
        <div class="card reveal">
            <h3 style="margin-top:0;"><?php echo htmlspecialchars($f[0]); ?></h3>
            <p style="margin-bottom:0;"><?php echo htmlspecialchars($f[1]); ?></p>
        </div>
    <?php endforeach; ?></div>

<?php include"includes/footer.php"; ?>
