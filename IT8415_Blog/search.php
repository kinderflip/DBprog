<?php
// ============================================================
// search.php — FULLTEXT search + filters
// ============================================================
session_start();
require_once 'DBConn.php';

$query     = trim($_GET['q']       ?? '');
$dateFrom  = trim($_GET['from']    ?? '');
$dateTo    = trim($_GET['to']      ?? '');
$authorId  = (int)($_GET['author'] ?? 0);
$sortBy    = $_GET['sort'] ?? 'newest';

$posts = [];
$searched = false;

// Fetch authors list for dropdown
$authors = mysqli_query($conn, "SELECT uid, username FROM dbProj_users WHERE role IN ('creator','admin') ORDER BY username");

if ($query || $dateFrom || $dateTo || $authorId) {
    $searched = true;

    $sql = "
        SELECT p.post_id, p.title, p.short_desc, p.image_path, p.created_at,
               u.username AS author, c.cat_name AS category,
               ROUND(AVG(r.rating),1) AS avg_rating
        FROM dbProj_posts p
        LEFT JOIN dbProj_users      u ON p.uid    = u.uid
        LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
        LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
        WHERE p.published = 1
    ";

    $params = [];
    $types  = '';

    if ($query) {
        $sql .= " AND MATCH(p.title, p.full_content) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $query . '*';
        $types   .= 's';
    }
    if ($dateFrom) {
        $sql .= " AND DATE(p.created_at) >= ?";
        $params[] = $dateFrom;
        $types   .= 's';
    }
    if ($dateTo) {
        $sql .= " AND DATE(p.created_at) <= ?";
        $params[] = $dateTo;
        $types   .= 's';
    }
    if ($authorId) {
        $sql .= " AND p.uid = ?";
        $params[] = $authorId;
        $types   .= 'i';
    }

    $sql .= " GROUP BY p.post_id";

    if ($sortBy === 'popular') {
        $sql .= " ORDER BY avg_rating DESC";
    } else {
        $sql .= " ORDER BY p.created_at DESC";
    }

    $stmt = mysqli_prepare($conn, $sql);
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search — The Blog</title>
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
            <h2>Search Posts</h2>
            <p>Find posts by keyword, date, author, or popularity</p>
        </div>
    </div>

    <div class="section-card" style="margin-bottom:1.5rem;">
        <form method="GET" action="search.php" id="searchForm" class="search-bar">
            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="q" placeholder="Search by title or content..." value="<?= htmlspecialchars($query) ?>">
            </div>
            <div class="form-group">
                <label>From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="form-group">
                <label>To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="form-group">
                <label>Author</label>
                <select name="author">
                    <option value="">All Authors</option>
                    <?php mysqli_data_seek($authors, 0); while ($a = mysqli_fetch_assoc($authors)): ?>
                        <option value="<?= $a['uid'] ?>" <?= ($authorId == $a['uid']) ? 'selected' : '' ?>><?= htmlspecialchars($a['username']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sort</label>
                <select name="sort">
                    <option value="newest"  <?= ($sortBy === 'newest')  ? 'selected' : '' ?>>Newest First</option>
                    <option value="popular" <?= ($sortBy === 'popular') ? 'selected' : '' ?>>Most Popular</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if ($searched): ?>
        <p style="margin-bottom:1.2rem; color:var(--color-text-muted); font-size:0.9rem;"><?= count($posts) ?> result(s) found.</p>
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <div class="icon">&#128269;</div>
                <p style="font-size:1rem;">No posts matched your search.</p>
                <p style="font-size:0.85rem;">Try different keywords or remove some filters.</p>
            </div>
        <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <?php $img = (!empty($post['image_path']) && file_exists(__DIR__ . '/' . $post['image_path'])) ? $post['image_path'] : 'images/no-image.png'; ?>
                <a href="view_post.php?id=<?= $post['post_id'] ?>" class="post-card-img-wrap">
                    <img src="<?= htmlspecialchars($img) ?>" alt="">
                </a>
                <div class="post-card-body">
                    <div style="margin-bottom:0.5rem;">
                        <span class="badge"><?= htmlspecialchars($post['category'] ?? '—') ?></span>
                    </div>
                    <h3><a href="view_post.php?id=<?= $post['post_id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                    <p><?= htmlspecialchars(mb_strimwidth($post['short_desc'], 0, 110, '...')) ?></p>
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
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">&#128269;</div>
            <p style="font-size:1rem;">Use the form above to search posts.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
