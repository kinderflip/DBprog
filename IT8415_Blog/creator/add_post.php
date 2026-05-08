<?php
// ============================================================
// creator/add_post.php — Add new blog post
// ============================================================
session_start();
require_once '../DBConn.php';
requireRole('creator', 'admin');

$error = '';

// Load categories
$cats = mysqli_query($conn, "SELECT * FROM dbProj_categories ORDER BY cat_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']      ?? '');
    $short     = trim($_POST['short_desc'] ?? '');
    $content   = trim($_POST['full_content'] ?? '');
    $cat_id    = (int)($_POST['cat_id']    ?? 0);
    $published = isset($_POST['published']) ? 1 : 0;
    $uid       = $_SESSION['uid'];

    if (!$title || !$content) {
        $error = 'Title and content are required.';
    } else {
        // Handle image upload — use __DIR__ for absolute path, only set DB column if move succeeds
        $image_path = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $filename = 'img_' . time() . '_' . rand(100,999) . '.' . $ext;
                $destPath = __DIR__ . '/../images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                    $image_path = 'images/' . $filename;
                }
                // If move fails (e.g. permissions), $image_path stays null so the post saves without a broken reference
            }
        }

        // Handle PDF upload — same pattern
        $pdf_path = null;
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

        $stmt = mysqli_prepare($conn, "
            INSERT INTO dbProj_posts (title, short_desc, full_content, image_path, pdf_path, cat_id, uid, published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, 'sssssiii', $title, $short, $content, $image_path, $pdf_path, $cat_id, $uid, $published);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Failed to save post.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Post</title>
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
        <h2>&#9997; New Post</h2>
        <a href="dashboard.php" class="btn">&larr; Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form id="postForm" method="POST" enctype="multipart/form-data">
        <div class="section-card">
            <h3>Content</h3>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" placeholder="Give your post a catchy title">
                <span class="field-error" id="err-title"></span>
            </div>
            <div class="form-group">
                <label>Short Description</label>
                <input type="text" name="short_desc" value="<?= htmlspecialchars($_POST['short_desc'] ?? '') ?>" placeholder="A one-line summary shown in the feed">
            </div>
            <div class="form-group">
                <label>Full Content *</label>
                <textarea name="full_content" id="fullContent" style="min-height:220px;" placeholder="Write your post here..."><?= htmlspecialchars($_POST['full_content'] ?? '') ?></textarea>
                <span class="field-error" id="err-content"></span>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="cat_id">
                    <option value="">— Select Category —</option>
                    <?php while ($c = mysqli_fetch_assoc($cats)): ?>
                        <option value="<?= $c['cat_id'] ?>" <?= (($_POST['cat_id'] ?? '') == $c['cat_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['cat_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="section-card">
            <h3>Media &amp; Publishing</h3>
            <div class="form-group">
                <label>Thumbnail Image (jpg/png/gif/webp)</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>PDF Attachment (optional)</label>
                <input type="file" name="pdf" accept=".pdf">
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0;">
                <input type="checkbox" name="published" id="published" <?= isset($_POST['published']) ? 'checked' : '' ?> style="width:auto;">
                <label for="published" style="font-weight:500; margin-bottom:0;">Publish immediately</label>
            </div>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
            <a href="dashboard.php" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Post</button>
        </div>
    </form>
</div>

<script>
$('#postForm').on('submit', function(e) {
    let valid = true;
    $('.field-error').text('').hide();

    if (!$('#title').val().trim()) {
        $('#err-title').text('Title is required.').fadeIn(); valid = false;
    }
    if (!$('#fullContent').val().trim()) {
        $('#err-content').text('Content is required.').fadeIn(); valid = false;
    }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
