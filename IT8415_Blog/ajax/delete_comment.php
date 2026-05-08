<?php
// ============================================================
// ajax/delete_comment.php — Admin deletes comment via AJAX
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false]);
    exit;
}

require_once '../DBConn.php';

$comment_id = (int)($_POST['comment_id'] ?? 0);
if (!$comment_id) { echo json_encode(['success' => false]); exit; }

$stmt = mysqli_prepare($conn, "DELETE FROM dbProj_comments WHERE comment_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $comment_id);
mysqli_stmt_execute($stmt);

// Only treat as success if a row was actually deleted
$affected = mysqli_stmt_affected_rows($stmt);
echo json_encode(['success' => $affected > 0]);
?>
