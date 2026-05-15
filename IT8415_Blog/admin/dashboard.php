<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

$userCount    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_users"))['c'];
$postCount    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_posts WHERE is_deleted = 0"))['c'];
$commentCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_comments"))['c'];
$catCount     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_categories"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
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
            <h2>&#9881; Admin Dashboard</h2>
            <p>Manage users, posts, and generate reports</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $userCount ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card accent">
            <div class="stat-value"><?= $postCount ?></div>
            <div class="stat-label">Total Posts</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value"><?= $commentCount ?></div>
            <div class="stat-label">Total Comments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $catCount ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>

    <div style="display:flex; gap:0.7rem; flex-wrap:wrap;">
        <a href="manage_users.php"      class="btn btn-primary">&#128100; Manage Users</a>
        <a href="manage_posts.php"      class="btn btn-accent">&#128221; Manage Posts</a>
        <a href="manage_categories.php" class="btn">&#128194; Manage Categories</a>
        <a href="reports.php"           class="btn btn-secondary">&#128202; Reports</a>
    </div>
</div>
</body>
</html>
