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
<link rel="stylesheet" href="../css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>My Posts</h2>
        <a href="add_post.php" class="btn btn-primary">+ New Post</a>
    </div>

    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Category</th><th>Status</th><th>Ratings</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($posts)): ?>
            <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                <td><?= $p['published'] ? '<span style="color:green;">Published</span>' : '<span style="color:#999;">Draft</span>' ?></td>
                <td><?= $p['ratings'] ?></td>
                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td>
                    <a href="edit_post.php?id=<?= $p['post_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="delete_post.php?id=<?= $p['post_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
