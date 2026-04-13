<?php
// ============================================================
// ajax/rate.php — Handle star rating via AJAX (prepared stmt)
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'msg' => 'Not logged in']);
    exit;
}

require_once '../DBConn.php';

$post_id = (int)($_POST['post_id'] ?? 0);
$rating  = (int)($_POST['rating']  ?? 0);

if (!$post_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'msg' => 'Invalid data']);
    exit;
}

$uid = $_SESSION['uid'];

// Insert or update rating (one per user per post)
$stmt = mysqli_prepare($conn, "
    INSERT INTO dbProj_ratings (post_id, uid, rating)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating)
");
mysqli_stmt_bind_param($stmt, 'iii', $post_id, $uid, $rating);
mysqli_stmt_execute($stmt);

// Return updated avg
$aStmt = mysqli_prepare($conn, "SELECT ROUND(AVG(rating),1) AS avg, COUNT(*) AS cnt FROM dbProj_ratings WHERE post_id = ?");
mysqli_stmt_bind_param($aStmt, 'i', $post_id);
mysqli_stmt_execute($aStmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($aStmt));

echo json_encode(['success' => true, 'avg' => $row['avg'], 'count' => $row['cnt']]);
?>
