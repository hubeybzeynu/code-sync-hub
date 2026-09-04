<?php
require "includes/db.php";
require "includes/auth.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Import Ministry Results";

$error = "";
$success = "";
$preview = [];

// Expected CSV columns: student_no,student_name,school_year,Amharic,English,Mathematics,General Science,Social Studies,HPE & Arts
$SUBJECT_COLS = ["Amharic", "English", "Mathematics", "General Science", "Social Studies", "HPE & Arts"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $tmpPath = $_FILES['csv_file']['tmp_name'];
    if (!$tmpPath || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please choose a CSV file to upload.";
    } else {
        $rows = [];
        if (($handle = fopen($tmpPath, "r")) !== false) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($header, $data);
                if ($row) $rows[] = $row;
            }
            fclose($handle);
        }

        if (!$rows) {
            $error = "No data rows found in that CSV file.";
        } else {
            $inserted = 0;
            foreach ($rows as $row) {
                $studentNo = trim($row['student_no'] ?? '');
                $studentName = trim($row['student_name'] ?? '');
                if ($studentNo === '') continue;

                $subjects = [];
                $total = 0; $count = 0;
                foreach ($SUBJECT_COLS as $col) {
                    if (isset($row[$col]) && $row[$col] !== '') {
                        $val = (float)$row[$col];
                        $subjects[$col] = $val;
                        $total += $val;
                        $count++;
                    }
                }
                $average = $count ? $total / $count : null;

                // Try to link to an existing student record by student_no
                $sStmt = $pdo->prepare("SELECT id, school_id FROM students WHERE student_no = ? LIMIT 1");
                $sStmt->execute([$studentNo]);
                $studentRow = $sStmt->fetch();

                $ins = $pdo->prepare("INSERT INTO ministry_results (school_id, student_id, student_no, student_name, grade, school_year, subjects, total, average, promotion_status, source) VALUES (?,?,?,?, '8',?,?,?,?,?, 'import')");
                $ins->execute([
                    $studentRow['school_id'] ?? null,
                    $studentRow['id'] ?? null,
                    $studentNo,
                    $studentName,
                    trim($row['school_year'] ?? ''),
                    json_encode($subjects),
                    $total,
                    $average,
                    ($average !== null && $average >= 50) ? 'promoted' : 'not_promoted',
                ]);
                $inserted++;
            }

            $log = $pdo->prepare("INSERT INTO ministry_result_imports (imported_by, filename, row_count) VALUES (?,?,?)");
            $log->execute([$admin['id'], $_FILES['csv_file']['name'], $inserted]);

            $success = "Imported $inserted ministry result row(s) from " . htmlspecialchars($_FILES['csv_file']['name']) . ".";
        }
    }
}

$imports = $pdo->query("
    SELECT i.*, a.full_name AS imported_by_name
    FROM ministry_result_imports i
    LEFT JOIN admins a ON a.id = i.imported_by
    ORDER BY i.created_at DESC LIMIT 20
")->fetchAll();

include "includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="alert alert-info">
    This is a manual stand-in for the counting machine that will scan Grade 8 answer sheets and report results
    automatically later. For now, upload a CSV file with these column headers on the first row:
    <br><code>student_no,student_name,school_year,Amharic,English,Mathematics,General Science,Social Studies,HPE &amp; Arts</code>
</div>

<div class="card form-narrow reveal">
    <h2>Upload CSV</h2>
    <form method="post" action="ministry-import.php" enctype="multipart/form-data">
        <div class="field"><label for="csv_file">CSV File</label><input type="file" id="csv_file" name="csv_file" accept=".csv" required></div>
        <button type="submit" class="btn btn-full">Import Results</button>
    </form>
</div>

<div class="card table-scroll reveal">
    <h2>Import History</h2>
    <table class="data-table">
        <tr><th>File</th><th class="center">Rows</th><th>Imported By</th><th>When</th></tr>
        <?php foreach ($imports as $i): ?>
            <tr>
                <td><?php echo htmlspecialchars($i['filename']); ?></td>
                <td class="center"><?php echo $i['row_count']; ?></td>
                <td><?php echo htmlspecialchars($i['imported_by_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($i['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$imports): ?><tr><td colspan="4">No imports yet.</td></tr><?php endif; ?>
    </table>
</div>

<?php include "includes/footer.php"; ?>
