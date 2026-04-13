<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

$userCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_users"))['c'];
$postCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_posts"))['c'];
$commentCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM dbProj_comments"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <h2 style="margin-bottom:1.5rem;">Admin Dashboard</h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
        <div style="background:#fff;border-radius:10px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.07);text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#0066cc;"><?= $userCount ?></div>
            <div style="color:#666;">Total Users</div>
        </div>
        <div style="background:#fff;border-radius:10px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.07);text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#43a047;"><?= $postCount ?></div>
            <div style="color:#666;">Total Posts</div>
        </div>
        <div style="background:#fff;border-radius:10px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.07);text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#f5a623;"><?= $commentCount ?></div>
            <div style="color:#666;">Total Comments</div>
        </div>
    </div>

    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <a href="manage_users.php"  class="btn btn-primary">Manage Users</a>
        <a href="manage_posts.php"  class="btn btn-secondary">Manage Posts</a>
        <a href="reports.php"       class="btn btn-secondary">Reports</a>
    </div>
</div>
</body>
</html>
