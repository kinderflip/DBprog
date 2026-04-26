<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

// Handle delete — show system message instead of removing row visually
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $pid = (int)$_POST['delete'];
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>&#128221; Manage All Posts</h2>
        <a href="dashboard.php" class="btn">&larr; Back to Dashboard</a>
    </div>

    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($posts)): ?>
        <tr>
            <td>
                <a href="../view_post.php?id=<?= $p['post_id'] ?>" style="font-weight:500;"><?= htmlspecialchars($p['title']) ?></a>
            </td>
            <td>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <div class="avatar-circle" style="width:28px; height:28px; font-size:0.75rem;"><?= strtoupper(substr($p['author'] ?? '?', 0, 1)) ?></div>
                    <span style="font-size:0.88rem;"><?= htmlspecialchars($p['author']) ?></span>
                </div>
            </td>
            <td><span class="badge"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></span></td>
            <td>
                <?php if (str_starts_with($p['title'], '[REMOVED]')): ?>
                    <span class="status-pill status-removed">Removed</span>
                <?php elseif ($p['published']): ?>
                    <span class="status-pill status-live">Live</span>
                <?php else: ?>
                    <span class="status-pill status-hidden">Hidden</span>
                <?php endif; ?>
            </td>
            <td style="color:var(--color-text-muted); font-size:0.85rem;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td style="text-align:right;">
                <a href="../creator/edit_post.php?id=<?= $p['post_id'] ?>" class="btn btn-sm">Edit</a>
                <?php if (!str_starts_with($p['title'], '[REMOVED]')): ?>
                    <form method="POST" action="manage_posts.php" style="display:inline;" onsubmit="return confirm('Remove this post? A system message will be shown in its place.')">
                        <input type="hidden" name="delete" value="<?= $p['post_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
