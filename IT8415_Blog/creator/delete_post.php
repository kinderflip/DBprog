<?php
session_start();
require_once '../DBConn.php';
requireRole('creator', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$post_id = (int)($_POST['id'] ?? 0);
$uid     = $_SESSION['uid'];

if ($_SESSION['role'] === 'admin') {
    $stmt = mysqli_prepare($conn, "DELETE FROM dbProj_posts WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $post_id);
} else {
    $stmt = mysqli_prepare($conn, "DELETE FROM dbProj_posts WHERE post_id = ? AND uid = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $post_id, $uid);
}
mysqli_stmt_execute($stmt);
header('Location: dashboard.php');
exit;
?>
