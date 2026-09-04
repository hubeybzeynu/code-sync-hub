<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Messages from Schools";

if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE school_messages SET status='read' WHERE id=? ");
    $stmt->execute([(int)$_GET['read']]);
    header("Location: school-messages.php");
    exit;
}
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM school_messages WHERE id=? ");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: school-messages.php");
    exit;
}

$messages = $pdo->query("
    SELECT m.*, sc.name AS school_name, a.full_name AS sender_name
    FROM school_messages m
    JOIN schools sc ON sc.id = m.school_id
    LEFT JOIN admins a ON a.id = m.sender_id
    ORDER BY m.status = 'unread' DESC, m.created_at DESC
")->fetchAll();

include "includes/header.php";
?>

<div class="card table-scroll reveal">
    <table class="data-table">
        <tr><th class="center">Status</th><th>School</th><th>Subject</th><th>Message</th><th>From</th><th class="center">Actions</th></tr>
        <?php foreach ($messages as $m): ?>
            <tr>
                <td class="center"><span class="tag tag-<?php echo $m['status']==='unread'? 'pending':'active'; ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                <td><?php echo htmlspecialchars($m['school_name']); ?></td>
                <td><?php echo htmlspecialchars($m['subject']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($m['body'])); ?></td>
                <td><?php echo htmlspecialchars($m['sender_name'] ?? '-'); ?></td>
                <td class="center">
                    <div class="table-actions">
                        <?php if ($m['status'] === 'unread'): ?>
                            <a class="btn btn-sm btn-outline" href="school-messages.php?read=<?php echo $m['id']; ?>">Mark Read</a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-danger confirm-delete" href="school-messages.php?delete=<?php echo $m['id']; ?>">Delete</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$messages): ?><tr><td colspan="6">No messages yet.</td></tr><?php endif; ?>
    </table>
</div>

<?php include "includes/footer.php"; ?>
