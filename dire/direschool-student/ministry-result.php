<?php
$pageTitle = "Ministry Result";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$result = null;
if ($student['grade'] === '8' && $student['student_no']) {
    $stmt = $pdo->prepare("SELECT * FROM ministry_results WHERE student_no = ? AND grade = '8' LIMIT 1");
    $stmt->execute([$student['student_no']]);
    $result = $stmt->fetch();
}

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 21V10M20 21V10M2 10l10-6 10 6M7 10v7M11 10v7M15 10v7M19 10v7"/></svg></div>
    <h1>Grade 8 Ministry Exam Result</h1>
    <p>Your official national exam result, once your school has entered it.</p></div><p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Ministry Result</p>

<?php if ($student['grade'] !== '8'): ?>
    <div class="alert alert-info reveal in-view">This result is only available for Grade 8 students.</div><?php elseif (!$result): ?>
    <div class="alert alert-info reveal in-view">Your Grade 8 Ministry result hasn't been published yet. Check back later.</div><?php else: ?>
    <?php $subjects = json_decode($result['subjects'] ?? '{}', true) ?: []; ?>
    <div class="card reveal">
        <div class="grid grid-4 stagger-list" style="margin-bottom:20px;">
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($result['student_no']); ?></div><div class="stat-label">Reg. Number</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo $result['total'] !== null ? $result['total'] : '-'; ?></div><div class="stat-label">Total</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo $result['average'] !== null ? number_format($result['average'], 1) : '-'; ?></div><div class="stat-label">Average</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($result['school_year'] ?? '-'); ?></div><div class="stat-label">School Year</div></div>
        </div>
        <?php if ($subjects): ?>
            <table class="data-table">
                <tr><th style="text-align:left;">Subject</th><th>Score</th></tr>
                <?php foreach ($subjects as $subj => $score): ?>
                    <tr><td class="subject-name"><?php echo htmlspecialchars($subj); ?></td><td><?php echo htmlspecialchars($score); ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <?php if ($result['promotion_status'] === 'promoted'): ?>
                <span class="tag tag-promoted"><?php echo htmlspecialchars($result['promotion_label'] ?: 'Promoted'); ?></span>
            <?php else: ?>
                <span class="tag tag-detained"><?php echo htmlspecialchars($result['promotion_label'] ?: 'Not Promoted'); ?></span>
            <?php endif; ?>
        </div>
        <button class="btn no-print" style="margin-top:15px;" onclick="printPage()"> Print</button>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
