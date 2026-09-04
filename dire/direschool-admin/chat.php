<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin', 'subadmin', 'teacher', 'librarian', 'staff', 'superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Messages";
$myType = $admin['role'];
$myId = (int)$admin['id'];

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $toType = trim($_POST['to_type'] ?? '');
    $toId = (int)($_POST['to_id'] ?? 0);
    $body = trim($_POST['body'] ?? '');
    $validTypes = ['admin', 'subadmin', 'teacher', 'librarian', 'staff', 'superadmin', 'parent'];
    if (!in_array($toType, $validTypes, true) || !$toId || $body === '') {
        $error = "Please choose a recipient and type a message.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_type, sender_id, receiver_type, receiver_id, body) VALUES (?,?,?,?,?)");
        $stmt->execute([$myType, $myId, $toType, $toId, $body]);
        header("Location: chat.php?with_type=$toType&with_id=$toId");
        exit;
    }
}

// Possible recipients: everyone else at the same school (plus any superadmin), matching
// the pairings described: teacher<->admin, staff<->teacher/admin, subadmin<->admin, etc.
$schoolCond = $admin['school_id'] ? "(school_id = " . (int)$admin['school_id'] . " OR role = 'superadmin')" : "1=1";
$contacts = $pdo->query("SELECT id, full_name, email, role FROM admins WHERE id != $myId AND $schoolCond ORDER BY role, full_name")->fetchAll();

// Parents of students at this school are also reachable — this is how a
// teacher/subadmin "gets the family" or an admin follows up after a
// conduct signature, and how a parent's message back reaches the school.
$parentContacts = [];
if ($admin['school_id']) {
    $parentContacts = $pdo->query("
        SELECT DISTINCT p.id, p.full_name, p.email, s.full_name AS child_name
        FROM parents p
        JOIN parent_students ps ON ps.parent_id = p.id
        JOIN students s ON s.id = ps.student_id
        WHERE s.school_id = " . (int)$admin['school_id'] . "
        ORDER BY p.full_name
    ")->fetchAll();
}

$withType = $_GET['with_type'] ?? '';
$withId = isset($_GET['with_id']) ? (int)$_GET['with_id'] : 0;
$thread = [];
if ($withType && $withId) {
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages
        WHERE (sender_type=? AND sender_id=? AND receiver_type=? AND receiver_id=?)
           OR (sender_type=? AND sender_id=? AND receiver_type=? AND receiver_id=?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$myType, $myId, $withType, $withId, $withType, $withId, $myType, $myId]);
    $thread = $stmt->fetchAll();
    // mark incoming as read
    $pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE sender_type=? AND sender_id=? AND receiver_type=? AND receiver_id=? ")
        ->execute([$withType, $withId, $myType, $myId]);
}

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="grid grid-3">
    <div class="card" style="grid-column: span 1;">
        <h2>Contacts</h2>
        <?php foreach ($contacts as $c): ?>
            <a class="list-item" style="display:block; margin-bottom:8px;" href="chat.php?with_type=<?php echo $c['role']; ?>&with_id=<?php echo $c['id']; ?>">
                <strong><?php echo htmlspecialchars($c['full_name']); ?></strong><br>
                <?php echo roleBadge($c['role']); ?>
            </a>
        <?php endforeach; ?>
        <?php if (!$contacts): ?><p class="help-text">No other staff accounts yet.</p><?php endif; ?>

        <h2 style="margin-top:22px;">Parents</h2>
        <?php foreach ($parentContacts as $p): ?>
            <a class="list-item" style="display:block; margin-bottom:8px;" href="chat.php?with_type=parent&with_id=<?php echo $p['id']; ?>">
                <strong><?php echo htmlspecialchars($p['full_name']); ?></strong><br>
                <span class="help-text">Parent of <?php echo htmlspecialchars($p['child_name']); ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (!$parentContacts): ?><p class="help-text">No linked parents yet.</p><?php endif; ?>
    </div>

    <div class="card" style="grid-column: span 2;">
        <?php if (!$withType || !$withId): ?>
            <p>Select a contact to view or start a conversation.</p>
        <?php else: ?>
            <h2>Conversation</h2>
            <div style="max-height:400px; overflow-y:auto; margin-bottom:16px;">
                <?php foreach ($thread as $m): ?>
                    <div style="margin-bottom:10px; text-align:<?php echo ($m['sender_type']===$myType && (int)$m['sender_id']===$myId) ? 'right' : 'left'; ?>;">
                        <span style="display:inline-block; background:<?php echo ($m['sender_type']===$myType && (int)$m['sender_id']===$myId) ? 'var(--accent-soft)' : '#F0F2F8'; ?>; padding:8px 14px; border-radius:12px; max-width:80%;">
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
