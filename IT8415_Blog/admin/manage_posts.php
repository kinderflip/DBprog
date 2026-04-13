<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

// Handle delete — show system message instead of removing row visually
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    // Mark as unpublished and set a deleted flag via title prefix (shows system message)
    $stmt = mysqli_prepare($conn, "UPDATE dbProj_posts SET published=0, title=CONCAT('[REMOVED] ', title) WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $pid);
    mysqli_stmt_execute($stmt);
    header('Location: manage_posts.php');
    exit;
}

$posts = mysqli_query($conn, "
    SELECT p.post_id, p.title, p.published, p.created_at,
           u.username AS author, c.cat_name
    FROM dbProj_posts p
    LEFT JOIN dbProj_users      u ON p.uid    = u.uid
    LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Posts</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>Manage All Posts</h2>
        <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
    </div>

    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($posts)): ?>
        <tr>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= htmlspecialchars($p['author']) ?></td>
            <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
            <td><?= $p['published'] ? '<span style="color:green;">Live</span>' : '<span style="color:#999;">Hidden</span>' ?></td>
            <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td>
                <a href="../creator/edit_post.php?id=<?= $p['post_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                <?php if (!str_starts_with($p['title'], '[REMOVED]')): ?>
                    <a href="manage_posts.php?delete=<?= $p['post_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this post? A system message will be shown in its place.')">Remove</a>
                <?php else: ?>
                    <span style="color:#e53935;font-size:0.8rem;">Removed</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
