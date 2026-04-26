<?php
// ============================================================
// admin/reports.php — Report 1: Popular content (stored proc)
//                     Report 2: Posts by specific user
// ============================================================
session_start();
require_once '../DBConn.php';
requireRole('admin');

$authors = mysqli_query($conn, "SELECT uid, username FROM dbProj_users WHERE role IN ('creator','admin') ORDER BY username");

// Report 1 — Stored procedure
$report1 = [];
$r1From  = $_GET['r1from'] ?? '';
$r1To    = $_GET['r1to']   ?? '';
if ($r1From && $r1To) {
    $stmt1 = mysqli_prepare($conn, "CALL GetPopularContent(?, ?)");
    mysqli_stmt_bind_param($stmt1, 'ss', $r1From, $r1To);
    mysqli_stmt_execute($stmt1);
    $res = mysqli_stmt_get_result($stmt1);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $report1[] = $row;
        mysqli_stmt_close($stmt1);
        mysqli_next_result($conn); // flush stored proc result
    }
}

// Report 2 — Posts by user
$report2   = [];
$r2Author  = (int)($_GET['r2author'] ?? 0);
if ($r2Author) {
    $stmt = mysqli_prepare($conn, "
        SELECT p.post_id, p.title, p.created_at, p.published,
               c.cat_name,
               ROUND(AVG(r.rating),1) AS avg_rating,
               COUNT(DISTINCT cm.comment_id) AS comments
        FROM dbProj_posts p
        LEFT JOIN dbProj_categories c  ON p.cat_id   = c.cat_id
        LEFT JOIN dbProj_ratings    r  ON p.post_id  = r.post_id
        LEFT JOIN dbProj_comments   cm ON p.post_id  = cm.post_id
        WHERE p.uid = ?
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $r2Author);
    mysqli_stmt_execute($stmt);
    $res2 = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res2)) $report2[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>&#128202; Reports</h2>
        <a href="dashboard.php" class="btn">&larr; Back to Dashboard</a>
    </div>

    <!-- REPORT 1 -->
    <div class="section-card">
        <h3>Report 1 &mdash; Most Popular Posts by Date Range</h3>
        <div class="callout">
            Powered by stored procedure <code>GetPopularContent(startDate, endDate)</code>
        </div>
        <form method="GET" style="display:flex; gap:0.7rem; flex-wrap:wrap; align-items:end; margin-bottom:1.2rem;">
            <input type="hidden" name="r2author" value="<?= $r2Author ?>">
            <div class="form-group" style="margin-bottom:0;">
                <label>From</label>
                <input type="date" name="r1from" value="<?= htmlspecialchars($r1From) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>To</label>
                <input type="date" name="r1to" value="<?= htmlspecialchars($r1To) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>

        <?php if ($r1From && $r1To): ?>
            <?php if (empty($report1)): ?>
                <div class="empty-state" style="padding:1.5rem;">No published posts in this date range.</div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Avg Rating</th><th>Total Ratings</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($report1 as $r): ?>
                <tr>
                    <td><a href="../view_post.php?id=<?= $r['post_id'] ?>" style="font-weight:500;"><?= htmlspecialchars($r['title']) ?></a></td>
                    <td><?= htmlspecialchars($r['author']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($r['category'] ?? '—') ?></span></td>
                    <td>&#11088; <strong><?= $r['avg_rating'] ?? '—' ?></strong></td>
                    <td><?= $r['total_ratings'] ?></td>
                    <td style="color:var(--color-text-muted); font-size:0.85rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- REPORT 2 -->
    <div class="section-card">
        <h3>Report 2 &mdash; All Posts by a Specific Author</h3>
        <form method="GET" style="display:flex; gap:0.7rem; flex-wrap:wrap; align-items:end; margin-bottom:1.2rem;">
            <input type="hidden" name="r1from" value="<?= htmlspecialchars($r1From) ?>">
            <input type="hidden" name="r1to"   value="<?= htmlspecialchars($r1To) ?>">
            <div class="form-group" style="margin-bottom:0; min-width:240px;">
                <label>Author</label>
                <select name="r2author">
                    <option value="">— Select Author —</option>
                    <?php while ($a = mysqli_fetch_assoc($authors)): ?>
                        <option value="<?= $a['uid'] ?>" <?= ($r2Author == $a['uid']) ? 'selected' : '' ?>><?= htmlspecialchars($a['username']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>

        <?php if ($r2Author): ?>
            <?php if (empty($report2)): ?>
                <div class="empty-state" style="padding:1.5rem;">This author has no posts.</div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Avg Rating</th><th>Comments</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($report2 as $r): ?>
                <tr>
                    <td><a href="../view_post.php?id=<?= $r['post_id'] ?>" style="font-weight:500;"><?= htmlspecialchars($r['title']) ?></a></td>
                    <td><span class="badge"><?= htmlspecialchars($r['cat_name'] ?? '—') ?></span></td>
                    <td>
                        <?php if ($r['published']): ?>
                            <span class="status-pill status-published">Live</span>
                        <?php else: ?>
                            <span class="status-pill status-draft">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>&#11088; <strong><?= $r['avg_rating'] ?? '—' ?></strong></td>
                    <td><?= $r['comments'] ?></td>
                    <td style="color:var(--color-text-muted); font-size:0.85rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
