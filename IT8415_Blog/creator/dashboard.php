<?php
// ============================================================
// creator/dashboard.php — Creator's post list
// ============================================================
session_start();
require_once '../DBConn.php';
requireRole('creator', 'admin');

$uid = $_SESSION['uid'];

$stmt = mysqli_prepare($conn, "
    SELECT p.post_id, p.title, p.published, p.created_at, c.cat_name,
           COUNT(r.rating_id) AS ratings
    FROM dbProj_posts p
    LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
    LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
    WHERE p.uid = ?
    GROUP BY p.post_id
    ORDER BY p.created_at DESC
");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$posts = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Posts — Creator Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="hero-header">
        <div>
            <h2>&#9997; My Posts</h2>
            <p>Manage and publish your content</p>
        </div>
        <a href="add_post.php" class="btn btn-primary">&#43; New Post</a>
    </div>

    <?php if (mysqli_num_rows($posts) === 0): ?>
        <div class="empty-state">
            <div class="icon">&#128221;</div>
            <p style="font-size:1rem; margin-bottom:0.5rem;">You haven't written any posts yet.</p>
            <p style="font-size:0.9rem; margin-bottom:1rem;">Click the button below to create your first one.</p>
            <a href="add_post.php" class="btn btn-primary">&#43; Create First Post</a>
        </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Category</th><th>Status</th><th>Ratings</th><th>Date</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($posts)): ?>
            <tr>
                <td>
                    <a href="../view_post.php?id=<?= $p['post_id'] ?>" style="font-weight:500;"><?= htmlspecialchars($p['title']) ?></a>
                </td>
                <td><span class="badge"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></span></td>
                <td>
                    <?php if ($p['published']): ?>
                        <span class="status-pill status-published">Published</span>
                    <?php else: ?>
                        <span class="status-pill status-draft">Draft</span>
                    <?php endif; ?>
                </td>
                <td>&#11088; <?= $p['ratings'] ?></td>
                <td style="color:var(--color-text-muted); font-size:0.85rem;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td style="text-align:right;">
                    <a href="edit_post.php?id=<?= $p['post_id'] ?>" class="btn btn-sm">Edit</a>
                    <form method="POST" action="delete_post.php" style="display:inline;" onsubmit="return confirm('Delete this post? This cannot be undone.')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= $p['post_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
