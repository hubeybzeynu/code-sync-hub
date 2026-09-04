<?php
$pageTitle = "Grade 8 Ministry Result";
include"includes/db.php";

$error = "";
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['student_no'] ?? '');

    if ($studentNo === '') {
        $error = "Please enter your registration number.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM ministry_results WHERE student_no = ? AND grade = '8' LIMIT 1");
        $stmt->execute([$studentNo]);
        $result = $stmt->fetch();

        if (!$result) {
            $error = "No Grade 8 Ministry result found for registration number \"" . htmlspecialchars($studentNo) ."\".";
        }
    }
}

include"includes/header.php";
?>


<h1>Grade 8 Ministry Exam Result</h1><p class="breadcrumb">Search the national Grade 8 exam result using your registration number.</p>

<div class="card form-narrow no-print">
    <div class="card-title-bar"><h2>Search</h2></div>
    <form method="post" action="ministry-result.php" id="ministryForm">
        <div class="field">
            <label for="student_no">Registration Number</label>
            <input type="text" id="student_no" name="student_no" required placeholder="e.g. 219339" value="<?php echo htmlspecialchars($_POST['student_no'] ?? ''); ?>">
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-full">View Result</button>
    </form>
    <p class="help-text" style="margin-top:14px;">Try the sample: Registration Number <b>219339</b></p></div><script>validateSearchForm('ministryForm');</script>

<?php if ($result): ?>
    <?php
        $subjects = json_decode($result['subjects'] ?? '{}', true) ?: [];
        $isPromoted = $result['promotion_status'] === 'promoted';
    ?>
    <div class="card reveal">
        <div class="card-title-bar"><h2>Result - <?php echo htmlspecialchars($result['student_name'] ?? ''); ?></h2></div>

        <div class="grid grid-4" style="margin-bottom:20px;">
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($result['student_no']); ?></div><div class="stat-label">Reg. Number</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo $result['total'] !== null ? $result['total'] : '-'; ?></div><div class="stat-label">Total</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo $result['average'] !== null ? number_format($result['average'], 1) : '-'; ?></div><div class="stat-label">Average</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($result['school_year'] ?? '-'); ?></div><div class="stat-label">School Year</div></div>
        </div>

        <?php if ($subjects): ?>
            <table class="data-table">
                <tr>
                    <th style="text-align:left;">Subject</th>
                    <th>Score</th>
                </tr>
                <?php foreach ($subjects as $subj => $score): ?>
                    <tr>
                        <td class="subject-name"><?php echo htmlspecialchars($subj); ?></td>
                        <td><?php echo htmlspecialchars($score); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <div style="margin-top:20px;">
            <h3>Result</h3>
            <?php if ($isPromoted): ?>
                <span class="tag tag-promoted"><?php echo htmlspecialchars($result['promotion_label'] ?: 'Promoted'); ?></span>
            <?php else: ?>
                <span class="tag tag-detained"><?php echo htmlspecialchars($result['promotion_label'] ?: 'Not Promoted'); ?></span>
            <?php endif; ?>
        </div>

        <button class="btn no-print" style="margin-top:15px;" onclick="printPage()"> Print</button>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
