<?php
require"includes/db.php";
require"includes/auth.php";
require dirname(__DIR__) . "/includes/school-helpers.php";
requireRole(['superadmin']);
$admin = currentAdmin($pdo);
$pageTitle = "Schools";

schoolEnsureColumns($pdo);
$templates = schoolTemplates();

$error = "";
$success = "";

// ---- Handle add/edit school ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_school'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $logo = trim($_POST['logo_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $websiteMode = in_array($_POST['website_mode'] ?? 'none', ['none', 'template', 'html'], true) ? $_POST['website_mode'] : 'none';
    $websiteTemplate = isset(schoolTemplates()[$_POST['website_template'] ?? '']) ? $_POST['website_template'] : null;
    $websiteHtml = $_POST['website_html'] ?? '';

    if ($name === '' || $slug === '') {
        $error = "School name and slug are required.";
    } else {
        if ($id) {
            $cur = $pdo->prepare("SELECT cover_image FROM schools WHERE id=?");
            $cur->execute([$id]);
            $cover = $cur->fetch()['cover_image'] ?? null;
            if (!empty($_FILES['cover_image']['name'])) { $p = saveSchoolImage($_FILES['cover_image'], $id, 'cover', $error); if ($p) $cover = $p; }
            if (!empty($_FILES['logo_file']['name']))   { $p = saveSchoolImage($_FILES['logo_file'], $id, 'logo', $error);   if ($p) $logo = $p; }
            if (!$error) {
                $stmt = $pdo->prepare("UPDATE schools SET name=?, slug=?, logo_url=?, description=?, cover_image=?, website_mode=?, website_template=?, website_html=? WHERE id=?");
                $stmt->execute([$name, $slug, $logo ?: null, $description ?: null, $cover, $websiteMode, $websiteTemplate, $websiteHtml ?: null, $id]);
                header("Location: schools.php?edit=$id&saved=1");
                exit;
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO schools (name, slug, logo_url, description, website_mode, website_template, website_html) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$name, $slug, $logo ?: null, $description ?: null, $websiteMode, $websiteTemplate, $websiteHtml ?: null]);
            $newId = $pdo->lastInsertId();
            $cover = null;
            if (!empty($_FILES['cover_image']['name'])) { $p = saveSchoolImage($_FILES['cover_image'], $newId, 'cover', $error); if ($p) $cover = $p; }
            if (!empty($_FILES['logo_file']['name']))   { $p = saveSchoolImage($_FILES['logo_file'], $newId, 'logo', $error);   if ($p) $logo = $p; }
            if (!$error) {
                $stmt = $pdo->prepare("UPDATE schools SET cover_image=?, logo_url=? WHERE id=?");
                $stmt->execute([$cover, $logo ?: null, $newId]);
            }
            if (!$error) {
                header("Location: schools.php?edit=$newId&saved=1");
                exit;
            }
        }
    }
}

// ---- Handle add section ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $schoolId = (int)$_POST['school_id'];
    $grade = trim($_POST['grade']);
    $section = trim($_POST['section']);
    if ($grade !== '' && $section !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO sections (school_id, grade, section) VALUES (?,?,?)");
        $stmt->execute([$schoolId, $grade, $section]);
        $success = "Section added.";
    }
}

// ---- Handle BAN a school (reason + re-typed superadmin password) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_school'])) {
    $schoolId = (int)$_POST['school_id'];
    $reason = trim($_POST['ban_reason'] ?? '');
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($reason === '' || $confirmPassword === '') {
        $error = "A reason and your password are both required to ban a school.";
    } elseif (!password_verify($confirmPassword, $admin['password_hash'])) {
        $error = "Your password did not match — the school was NOT banned.";
    } else {
        $stmt = $pdo->prepare("UPDATE schools SET status='blocked', ban_reason=?, banned_by=?, banned_at=NOW() WHERE id=? ");
        $stmt->execute([$reason, $admin['id'], $schoolId]);
        header("Location: schools.php?edit=$schoolId&banned=1");
        exit;
    }
}

// ---- Handle UNBAN ----
if (isset($_GET['unban'])) {
    $stmt = $pdo->prepare("UPDATE schools SET status='active', ban_reason=NULL, banned_by=NULL, banned_at=NULL WHERE id=? ");
    $stmt->execute([(int)$_GET['unban']]);
    header("Location: schools.php?edit=" . (int)$_GET['unban'] ."&unbanned=1");
    exit;
}

// ---- Handle delete ----
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ? ");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: schools.php");
    exit;
}
if (isset($_GET['delete_section'])) {
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ? ");
    $stmt->execute([(int)$_GET['delete_section']]);
    header("Location: schools.php" . (isset($_GET['edit']) ? "?edit=" . (int)$_GET['edit'] : ""));
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['action']) && $_GET['action'] === 'add' ? 0 : null);
$editSchool = null;
$sectionsForEdit = [];
$studentsForEdit = [];
$pendingTransferCount = 0;
if ($editId === 0 && !$editSchool) {
    $editSchool = []; // placeholder so the "add new school" form renders below
}
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ? ");
    $stmt->execute([$editId]);
    $editSchool = $stmt->fetch();
    if ($editSchool) {
        $sStmt = $pdo->prepare("SELECT * FROM sections WHERE school_id = ? ORDER BY grade, section");
        $sStmt->execute([$editId]);
        $sectionsForEdit = $sStmt->fetchAll();
        $stStmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY grade, section, full_name");
        $stStmt->execute([$editId]);
        $studentsForEdit = $stStmt->fetchAll();
        $tc = $pdo->prepare("SELECT COUNT(*) c FROM school_transfer_requests WHERE (from_school_id=? OR to_school_id=?) AND status='pending'");
        $tc->execute([$editId, $editId]);
        $pendingTransferCount = $tc->fetch()['c'];
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT s.*, (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id) AS student_count FROM schools s";
if ($statusFilter === 'blocked') $sql .= " WHERE s.status = 'blocked'";
$sql .= " ORDER BY s.name";
$schools = $pdo->query($sql)->fetchAll();

include"includes/header.php";
?>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?><?php if (isset($_GET['saved'])): ?><div class="alert alert-success">School saved.</div><?php endif; ?><?php if (isset($_GET['student_saved'])): ?><div class="alert alert-success">Student saved.</div><?php endif; ?><?php if (isset($_GET['banned'])): ?><div class="alert alert-error">School has been blocked.</div><?php endif; ?><?php if (isset($_GET['unbanned'])): ?><div class="alert alert-success">School has been unblocked.</div><?php endif; ?>

<?php if ($editId !== null && ($editSchool || $editId === 0)): ?>

    <?php if (!empty($editSchool['status']) && $editSchool['status'] === 'blocked'): ?>
        <div class="card" style="border-left:4px solid #E5484D;">
            <h2 style="color:#A5222A;"> This school is currently BLOCKED</h2>
            <p><strong>Reason:</strong><?php echo nl2br(htmlspecialchars($editSchool['ban_reason'])); ?></p>
            <p class="help-text">Blocked at <?php echo htmlspecialchars($editSchool['banned_at']); ?>. Staff at this school cannot log in while blocked.</p>
            <a class="btn" href="schools.php?unban=<?php echo $editSchool['id']; ?>">Unblock This School</a>
        </div>
    <?php endif; ?>

    <div class="card reveal">
        <div class="btn-row" style="justify-content:space-between; margin-bottom:10px;">
            <h2 style="margin:0;"><?php echo !empty($editSchool['id']) ? 'Edit School' : 'Add New School'; ?></h2>
            <?php if (!empty($editSchool['id']) && $editSchool['status'] === 'active'): ?>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('banModal').classList.add('open')"> Ban This School</button>
            <?php endif; ?>
        </div>
        <form method="post" action="schools.php" enctype="multipart/form-data">
            <input type="hidden" name="save_school" value="1">
            <input type="hidden" name="id" value="<?php echo $editSchool['id'] ?? ''; ?>">
            <div class="grid grid-2">
                <div class="field"><label for="name">School Name</label><input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editSchool['name'] ?? ''); ?>"></div>
                <div class="field"><label for="slug">Slug (short URL name, no spaces)</label><input type="text" id="slug" name="slug" required value="<?php echo htmlspecialchars($editSchool['slug'] ?? ''); ?>"></div>
            </div>
            <div class="field"><label for="description">Short Description (shown on the school's card &amp; website)</label><textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editSchool['description'] ?? ''); ?></textarea></div>
            <div class="grid grid-2">
                <div class="field">
                    <label>Logo</label>
                    <?php if (!empty($editSchool['logo_url'])): ?>
                        <div class="school-img-preview"><img src="<?php echo htmlspecialchars(assetUrl($editSchool['logo_url'])); ?>" alt="Current logo"></div>
                    <?php else: ?>
                        <p class="help-text" style="margin-top:0;">No logo yet.</p>
                    <?php endif; ?>
                    <label style="font-weight:600;">Logo URL</label>
                    <input type="text" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($editSchool['logo_url'] ?? ''); ?>" placeholder="https://... or /shared-uploads/schools/1/logo.png">
                    <label style="font-weight:600;">...or upload a logo file (PNG/JPG/SVG/WEBP, max 4MB)</label>
                    <input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.svg,.webp">
                </div>
                <div class="field">
                    <label>Cover / Hero Image (wide photo shown on the card &amp; website)</label>
                    <?php if (!empty($editSchool['cover_image'])): ?>
                        <div class="school-img-preview school-img-preview-cover"><img src="<?php echo htmlspecialchars(assetUrl($editSchool['cover_image'])); ?>" alt="Current cover"></div>
                    <?php else: ?>
                        <p class="help-text" style="margin-top:0;">No cover image yet &mdash; a blue gradient is used instead.</p>
                    <?php endif; ?>
                    <input type="file" name="cover_image" accept=".png,.jpg,.jpeg,.webp">
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Save School</button>
                <a class="btn btn-outline" href="schools.php">Back to List</a>
                <?php if (!empty($editSchool['website_mode']) && $editSchool['website_mode'] !== 'none' && !empty($editSchool['slug'])): ?>
                    <a class="btn btn-outline" href="/school-site.php?slug=<?php echo urlencode($editSchool['slug']); ?>" target="_blank">View Live Website &rarr;</a>
                <?php endif; ?>
                <?php if ($pendingTransferCount > 0): ?>
                    <a class="btn btn-warn" href="transfers.php"><?php echo $pendingTransferCount; ?> pending transfer(s) involving this school</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card reveal">
        <h2>School Website</h2>
        <p class="help-text" style="margin-top:-6px;">This school's website opens when visitors click its card on the public schools page. Pick a built-in template or paste your own HTML document.</p>
        <form method="post" action="schools.php" enctype="multipart/form-data">
            <input type="hidden" name="save_school" value="1">
            <input type="hidden" name="id" value="<?php echo $editSchool['id'] ?? ''; ?>">
            <div class="grid grid-2">
                <div class="field">
                    <label for="website_mode">Website Mode</label>
                    <select id="website_mode" name="website_mode" onchange="toggleWebsiteMode(this.value)">
                        <option value="none"<?php echo ($editSchool['website_mode'] ?? 'none') === 'none' ? ' selected' : ''; ?>>No website (card shows info only)</option>
                        <option value="template"<?php echo ($editSchool['website_mode'] ?? '') === 'template' ? ' selected' : ''; ?>>Use a built-in template</option>
                        <option value="html"<?php echo ($editSchool['website_mode'] ?? '') === 'html' ? ' selected' : ''; ?>>Paste my own HTML document</option>
                    </select>
                </div>
                <div class="field" id="field_template">
                    <label for="website_template">Template</label>
                    <select id="website_template" name="website_template">
                        <?php foreach ($templates as $key => $t): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($editSchool['website_template'] ?? '') === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?> &mdash; <?php echo htmlspecialchars($t['desc']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field" id="field_html" style="<?php echo ($editSchool['website_mode'] ?? 'none') === 'html' ? '' : 'display:none;'; ?>">
                <label for="website_html">Paste your full HTML document</label>
                <textarea id="website_html" name="website_html" rows="14" style="font-family:var(--font-mono); font-size:13px;"><?php echo htmlspecialchars($editSchool['website_html'] ?? ''); ?></textarea>
                <p class="help-text">Paste a complete page (including &lt;html&gt; / &lt;head&gt; / &lt;body&gt;) or just a snippet &mdash; it is shown exactly as written when the school's card is clicked. Save, then open "View Live Website" to check it.</p>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Save Website</button>
                <?php if (!empty($editSchool['slug'])): ?>
                    <a class="btn btn-outline" href="/school-site.php?slug=<?php echo urlencode($editSchool['slug']); ?>" target="_blank">Open /school-site.php?slug=<?php echo htmlspecialchars($editSchool['slug']); ?></a>
                <?php endif; ?>
            </div>
        </form>
        <script>
        function toggleWebsiteMode(mode) {
            var t = document.getElementById('field_template');
            var h = document.getElementById('field_html');
            if (t) t.style.display = (mode === 'template') ? '' : 'none';
            if (h) h.style.display = (mode === 'html') ? '' : 'none';
        }
        </script>
    </div>

    <?php if (!empty($editSchool['id'])): ?>

    <div class="card reveal">
        <h2>Grades &amp; Sections</h2>
        <table class="data-table" style="margin-bottom:18px;">
            <tr><th>Grade</th><th>Section</th><th class="center">Action</th></tr>
            <?php foreach ($sectionsForEdit as $sec): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sec['grade']); ?></td>
                    <td><?php echo htmlspecialchars($sec['section']); ?></td>
                    <td class="center"><a class="confirm-delete" href="schools.php?edit=<?php echo $editSchool['id']; ?>&delete_section=<?php echo $sec['id']; ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sectionsForEdit): ?><tr><td colspan="3">No sections yet.</td></tr><?php endif; ?>
        </table>
        <form method="post" action="schools.php?edit=<?php echo $editSchool['id']; ?>">
            <input type="hidden" name="add_section" value="1">
            <input type="hidden" name="school_id" value="<?php echo $editSchool['id']; ?>">
            <div class="grid grid-3">
                <div class="field"><label for="grade">Grade</label><input type="text" id="grade" name="grade" placeholder="e.g. 9" required></div>
                <div class="field"><label for="section">Section</label><input type="text" id="section" name="section" placeholder="e.g. A" required></div>
                <div class="field" style="display:flex; align-items:flex-end;"><button type="submit" class="btn btn-full">Add Section</button></div>
            </div>
        </form>
    </div>

    <div class="card reveal">
        <h2>Students in this School</h2>
        <p class="help-text">Students are managed by this school's Admin, not from here — you're seeing this as a read-only summary.</p>
        <table class="data-table">
            <tr><th>Student ID</th><th>Full Name</th><th class="center">Grade</th><th class="center">Section</th><th class="center">Status</th></tr>
            <?php foreach ($studentsForEdit as $st): ?>
                <tr>
                    <td><?php echo htmlspecialchars($st['student_no'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($st['full_name']); ?></td>
                    <td class="center"><?php echo htmlspecialchars($st['grade']); ?></td>
                    <td class="center"><?php echo htmlspecialchars($st['section']); ?></td>
                    <td class="center"><span class="tag tag-<?php echo $st['reg_status']==='active'? 'active':($st['reg_status']==='banned'? 'blocked':'pending'); ?>"><?php echo htmlspecialchars($st['reg_status']); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$studentsForEdit): ?><tr><td colspan="5">No students yet.</td></tr><?php endif; ?>
        </table>
    </div>

    <!-- Ban modal -->
    <div class="modal-overlay" id="banModal">
        <div class="modal-box">
            <h2 style="margin-top:0; color:#A5222A;">Ban"<?php echo htmlspecialchars($editSchool['name']); ?>"</h2>
            <p class="help-text">The school is NOT deleted — it moves to the blocked list. Staff there cannot log in until you unblock it. This action requires your own password to confirm.</p>
            <form method="post" action="schools.php?edit=<?php echo $editSchool['id']; ?>">
                <input type="hidden" name="ban_school" value="1">
                <input type="hidden" name="school_id" value="<?php echo $editSchool['id']; ?>">
                <div class="field"><label for="ban_reason">Reason for ban</label><textarea id="ban_reason" name="ban_reason" required></textarea></div>
                <div class="field"><label for="confirm_password">Type your Super Admin password to confirm</label><input type="password" id="confirm_password" name="confirm_password" required></div>
                <div class="btn-row">
                    <button type="submit" class="btn btn-danger">Confirm Ban</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('banModal').classList.remove('open')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>

<?php else: ?>
    <div class="btn-row" style="margin-bottom:18px;">
        <a class="btn" href="schools.php?action=add">+ Add School</a>
        <a class="btn btn-outline" href="schools.php">All</a>
        <a class="btn btn-outline" href="schools.php?status=blocked">Blocked Only</a>
    </div>
    <div class="card table-scroll reveal">
        <table class="data-table">
            <tr><th>Name</th><th>Slug</th><th class="center">Students</th><th class="center">Status</th><th class="center">Actions</th></tr>
            <?php foreach ($schools as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo htmlspecialchars($s['slug']); ?></td>
                    <td class="center"><?php echo $s['student_count']; ?></td>
                    <td class="center"><span class="tag tag-<?php echo $s['status']==='active'? 'active':'blocked'; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                    <td class="center">
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline" href="schools.php?edit=<?php echo $s['id']; ?>">Manage</a>
                            <a class="btn btn-sm btn-danger confirm-delete" href="schools.php?delete=<?php echo $s['id']; ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$schools): ?><tr><td colspan="5">No schools yet.</td></tr><?php endif; ?>
        </table>
    </div><?php endif; ?>

<?php include"includes/footer.php"; ?>
