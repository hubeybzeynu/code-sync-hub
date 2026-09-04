<?php
$pageTitle = "Parent Dashboard";
require"includes/db.php";
require"includes/auth.php";
requireParentLogin();
$parent = currentParent($pdo);
if (!$parent) { header("Location: logout.php"); exit; }

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_child'])) {
    $studentNo = trim($_POST['student_no'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_no = ? LIMIT 1");
    $stmt->execute([$studentNo]);
    $child = $stmt->fetch();

    if (!$child || !$child['password_hash'] || !password_verify($password, $child['password_hash'])) {
        $error = "We couldn't verify that Student ID and Password.";
    } else {
        $ins = $pdo->prepare("INSERT IGNORE INTO parent_students (parent_id, student_id) VALUES (?,?)");
        $ins->execute([$parent['id'], $child['id']]);
        $success = "Linked to" . htmlspecialchars($child['full_name']) .".";
    }
}

$children = $pdo->prepare("
    SELECT s.*, sc.name AS school_name FROM parent_students ps
    JOIN students s ON s.id = ps.student_id
    JOIN schools sc ON sc.id = s.school_id
    WHERE ps.parent_id = ? ");
$children->execute([$parent['id']]);
$children = $children->fetchAll();

$viewId = isset($_GET['child']) ? (int)$_GET['child'] : ($children[0]['id'] ?? 0);
$viewChild = null;
$card = null;
$conduct = [];
$attendance = [];
foreach ($children as $c) if ((int)$c['id'] === $viewId) $viewChild = $c;

if ($viewChild) {
    $cd = $pdo->prepare("SELECT * FROM report_cards WHERE student_id=? ORDER BY school_year DESC LIMIT 1");
    $cd->execute([$viewChild['id']]);
    $card = $cd->fetch();

    $co = $pdo->prepare("SELECT * FROM student_conduct WHERE student_id=? ORDER BY created_at DESC LIMIT 20");
    $co->execute([$viewChild['id']]);
    $conduct = $co->fetchAll();

    $at = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id=? ORDER BY attend_date DESC LIMIT 20");
    $at->execute([$viewChild['id']]);
    $attendance = $at->fetchAll();
}

include"includes/header.php";
?>

<div class="welcome-hero">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></div>
    <h1>Welcome, <?php echo htmlspecialchars($parent['full_name']); ?></h1>
    <p>Link your child using their Student ID and password, then follow their attendance, conduct and report card right here.</p></div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card form-narrow reveal">
    <h2>Link a Child</h2>
    <form method="post" action="index.php">
        <input type="hidden" name="link_child" value="1">
        <div class="field"><label>Child's Student ID</label><input type="text" name="student_no" required></div>
        <div class="field"><label>Child's Password</label><input type="password" name="password" required></div>
        <button type="submit" class="btn btn-full">Link Child</button>
    </form></div>

<?php if ($children): ?>
    <div class="btn-row" style="margin-bottom:20px;">
        <?php foreach ($children as $c): ?>
            <a class="btn <?php echo (int)$c['id']===$viewId? '':'btn-outline'; ?>" href="index.php?child=<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></a>
        <?php endforeach; ?>
    </div><?php endif; ?>

<?php if ($viewChild): ?>
    <div class="card reveal">
        <h2><?php echo htmlspecialchars($viewChild['full_name']); ?></h2>
        <p><?php echo htmlspecialchars($viewChild['school_name']); ?> &middot; Grade <?php echo htmlspecialchars($viewChild['grade']); ?>/<?php echo htmlspecialchars($viewChild['section']); ?></p>
    </div>

    <div class="card reveal">
        <h2>Report Card</h2>
        <?php if (!$card): ?>
            <p>No report card yet.</p>
        <?php else: ?>
            <p>Result:
                <?php if ($card['promotion_status'] === 'promoted'): ?><span class="tag tag-promoted">Promoted</span>
                <?php elseif ($card['promotion_status'] === 'detained'): ?><span class="tag tag-detained">Detained</span>
                <?php else: ?><span class="tag tag-waiting">Waiting for Q4</span><?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="card table-scroll reveal">
        <h2>Recent Conduct</h2>
        <table class="data-table">
            <tr><th>Date</th><th>Type</th><th>Note</th></tr>
            <?php foreach ($conduct as $c): ?>
                <tr><td><?php echo htmlspecialchars($c['created_at']); ?></td><td><?php echo htmlspecialchars($c['type']); ?></td><td style="text-align:left;"><?php echo htmlspecialchars($c['note'] ?: '-'); ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$conduct): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
        </table>
    </div>

    <div class="card table-scroll reveal">
        <h2>Recent Attendance</h2>
        <table class="data-table">
            <tr><th>Date</th><th>Status</th></tr>
            <?php foreach ($attendance as $a): ?>
                <tr><td><?php echo htmlspecialchars($a['attend_date']); ?></td><td><?php echo htmlspecialchars($a['status']); ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$attendance): ?><tr><td colspan="2">None yet.</td></tr><?php endif; ?>
        </table>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
