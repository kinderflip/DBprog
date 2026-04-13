<?php
// ============================================================
// index.php — Home page (newest posts, paginated at 10)
// ============================================================
session_start();
require_once 'DBConn.php';

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Total published posts
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM dbProj_posts WHERE published = 1");
$total = mysqli_fetch_assoc($totalResult)['cnt'];
$totalPages = ceil($total / $perPage);

// Fetch posts with author, category, avg rating
$stmt = mysqli_prepare($conn, "
    SELECT p.post_id, p.title, p.short_desc, p.image_path, p.created_at,
           u.username AS author, c.cat_name AS category,
           ROUND(AVG(r.rating), 1) AS avg_rating,
           COUNT(r.rating_id)      AS total_ratings
    FROM dbProj_posts p
    LEFT JOIN dbProj_users      u ON p.uid    = u.uid
    LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
    LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
    WHERE p.published = 1
    GROUP BY p.post_id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
mysqli_stmt_execute($stmt);
$posts = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Blog — Home</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Latest Posts</h2>
        <a href="search.php" class="btn btn-secondary">Search</a>
    </div>

    <div class="posts-grid">
    <?php while ($post = mysqli_fetch_assoc($posts)): ?>
        <div class="post-card">
            <?php if ($post['image_path']): ?>
            <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image">
            <?php endif; ?>
            <div class="post-card-body">
                <h3><a href="view_post.php?id=<?= $post['post_id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                <p><?= htmlspecialchars(mb_strimwidth($post['short_desc'], 0, 120, '...')) ?></p>
                <div class="post-meta">
                    <span><span class="badge"><?= htmlspecialchars($post['category'] ?? 'Uncategorised') ?></span></span>
                    <span>⭐ <?= $post['avg_rating'] ?? '—' ?></span>
                </div>
                <div class="post-meta" style="margin-top:6px;">
                    <span>By <?= htmlspecialchars($post['author']) ?></span>
                    <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                </div>
                <a href="view_post.php?id=<?= $post['post_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:0.7rem;">View More</a>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
