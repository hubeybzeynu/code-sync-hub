<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin', 'subadmin', 'superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "News";
$isSuper = isSuperAdmin($admin);

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $schoolId = $isSuper ? ($_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null) : (int)$admin['school_id'];

    if ($title === '' || $body === '') {
        $error = "Title and message are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO news (school_id, posted_by, title, body) VALUES (?,?,?,?)");
        $stmt->execute([$schoolId, $admin['id'], $title, $body]);
        $success = "News posted.";
    }
}

if (isset($_GET['delete'])) {
    $cond = $isSuper ? "id=? " : "id=? AND school_id=" . (int)$admin['school_id'];
    $stmt = $pdo->prepare("DELETE FROM news WHERE $cond");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: news.php");
    exit;
}

$schools = $isSuper ? $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll() : [];

$sql = "SELECT n.*, sc.name AS school_name, a.full_name AS author,
        (SELECT COUNT(*) FROM news_comments c WHERE c.news_id = n.id) AS comment_count
        FROM news n LEFT JOIN schools sc ON sc.id = n.school_id LEFT JOIN admins a ON a.id = n.posted_by";
if (!$isSuper) $sql .= " WHERE n.school_id = " . (int)$admin['school_id'];
$sql .= " ORDER BY n.created_at DESC";
$posts = $pdo->query($sql)->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card form-narrow reveal">
    <h2>Post News</h2>
    <form method="post" action="news.php">
        <input type="hidden" name="save" value="1">
        <?php if ($isSuper): ?>
            <div class="field">
                <label>School (leave blank for a system-wide announcement)</label>
                <select name="school_id">
                    <option value="">-- All Schools --</option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field"><label>Title</label><input type="text" name="title" required></div>
        <div class="field"><label>Message</label><textarea name="body" required></textarea></div>
        <button type="submit" class="btn btn-full">Post to the Student Portal</button>
    </form>
</div>

<div class="card reveal">
    <h2>Posted News</h2>
    <?php foreach ($posts as $p): ?>
        <div style="border-bottom:1px solid var(--border); padding:14px 0;">
            <div class="btn-row" style="justify-content:space-between;">
                <h3 style="margin:4px 0;"><?php echo htmlspecialchars($p['title']); ?></h3>
                <a class="btn btn-sm btn-danger confirm-delete" href="news.php?delete=<?php echo $p['id']; ?>">Delete</a>
            </div>
            <p><?php echo nl2br(htmlspecialchars($p['body'])); ?></p>
            <p class="help-text">
                <?php echo htmlspecialchars($p['school_name'] ?? 'All Schools'); ?> &middot;
                by <?php echo htmlspecialchars($p['author'] ?? '-'); ?> &middot;
                <?php echo htmlspecialchars($p['created_at']); ?> &middot;
                <?php echo (int)$p['comment_count']; ?> comment(s)
            </p>
        </div>
    <?php endforeach; ?>
    <?php if (!$posts): ?><p>No news posted yet.</p><?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
