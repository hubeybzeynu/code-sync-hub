<?php
$pageTitle = "Exam Result";
include"includes/db.php";

$error = "";
$results = [];
$examType = $_POST['exam_type'] ?? 'mid';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['student_no'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $examType  = in_array($_POST['exam_type'] ?? '', ['mid', 'final']) ? $_POST['exam_type'] : 'mid';

    if ($studentNo === '' || $password === '') {
        $error = "Please enter Student ID, Password, and choose the exam type.";
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM exam_results
            WHERE student_no = ? AND student_password = ? AND exam_type = ?
            ORDER BY subject
        ");
        $stmt->execute([$studentNo, $password, $examType]);
        $results = $stmt->fetchAll();

        if (!$results) {
            $error = "No" . ($examType === 'mid' ? "Mid-Term" : "Final-Term") ." result found for that Student ID and Password.";
        }
    }
}

include"includes/header.php";
?>

<h1>Mid / Final Exam Result</h1><p class="breadcrumb">Search for your mid-term or final-term exam result images.</p>

<div class="card form-narrow no-print">
    <div class="card-title-bar"><h2>Search</h2></div>
    <form method="post" action="exam-result.php" id="examForm">
        <div class="field">
            <label for="student_no">Student ID</label>
            <input type="text" id="student_no" name="student_no" required value="<?php echo htmlspecialchars($_POST['student_no'] ?? ''); ?>">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="field">
            <label for="exam_type">Exam Type</label>
            <select name="exam_type" id="exam_type">
                <option value="mid" <?php echo $examType === 'mid' ? 'selected' : ''; ?>>Mid-Term</option>
                <option value="final" <?php echo $examType === 'final' ? 'selected' : ''; ?>>Final-Term</option>
            </select>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-full">View Result</button>
    </form>
    <p class="help-text" style="margin-top:14px;">Try the sample: Student ID <b>101</b>, Password <b>1234</b>, Mid-Term</p></div><script>validateSearchForm('examForm');</script>

<?php if ($results): ?>
    <div class="card reveal">
        <div class="card-title-bar"><h2><?php echo $examType === 'mid' ? 'Mid-Term' : 'Final-Term'; ?> Results - <?php echo htmlspecialchars($results[0]['student_name'] ?? ''); ?></h2></div>
        <?php foreach ($results as $r): ?>
            <div style="margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #e3ecf5;">
                <h3><?php echo htmlspecialchars($r['subject'] ?? 'Subject'); ?><?php if ($r['grade_group']): ?><span class="badge"><?php echo htmlspecialchars($r['grade_group']); ?></span><?php endif; ?></h3>
                <?php if (!empty($r['result_image_url'])): ?>
                    <p><strong>Result:</strong><br>
                        <img src="<?php echo htmlspecialchars($r['result_image_url']); ?>" alt="Result" style="max-width:100%; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <a href="<?php echo htmlspecialchars($r['result_image_url']); ?>" target="_blank" class="help-text" style="display:none;">Open result image: <?php echo htmlspecialchars($r['result_image_url']); ?></a>
                    </p>
                <?php endif; ?>
                <?php if (!empty($r['answer_image_url'])): ?>
                    <p><strong>Answer Sheet:</strong><br>
                        <a href="<?php echo htmlspecialchars($r['answer_image_url']); ?>" target="_blank">View answer sheet</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn no-print" onclick="printPage()"> Print</button>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
