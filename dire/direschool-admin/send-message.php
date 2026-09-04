<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['admin', 'subadmin', 'staff']);
$admin = currentAdmin($pdo);
$pageTitle = "Message the Super Admin";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($subject === '' || $body === '') {
        $error = "Subject and message are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO school_messages (school_id, sender_id, subject, body) VALUES (?,?,?,?)");
        $stmt->execute([$admin['school_id'], $admin['id'], $subject, $body]);
        $success = "Message sent to the Super Admin.";
    }
}

$mine = $pdo->query("SELECT * FROM school_messages WHERE school_id=" . (int)$admin['school_id'] . " ORDER BY created_at DESC")->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card form-narrow reveal">
    <h2>New Message</h2>
    <form method="post" action="send-message.php">
        <input type="hidden" name="send" value="1">
        <div class="field"><label>Subject</label><input type="text" name="subject" required></div>
        <div class="field"><label>Message</label><textarea name="body" required></textarea></div>
        <button type="submit" class="btn btn-full">Send to Super Admin</button>
    </form>
</div>

<div class="card table-scroll reveal">
    <h2>Sent Messages</h2>
    <table class="data-table">
        <tr><th>Subject</th><th>Message</th><th class="center">Status</th><th>When</th></tr>
        <?php foreach ($mine as $m): ?>
            <tr>
                <td><?php echo htmlspecialchars($m['subject']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($m['body'])); ?></td>
                <td class="center"><span class="tag tag-<?php echo $m['status']==='read'? 'active':'pending'; ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                <td><?php echo htmlspecialchars($m['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$mine): ?><tr><td colspan="4">No messages sent yet.</td></tr><?php endif; ?>
    </table>
</div>

<?php include "includes/footer.php"; ?>
