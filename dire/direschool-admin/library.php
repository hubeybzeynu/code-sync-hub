<?php
require"includes/db.php";
require"includes/auth.php";
requireRole(['librarian', 'admin']);
$admin = currentAdmin($pdo);
$pageTitle = "Library";
$schoolId = (int)$admin['school_id'];

$error = "";
$success = "";

// ---- Create a folder (grade) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_folder'])) {
    $grade = trim($_POST['grade'] ?? '');
    if ($grade === '') {
        $error = "Grade is required to create a folder.";
    } else {
        $stmt = $pdo->prepare("INSERT IGNORE INTO textbook_folders (school_id, grade) VALUES (?, ?)");
        $stmt->execute([$schoolId, $grade]);
        $success = "Folder ready for Grade $grade.";
    }
}

// ---- Add a book into a folder (grade/type auto-filled from the folder) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $folderId = (int)$_POST['folder_id'];
    $name = trim($_POST['name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $driveLink = trim($_POST['drive_link'] ?? '');

    $f = $pdo->prepare("SELECT * FROM textbook_folders WHERE id=? AND school_id=? ");
    $f->execute([$folderId, $schoolId]);
    $folder = $f->fetch();

    if (!$folder || $name === '') {
        $error = "Please choose a folder and enter the book name.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO textbooks (folder_id, school_id, name, subject, grade, drive_link, uploaded_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$folderId, $schoolId, $name, $subject ?: null, $folder['grade'], $driveLink ?: null, $admin['id']]);
        $success = "Book added to Grade" . htmlspecialchars($folder['grade']) ." folder.";
    }
}

if (isset($_GET['delete_book'])) {
    $stmt = $pdo->prepare("DELETE FROM textbooks WHERE id=? AND school_id=? ");
    $stmt->execute([(int)$_GET['delete_book'], $schoolId]);
    header("Location: library.php");
    exit;
}
if (isset($_GET['delete_folder'])) {
    $stmt = $pdo->prepare("DELETE FROM textbook_folders WHERE id=? AND school_id=? ");
    $stmt->execute([(int)$_GET['delete_folder'], $schoolId]);
    header("Location: library.php");
    exit;
}

$folders = $pdo->query("SELECT * FROM textbook_folders WHERE school_id=$schoolId ORDER BY grade")->fetchAll();
$books = $pdo->query("SELECT * FROM textbooks WHERE school_id=$schoolId ORDER BY grade, subject")->fetchAll();
$booksByFolder = [];
foreach ($books as $b) $booksByFolder[$b['folder_id']][] = $b;

include"includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card form-narrow reveal">
    <h2>Create a Grade Folder</h2>
    <form method="post" action="library.php">
        <input type="hidden" name="add_folder" value="1">
        <div class="field"><label>Grade</label><input type="text" name="grade" placeholder="e.g. 9" required></div>
        <button type="submit" class="btn btn-full">Create Folder</button>
    </form></div>

<div class="card reveal">
    <h2>Add a Book</h2>
    <p class="help-text">Grade is filled in automatically from the folder you pick — the folder already tells us the grade, so you only need to type the book's name, subject and (optionally) a Google Drive link to preview/download it.</p>
    <form method="post" action="library.php" class="grid grid-4">
        <div class="field">
            <label>Folder (Grade)</label>
            <select name="folder_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?php echo $f['id']; ?>">Grade <?php echo htmlspecialchars($f['grade']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Book Name</label><input type="text" name="name" required></div>
        <div class="field"><label>Subject (optional)</label><input type="text" name="subject"></div>
        <div class="field"><label>Google Drive Link (optional)</label><input type="text" name="drive_link" placeholder="https://drive.google.com/..."></div>
        <div class="field" style="grid-column:1/-1;"><button type="submit" name="add_book" value="1" class="btn">Add Book</button></div>
    </form></div>

<?php foreach ($folders as $f): ?>
    <div class="card reveal">
        <div class="btn-row" style="justify-content:space-between;">
            <h2 style="margin:0;"> Grade <?php echo htmlspecialchars($f['grade']); ?></h2>
            <a class="btn btn-sm btn-danger confirm-delete" href="library.php?delete_folder=<?php echo $f['id']; ?>">Delete Folder</a>
        </div>
        <table class="data-table" style="margin-top:12px;">
            <tr><th>Book</th><th>Subject</th><th>Drive Link</th><th class="center">Actions</th></tr>
            <?php foreach (($booksByFolder[$f['id']] ?? []) as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['name']); ?></td>
                    <td><?php echo htmlspecialchars($b['subject'] ?: '-'); ?></td>
                    <td><?php echo $b['drive_link'] ? '<a href="' . htmlspecialchars($b['drive_link']) .'" target="_blank">Open</a>' : '<span class="help-text">No soft copy yet</span>'; ?></td>
                    <td class="center"><a class="btn btn-sm btn-danger confirm-delete" href="library.php?delete_book=<?php echo $b['id']; ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($booksByFolder[$f['id']])): ?><tr><td colspan="4">No books yet in this folder.</td></tr><?php endif; ?>
        </table>
    </div><?php endforeach; ?><?php if (!$folders): ?><div class="card reveal">No folders yet — create one above.</div><?php endif; ?>

<?php include"includes/footer.php"; ?>
