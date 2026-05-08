<?php
// ============================================================
// creator/edit_post.php — Edit existing post
// ============================================================
session_start();
require_once '../DBConn.php';
requireRole('creator', 'admin');

$post_id = (int)($_GET['id'] ?? $_POST['post_id'] ?? 0);
$uid     = $_SESSION['uid'];

// Fetch post (creator can only edit own; admin can edit any)
$ownerClause = ($_SESSION['role'] === 'admin') ? '' : 'AND uid = ?';
$sql = "SELECT * FROM dbProj_posts WHERE post_id = ? $ownerClause";
$stmt = mysqli_prepare($conn, $sql);
if ($_SESSION['role'] === 'admin') {
    mysqli_stmt_bind_param($stmt, 'i', $post_id);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $post_id, $uid);
}
mysqli_stmt_execute($stmt);
$post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$post) { echo '<p>Post not found.</p>'; exit; }

$cats  = mysqli_query($conn, "SELECT * FROM dbProj_categories ORDER BY cat_name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']        ?? '');
    $short     = trim($_POST['short_desc']   ?? '');
    $content   = trim($_POST['full_content'] ?? '');
    $cat_id    = (int)($_POST['cat_id']      ?? 0);
    $published = isset($_POST['published'])  ? 1 : 0;

    if (!$title || !$content) {
        $error = 'Title and content are required.';
    } else {
        // Replace image only if upload actually succeeded; otherwise keep existing
        $image_path = $post['image_path'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $filename = 'img_' . time() . '_' . rand(100,999) . '.' . $ext;
                $destPath = __DIR__ . '/../images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                    $image_path = 'images/' . $filename;
                }
            }
        }

        $pdf_path = $post['pdf_path'];
        if (!empty($_FILES['pdf']['name']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $pdfname = 'pdf_' . time() . '_' . rand(100,999) . '.pdf';
                $destPath = __DIR__ . '/../uploads/' . $pdfname;
                if (move_uploaded_file($_FILES['pdf']['tmp_name'], $destPath)) {
                    $pdf_path = 'uploads/' . $pdfname;
                }
            }
        }

        $upd = mysqli_prepare($conn, "
            UPDATE dbProj_posts
            SET title=?, short_desc=?, full_content=?, image_path=?, pdf_path=?, cat_id=?, published=?
            WHERE post_id=?
        ");
        mysqli_stmt_bind_param($upd, 'sssssiii', $title, $short, $content, $image_path, $pdf_path, $cat_id, $published, $post_id);
        if (mysqli_stmt_execute($upd)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Update failed.';
        }
    }
}

$thumbExists = !empty($post['image_path']) && file_exists(__DIR__ . '/../' . $post['image_path']);
$pdfExists   = !empty($post['pdf_path'])   && file_exists(__DIR__ . '/../' . $post['pdf_path']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Post</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container" style="max-width:800px;">
    <div class="page-header">
        <h2>&#9997; Edit Post</h2>
        <a href="dashboard.php" class="btn">&larr; Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form id="editForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?= $post_id ?>">

        <div class="section-card">
            <h3>Content</h3>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>">
                <span class="field-error" id="err-title"></span>
            </div>
            <div class="form-group">
                <label>Short Description</label>
                <input type="text" name="short_desc" value="<?= htmlspecialchars($post['short_desc']) ?>">
            </div>
            <div class="form-group">
                <label>Full Content *</label>
                <textarea name="full_content" id="fullContent" style="min-height:220px;"><?= htmlspecialchars($post['full_content']) ?></textarea>
                <span class="field-error" id="err-content"></span>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="cat_id">
                    <option value="">— Select —</option>
                    <?php while ($c = mysqli_fetch_assoc($cats)): ?>
                        <option value="<?= $c['cat_id'] ?>" <?= ($post['cat_id'] == $c['cat_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['cat_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="section-card">
            <h3>Media &amp; Publishing</h3>
            <div class="form-group">
                <label>Replace Image (leave blank to keep current)</label>
                <input type="file" name="image" accept="image/*">
                <?php if ($thumbExists): ?>
                    <img src="../<?= htmlspecialchars($post['image_path']) ?>" alt="Current thumbnail" class="thumb-preview">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Replace PDF (leave blank to keep current)</label>
                <input type="file" name="pdf" accept=".pdf">
                <?php if ($pdfExists): ?>
                    <p style="margin-top:0.4rem; font-size:0.85rem; color:var(--color-text-muted);">Current: <a href="../<?= htmlspecialchars($post['pdf_path']) ?>" target="_blank"><?= basename($post['pdf_path']) ?></a></p>
                <?php endif; ?>
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0;">
                <input type="checkbox" name="published" id="published" <?= $post['published'] ? 'checked' : '' ?> style="width:auto;">
                <label for="published" style="font-weight:500; margin-bottom:0;">Published</label>
            </div>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
            <a href="dashboard.php" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Post</button>
        </div>
    </form>
</div>

<script>
$('#editForm').on('submit', function(e) {
    let valid = true;
    $('.field-error').text('').hide();
    if (!$('#title').val().trim()) { $('#err-title').text('Title is required.').fadeIn(); valid = false; }
    if (!$('#fullContent').val().trim()) { $('#err-content').text('Content is required.').fadeIn(); valid = false; }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
