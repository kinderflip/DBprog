<?php
// ============================================================
// DBConn.php — Single database connection (include everywhere)
// IT8415 | Group 5 | Student: 202200881
// ============================================================

$host   = 'localhost';
$dbuser = 'u202200881';
$dbpass = 'tagzag-5xowmo-zypkIh';
$dbname = 'db202200881';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// Helper — session role check (call at top of protected pages)
function requireRole(...$roles) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], $roles)) {
        header('Location: ' . str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) . 'login.php');
        exit;
    }
}
?>
