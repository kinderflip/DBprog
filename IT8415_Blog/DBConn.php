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

// ============================================================
// CSRF protection helpers
// ============================================================
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Guard for POST handlers — call at the top after session_start
function requireCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

// Same as requireCsrf but returns JSON for AJAX endpoints
function requireCsrfAjax() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'Invalid security token']);
        exit;
    }
}

// ============================================================
// File upload validation — defends against extension spoofing
// (e.g. evil.php renamed to evil.jpg).
// $type: 'image' or 'pdf'
// Returns true only if the file's actual MIME matches the expected type.
// ============================================================
function validateUpload($file, $type) {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return false;
    if (!is_uploaded_file($file['tmp_name'])) return false;

    if (!function_exists('finfo_open')) {
        // finfo extension not available — fall back to getimagesize for images
        if ($type === 'image') {
            return @getimagesize($file['tmp_name']) !== false;
        }
        return false;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($type === 'image') {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }
    if ($type === 'pdf') {
        return $mime === 'application/pdf';
    }
    return false;
}
?>
