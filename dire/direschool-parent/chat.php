<?php
$pageTitle = "Messages";
require "includes/db.php";
require "includes/auth.php";
requireParentLogin();
$parent = currentParent($pdo);
if (!$parent) { header("Location: logout.php"); exit; }

$myId = (int)$parent['id'];

// Staff reachable: anyone at the schools of this parent's linked children.
$contacts = $pdo->query("
    SELECT DISTINCT a.id, a.full_name, a.role, s.name AS school_name
    FROM admins a
    JOIN students st ON st.school_id = a.school_id
    JOIN parent_students ps ON ps.student_id = st.id
    JOIN schools s ON s.id = a.school_id
    WHERE ps.parent_id = $myId AND a.role IN ('admin', 'subadmin', 'teacher')
    ORDER BY a.role, a.full_name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $toType = trim($_POST['to_type'] ?? '');
    $toId = (int)($_POST['to_id'] ?? 0);
    $body = trim($_POST['body'] ?? '');
    if (in_array($toType, ['admin', 'subadmin', 'teacher'], true) && $toId && $body !== '') {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_type, sender_id, receiver_type, receiver_id, body) VALUES ('parent', ?, ?, ?, ?)");
        $stmt->execute([$myId, $toType, $toId, $body]);
        header("Location: chat.php?with_type=$toType&with_id=$toId");
        exit;
    }
}

$withType = $_GET['with_type'] ?? '';
$withId = isset($_GET['with_id']) ? (int)$_GET['with_id'] : 0;
$thread = [];
if ($withType && $withId) {
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages
        WHERE (sender_type='parent' AND sender_id=? AND receiver_type=? AND receiver_id=?)
           OR (sender_type=? AND sender_id=? AND receiver_type='parent' AND receiver_id=?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$myId, $withType, $withId, $withType, $withId, $myId]);
    $thread = $stmt->fetchAll();
}

include "includes/header.php";
?>

<h1>Messages</h1>
<p class="breadcrumb"><a href="index.php">Dashboard</a> &raquo; Messages</p>

<div class="grid grid-3">
    <div class="card" style="grid-column: span 1;">
        <h2>Contacts</h2>
        <?php foreach ($contacts as $c): ?>
            <a class="list-item" style="display:block; margin-bottom:8px;" href="chat.php?with_type=<?php echo $c['role']; ?>&with_id=<?php echo $c['id']; ?>">
                <strong><?php echo htmlspecialchars($c['full_name']); ?></strong><br>
                <span class="help-text"><?php echo htmlspecialchars(ucfirst($c['role'])); ?> at <?php echo htmlspecialchars($c['school_name']); ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (!$contacts): ?><p class="help-text">Link a child first to see their school's staff here.</p><?php endif; ?>
    </div>
    <div class="card" style="grid-column: span 2;">
        <?php if (!$withType || !$withId): ?>
            <p>Select a contact to view or start a conversation.</p>
        <?php else: ?>
            <div style="max-height:400px; overflow-y:auto; margin-bottom:16px;">
                <?php foreach ($thread as $m): ?>
                    <div style="margin-bottom:10px; text-align:<?php echo $m['sender_type']==='parent' ? 'right' : 'left'; ?>;">
                        <span style="display:inline-block; background:<?php echo $m['sender_type']==='parent' ? 'var(--accent-soft)' : '#F0F2F8'; ?>; padding:8px 14px; border-radius:12px; max-width:80%;">
                            <?php echo nl2br(htmlspecialchars($m['body'])); ?>
                        </span>
                        <div class="help-text"><?php echo htmlspecialchars($m['created_at']); ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$thread): ?><p class="help-text">No messages yet — say hello.</p><?php endif; ?>
            </div>
            <form method="post" action="chat.php">
                <input type="hidden" name="send" value="1">
                <input type="hidden" name="to_type" value="<?php echo htmlspecialchars($withType); ?>">
                <input type="hidden" name="to_id" value="<?php echo $withId; ?>">
                <div class="btn-row">
                    <input type="text" name="body" placeholder="Type a message..." required style="flex:1; margin:0;">
                    <button type="submit" class="btn">Send</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>
