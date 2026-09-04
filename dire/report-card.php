<?php
$pageTitle = "Report Card";
include"includes/db.php";

// Fixed list of subjects and quarters (same as the school syllabus)
$SUBJECTS = [
    "Amharic", "English", "Mathematics", "General Science", "Social Studies",
    "Citizenship Education", "Performing & Visual Arts", "Information Technology",
    "Health & Physical Education", "Career & Technical Education",
];
$QUARTERS = ["1", "2", "3", "4"];

function safeNum($v) {
    return is_numeric($v) ? (float) $v : null;
}

// average of quarter marks entered for one subject (ignores empty/zero)
function subjectAverage($row) {
    if (!$row) return null;
    $vals = [];
    foreach (["1", "2", "3", "4"] as $q) {
        $n = safeNum($row[$q] ?? null);
        if ($n !== null && $n > 0) $vals[] = $n;
    }
    if (!$vals) return null;
    return array_sum($vals) / count($vals);
}

function isCardComplete($card, $SUBJECTS) {
    $subjects = json_decode($card['subjects'] ?? '{}', true) ?: [];
    foreach ($SUBJECTS as $s) {
        $row = $subjects[$s] ?? null;
        if (!$row) return false;
        foreach (["1", "2", "3", "4"] as $q) {
            if (!(safeNum($row[$q] ?? null) > 0)) return false;
        }
    }
    return true;
}

function grandAverage($card, $SUBJECTS) {
    $subjects = json_decode($card['subjects'] ?? '{}', true) ?: [];
    $avgs = [];
    foreach ($SUBJECTS as $s) {
        $a = subjectAverage($subjects[$s] ?? null);
        if ($a !== null) $avgs[] = $a;
    }
    if (!$avgs) return null;
    return array_sum($avgs) / count($avgs);
}

$error = "";
$card = null;
$student = null;
$rank = null;
$rankTotal = null;
$promotion = null; // 'promoted' | 'detained' | 'incomplete'
$promotionLabel = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['student_no'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if ($studentNo === '' || $password === '') {
        $error = "Please enter both Student ID and Password.";
    } else {
        $stmt = $pdo->prepare("
            SELECT rc.*, s.full_name, s.school_id
            FROM report_cards rc
            JOIN students s ON s.id = rc.student_id
            WHERE s.student_no = ? AND rc.card_password = ?
            ORDER BY rc.school_year DESC
            LIMIT 1
        ");
        $stmt->execute([$studentNo, $password]);
        $card = $stmt->fetch();

        if (!$card) {
            $error = "No report card found for that Student ID and Password. Please check and try again.";
        } else {
            // Rank within same school + grade + section + year, among complete cards
            $cohortStmt = $pdo->prepare("
                SELECT rc.*, s.id AS sid
                FROM report_cards rc
                JOIN students s ON s.id = rc.student_id
                WHERE rc.school_id = ? AND rc.grade = ? AND rc.section = ? AND rc.school_year = ?
            ");
            $cohortStmt->execute([$card['school_id'], $card['grade'], $card['section'], $card['school_year']]);
            $cohort = $cohortStmt->fetchAll();

            if (isCardComplete($card, $SUBJECTS)) {
                $pool = array_values(array_filter($cohort, fn($c) => isCardComplete($c, $SUBJECTS)));
                usort($pool, function ($a, $b) use ($SUBJECTS) {
                    $av = grandAverage($a, $SUBJECTS) ?? -1;
                    $bv = grandAverage($b, $SUBJECTS) ?? -1;
                    if ($av == $bv) return $a['student_id'] <=> $b['student_id'];
                    return $bv <=> $av;
                });
                foreach ($pool as $i => $c) {
                    if ((int)$c['id'] === (int)$card['id']) { $rank = $i + 1; break; }
                }
                $rankTotal = count($pool);

                // promotion rule: detained if >=2 subject averages < 60
                $fails = 0;
                $subjectsData = json_decode($card['subjects'], true) ?: [];
                foreach ($SUBJECTS as $s) {
                    $a = subjectAverage($subjectsData[$s] ?? null);
                    if ($a === null || $a < 60) $fails++;
                }
                if ($fails >= 2) {
                    $promotion = 'detained';
                    $promotionLabel = "Detained in Grade" . htmlspecialchars($card['grade']);
                } else {
                    $promotion = 'promoted';
                    $next = is_numeric($card['grade']) ? ((int)$card['grade'] + 1) : '? ';
                    $promotionLabel = "Promoted to Grade" . $next;
                }
            } else {
                $promotion = 'incomplete';
                $promotionLabel = "Waiting for Q4";
            }
        }
    }
}

include"includes/header.php";
?>

<h1>Report Card Lookup</h1><p class="breadcrumb">Enter your Student ID and password to view your quarterly report card.</p>

<div class="card form-narrow no-print">
    <div class="card-title-bar"><h2>Search</h2></div>
    <form method="post" action="report-card.php" id="rcForm">
        <div class="field">
            <label for="student_no">Student ID</label>
            <input type="text" id="student_no" name="student_no" required value="<?php echo htmlspecialchars($_POST['student_no'] ?? ''); ?>">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-full">View Report Card</button>
    </form>
    <p class="help-text" style="margin-top:14px;">Try the sample: Student ID <b>101</b>, Password <b>1234</b></p></div><script>validateSearchForm('rcForm');</script>

<?php if ($card): ?>
    <?php
        $subjectsData = json_decode($card['subjects'], true) ?: [];
        $conductData  = json_decode($card['conduct'] ?? '{}', true) ?: [];
        $ga = grandAverage($card, $SUBJECTS);
    ?>
    <div class="card reveal">
        <div class="card-title-bar"><h2>Report Card - <?php echo htmlspecialchars($card['full_name']); ?></h2></div>

        <div class="grid grid-4" style="margin-bottom:20px;">
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($card['grade']); ?></div><div class="stat-label">Grade</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($card['section']); ?></div><div class="stat-label">Section</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo htmlspecialchars($card['school_year']); ?></div><div class="stat-label">School Year</div></div>
            <div class="stat-box"><div class="stat-value"><?php echo $ga !== null ? number_format($ga, 1) : '-'; ?></div><div class="stat-label">Grand Average</div></div>
        </div>

        <table class="data-table">
            <tr>
                <th style="text-align:left;">Subject</th>
                <th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Average</th>
            </tr>
            <?php foreach ($SUBJECTS as $s): ?>
                <?php $row = $subjectsData[$s] ?? []; $avg = subjectAverage($row); ?>
                <tr>
                    <td class="subject-name"><?php echo htmlspecialchars($s); ?></td>
                    <td><?php echo htmlspecialchars($row['1'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['2'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['3'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['4'] ?? '-'); ?></td>
                    <td><?php echo $avg !== null ? number_format($avg, 1) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="grid grid-2" style="margin-top:20px;">
            <div>
                <h3>Rank</h3>
                <p><?php echo ($rank && $rankTotal) ? "Rank $rank out of $rankTotal students" : "Not yet ranked (report card incomplete)"; ?></p>
            </div>
            <div>
                <h3>Promotion Status</h3>
                <p>
                    <?php if ($promotion === 'promoted'): ?>
                        <span class="tag tag-promoted"><?php echo htmlspecialchars($promotionLabel); ?></span>
                    <?php elseif ($promotion === 'detained'): ?>
                        <span class="tag tag-detained"><?php echo htmlspecialchars($promotionLabel); ?></span>
                    <?php else: ?>
                        <span class="tag tag-waiting"><?php echo htmlspecialchars($promotionLabel); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if (!empty($card['remarks'])): ?>
            <h3>Remarks</h3>
            <p><?php echo nl2br(htmlspecialchars($card['remarks'])); ?></p>
        <?php endif; ?>

        <button class="btn no-print" style="margin-top:15px;" onclick="printPage()"> Print Report Card</button>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
