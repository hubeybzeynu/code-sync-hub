<?php
$pageTitle = "News";
require"includes/db.php";
require"includes/auth.php";
requireStudentLogin();
$student = currentStudent($pdo);
if (!$student) { header("Location: logout.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $newsId = (int)$_POST['news_id'];
    $comment = trim($_POST['comment_text'] ?? '');
    if ($comment !== '') {
        $stmt = $pdo->prepare("INSERT INTO news_comments (news_id, student_id, comment) VALUES (?,?,?)");
        $stmt->execute([$newsId, $student['id'], $comment]);
    }
    header("Location: news.php");
    exit;
}

$posts = $pdo->prepare("
    SELECT n.* FROM news n
    WHERE n.school_id = ? OR n.school_id IS NULL
    ORDER BY n.created_at DESC");
$posts->execute([$student['school_id']]);
$posts = $posts->fetchAll();

$commentsByPost = [];
if ($posts) {
    $ids = implode(', ', array_map(fn($p) => (int)$p['id'], $posts));
    $c = $pdo->query("
        SELECT c.*, s.full_name, s.grade, s.section FROM news_comments c
        JOIN students s ON s.id = c.student_id
        WHERE c.news_id IN ($ids) ORDER BY c.created_at ASC
    ")->fetchAll();
    foreach ($c as $row) $commentsByPost[$row['news_id']][] = $row;
}

include"includes/header.php";
?>

<div class="welcome-hero" style="padding-top:16px; padding-bottom:20px;">
    <div class="welcome-icon"><svg style="width:34px;height:34px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="15" rx="1.5"/><path d="M16 8.5h5v9a2 2 0 0 1-2 2h-3"/><path d="M6.5 9h6M6.5 12h6M6.5 15h4"/></svg></div>
    <h1>News</h1>
    <p>Announcements from your school.</p></div><p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; News</p>

<div class="stagger-list"><?php foreach ($posts as $p): ?>
    <div class="card reveal">
        <h2><?php echo htmlspecialchars($p['title']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($p['body'])); ?></p>
        <p class="help-text"><?php echo htmlspecialchars($p['created_at']); ?></p>

        <h3 style="margin-top:20px;">Comments</h3>
        <?php foreach (($commentsByPost[$p['id']] ?? []) as $c): ?>
            <div style="padding:8px 0; border-top:1px solid var(--border);">
                <strong><?php echo htmlspecialchars($c['full_name']); ?></strong>
                <span class="help-text">(Grade <?php echo htmlspecialchars($c['grade']); ?>/<?php echo htmlspecialchars($c['section']); ?>)</span>
                <p style="margin:4px 0 0;"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
            </div>
        <?php endforeach; ?>

        <form method="post" action="news.php" style="margin-top:12px;">
            <input type="hidden" name="comment" value="1">
            <input type="hidden" name="news_id" value="<?php echo $p['id']; ?>">
            <div class="btn-row">
                <input type="text" name="comment_text" placeholder="Write a comment..." required style="flex:1; margin:0;">
                <button type="submit" class="btn btn-sm">Post</button>
            </div>
        </form>
    </div><?php endforeach; ?></div><?php if (!$posts): ?><div class="card reveal">No news yet.</div><?php endif; ?>

<?php include"includes/footer.php"; ?>
