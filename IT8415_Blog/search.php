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
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2 style="margin-bottom:1.2rem;">Search Posts</h2>

    <form method="GET" action="search.php" class="search-bar" id="searchForm">
        <input type="text" name="q" placeholder="Search by title or content..." value="<?= htmlspecialchars($query) ?>">
        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" title="From date">
        <input type="date" name="to"   value="<?= htmlspecialchars($dateTo) ?>"   title="To date">
        <select name="author">
            <option value="">All Authors</option>
            <?php mysqli_data_seek($authors, 0); while ($a = mysqli_fetch_assoc($authors)): ?>
                <option value="<?= $a['uid'] ?>" <?= ($authorId == $a['uid']) ? 'selected' : '' ?>><?= htmlspecialchars($a['username']) ?></option>
            <?php endwhile; ?>
        </select>
        <select name="sort">
            <option value="newest"  <?= ($sortBy === 'newest')  ? 'selected' : '' ?>>Newest First</option>
            <option value="popular" <?= ($sortBy === 'popular') ? 'selected' : '' ?>>Most Popular</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($searched): ?>
        <p style="margin-bottom:1rem;color:#666;"><?= count($posts) ?> result(s) found.</p>
        <?php if (empty($posts)): ?>
            <div class="alert alert-error">No posts matched your search.</div>
        <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <img src="<?= htmlspecialchars($post['image_path'] ?: 'images/default.jpg') ?>" alt="">
                <div class="post-card-body">
                    <h3><a href="view_post.php?id=<?= $post['post_id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                    <p><?= htmlspecialchars(mb_strimwidth($post['short_desc'], 0, 110, '...')) ?></p>
                    <div class="post-meta">
                        <span class="badge"><?= htmlspecialchars($post['category'] ?? '—') ?></span>
                        <span>⭐ <?= $post['avg_rating'] ?? '—' ?></span>
                    </div>
                    <div class="post-meta" style="margin-top:5px;">
                        <span>By <?= htmlspecialchars($post['author']) ?></span>
                        <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                    </div>
                    <a href="view_post.php?id=<?= $post['post_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:0.7rem;">View More</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
