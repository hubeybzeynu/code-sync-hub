<?php
$pageTitle = "My Report Card";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

$SUBJECTS = [
    "Amharic", "English", "Mathematics", "General Science", "Social Studies",
    "Citizenship Education", "Performing & Visual Arts", "Information Technology",
    "Health & Physical Education", "Career & Technical Education",
];

function subjAvg($row) {
    if (!$row) return null;
    $vals = array_filter([$row['1'] ?? null, $row['2'] ?? null, $row['3'] ?? null, $row['4'] ?? null], fn($v) => is_numeric($v) && $v > 0);
    return count($vals) ? array_sum($vals) / count($vals) : null;
}

$card = $pdo->prepare("SELECT * FROM report_cards WHERE student_id=? ORDER BY school_year DESC LIMIT 1");
$card->execute([$student['id']]);
$card = $card->fetch();

$conduct = $pdo->prepare("SELECT * FROM student_conduct WHERE student_id=? ORDER BY created_at DESC");
$conduct->execute([$student['id']]);
$conduct = $conduct->fetchAll();
$signatureCount = count(array_filter($conduct, fn($c) => $c['type'] === 'signature'));
$warningCount = count(array_filter($conduct, fn($c) => $c['type'] === 'warning'));

$attendance = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id=? ORDER BY attend_date DESC LIMIT 60");
$attendance->execute([$student['id']]);
$attendance = $attendance->fetchAll();
$presentCount = count(array_filter($attendance, fn($a) => $a['status'] === 'present'));
$absentCount = count(array_filter($attendance, fn($a) => $a['status'] === 'absent'));

$tab = $_GET['tab'] ?? 'marks';

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M12 20V4M20 20v-7"/><path d="M4 20h16"/></svg></div>
    <h1>My Report Card</h1>
    <p>Marks, conduct and attendance — all in one place.</p></div><p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Report Card</p>

<div class="btn-row" style="margin-bottom:20px;">
    <a class="btn <?php echo $tab==='marks'? '':'btn-outline'; ?>" href="report-card.php?tab=marks">Marks</a>
    <a class="btn <?php echo $tab==='conduct'? '':'btn-outline'; ?>" href="report-card.php?tab=conduct">Conduct</a>
    <a class="btn <?php echo $tab==='attendance'? '':'btn-outline'; ?>" href="report-card.php?tab=attendance">Attendance</a></div>

<?php if ($tab === 'marks'): ?>
    <?php if (!$card): ?>
        <div class="card reveal">No report card has been entered yet.</div>
    <?php else: ?>
        <?php $subjects = json_decode($card['subjects'] ?? '{}', true) ?: []; ?>
        <div class="card reveal">
            <table class="data-table">
                <tr><th style="text-align:left;">Subject</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Average</th></tr>
                <?php foreach ($SUBJECTS as $s): ?>
                    <?php $row = $subjects[$s] ?? []; $avg = subjAvg($row); ?>
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
            <p style="margin-top:14px;">
                Result:
                <?php if ($card['promotion_status'] === 'promoted'): ?><span class="tag tag-promoted">Promoted</span>
                <?php elseif ($card['promotion_status'] === 'detained'): ?><span class="tag tag-detained">Detained</span>
                <?php else: ?><span class="tag tag-waiting">Waiting for Q4</span><?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'conduct'): ?>
    <div class="grid grid-3 stagger-list" style="margin-bottom:20px;">
        <div class="stat-box"><div class="stat-value"><?php echo $signatureCount; ?></div><div class="stat-label">Signatures</div></div>
        <div class="stat-box"><div class="stat-value"><?php echo $warningCount; ?></div><div class="stat-label">Warnings</div></div>
        <div class="stat-box"><div class="stat-value"><?php echo count($conduct); ?></div><div class="stat-label">Total Entries</div></div>
    </div>
    <?php if ($signatureCount >= 3): ?>
        <div class="alert alert-error">You have reached <?php echo $signatureCount; ?> signatures — your family has been asked to meet with the school.</div>
    <?php endif; ?>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Date</th><th>Type</th><th>Note</th></tr>
            <?php foreach ($conduct as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                    <td><span class="tag tag-<?php echo $c['type']==='signature'? 'detained':($c['type']==='warning'? 'waiting':'active'); ?>"><?php echo htmlspecialchars($c['type']); ?></span></td>
                    <td style="text-align:left;"><?php echo htmlspecialchars($c['note'] ?: '-'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$conduct): ?><tr><td colspan="3">No conduct entries yet.</td></tr><?php endif; ?>
        </table>
    </div>

<?php else: ?>
    <div class="grid grid-2 stagger-list" style="margin-bottom:20px;">
        <div class="stat-box"><div class="stat-value" style="color:#17A673;"><?php echo $presentCount; ?></div><div class="stat-label">Days Present (last 60)</div></div>
        <div class="stat-box"><div class="stat-value" style="color:#E5484D;"><?php echo $absentCount; ?></div><div class="stat-label">Days Absent (last 60)</div></div>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Date</th><th>Status</th></tr>
            <?php foreach ($attendance as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['attend_date']); ?></td>
                    <td><span class="tag tag-<?php echo $a['status']==='present'? 'active':'blocked'; ?>"><?php echo htmlspecialchars($a['status']); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$attendance): ?><tr><td colspan="2">No attendance recorded yet.</td></tr><?php endif; ?>
        </table>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
