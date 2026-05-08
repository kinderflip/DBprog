<?php
// ============================================================
// index.php — Home page (newest posts, paginated at 10)
// ============================================================
session_start();
require_once 'DBConn.php';

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$catFilter = (int)($_GET['cat'] ?? 0);

// Total published posts (with optional category filter)
if ($catFilter) {
    $cntStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM dbProj_posts WHERE published = 1 AND cat_id = ?");
    mysqli_stmt_bind_param($cntStmt, 'i', $catFilter);
    mysqli_stmt_execute($cntStmt);
    $total = mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt))['cnt'];
} else {
    $totalResult = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM dbProj_posts WHERE published = 1");
    $total = mysqli_fetch_assoc($totalResult)['cnt'];
}
$totalPages = ceil($total / $perPage);

// Fetch posts with author, category, avg rating
if ($catFilter) {
    $stmt = mysqli_prepare($conn, "
        SELECT p.post_id, p.title, p.short_desc, p.image_path, p.created_at,
               u.username AS author, c.cat_name AS category,
               ROUND(AVG(r.rating), 1) AS avg_rating,
               COUNT(r.rating_id)      AS total_ratings
        FROM dbProj_posts p
        LEFT JOIN dbProj_users      u ON p.uid    = u.uid
        LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
        LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
        WHERE p.published = 1 AND p.cat_id = ?
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    mysqli_stmt_bind_param($stmt, 'iii', $catFilter, $perPage, $offset);
} else {
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
}
mysqli_stmt_execute($stmt);
$posts = mysqli_stmt_get_result($stmt);

// Get category name for heading
$catName = '';
if ($catFilter) {
    $catStmt = mysqli_prepare($conn, "SELECT cat_name FROM dbProj_categories WHERE cat_id = ?");
    mysqli_stmt_bind_param($catStmt, 'i', $catFilter);
    mysqli_stmt_execute($catStmt);
    $catRow = mysqli_fetch_assoc(mysqli_stmt_get_result($catStmt));
    $catName = $catRow['cat_name'] ?? '';
}
?>
<?php
// Fetch all categories for chip row
$allCatsResult = mysqli_query($conn, "SELECT cat_id, cat_name FROM dbProj_categories ORDER BY cat_name");
$allCats = [];
if ($allCatsResult) while ($cr = mysqli_fetch_assoc($allCatsResult)) $allCats[] = $cr;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Blog — Home</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <div class="hero-header">
        <div>
            <h2><?= $catName ? htmlspecialchars($catName) : 'Latest Posts' ?></h2>
            <p><?= $catName ? 'Browsing posts in ' . htmlspecialchars($catName) : 'Discover our newest stories, tutorials, and reviews' ?></p>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <?php if ($catFilter): ?>
                <a href="index.php" class="btn">All Posts</a>
            <?php endif; ?>
            <a href="search.php" class="btn btn-primary">&#128269; Search</a>
        </div>
    </div>

    <div class="cat-chips">
        <a href="index.php" class="cat-chip <?= !$catFilter ? 'active' : '' ?>">All</a>
        <?php foreach ($allCats as $c): ?>
            <a href="index.php?cat=<?= $c['cat_id'] ?>" class="cat-chip <?= ($catFilter == $c['cat_id']) ? 'active' : '' ?>"><?= htmlspecialchars($c['cat_name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="posts-grid">
    <?php while ($post = mysqli_fetch_assoc($posts)): ?>
        <div class="post-card">
            <?php $img = (!empty($post['image_path']) && file_exists(__DIR__ . '/' . $post['image_path'])) ? $post['image_path'] : 'images/no-image.png'; ?>
            <a href="view_post.php?id=<?= $post['post_id'] ?>" class="post-card-img-wrap">
                <img src="<?= htmlspecialchars($img) ?>" alt="Post Image">
            </a>
            <div class="post-card-body">
                <div style="margin-bottom:0.5rem;">
                    <span class="badge"><?= htmlspecialchars($post['category'] ?? 'Uncategorised') ?></span>
                </div>
                <h3><a href="view_post.php?id=<?= $post['post_id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                <p><?= htmlspecialchars(mb_strimwidth($post['short_desc'], 0, 120, '...')) ?></p>
                <div class="post-meta">
                    <span>By <strong style="color:var(--color-text);"><?= htmlspecialchars($post['author']) ?></strong></span>
                    <span>&#11088; <?= $post['avg_rating'] ?? '—' ?></span>
                </div>
                <div class="post-meta" style="margin-top:0.3rem;">
                    <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                </div>
                <a href="view_post.php?id=<?= $post['post_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:0.9rem;">Read More &rarr;</a>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php
        $pageQuery = $catFilter ? '&cat=' . $catFilter : '';
    ?>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $pageQuery ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?><?= $pageQuery ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?><?= $pageQuery ?>">Next »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($page === 1 && !$catFilter): ?>
    <!-- Why Choose Us — feature highlights -->
    <div class="features-section">
        <h3>Why Choose The Blog?</h3>
        <p class="features-subtitle">Built with modern web technology and a focus on the reader experience</p>
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-icon">&#128269;</span>
                <h4>Powerful Search</h4>
                <p>FULLTEXT-indexed search across titles and content. Filter by date, author, or popularity.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">&#11088;</span>
                <h4>Star Ratings</h4>
                <p>Rate posts 1&ndash;5 stars with instant AJAX updates &mdash; no page reload required.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">&#128172;</span>
                <h4>Live Comments</h4>
                <p>Leave comments on any post and join the discussion. Admins keep things tidy.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">&#128241;</span>
                <h4>Mobile Friendly</h4>
                <p>Fully responsive layout that adapts to your phone, tablet, or desktop screen.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
