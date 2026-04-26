<?php
// ============================================================
// register.php — Sign-up page
// ============================================================
session_start();
if (isset($_SESSION['uid'])) { header('Location: index.php'); exit; }

require_once 'DBConn.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';
    $confirm  =       $_POST['confirm']  ?? '';

    // Server-side validation
    if (!$username || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists — prepared statement
        $stmt = mysqli_prepare($conn, "SELECT uid FROM dbProj_users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO dbProj_users (username, email, password, role) VALUES (?, ?, ?, 'viewer')");
            mysqli_stmt_bind_param($stmt2, 'sss', $username, $email, $hashed);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Account created! <a href="login.php">Log in now</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — The Blog</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container form-page">
    <div class="form-card">
        <h2>Create an Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="register.php" novalidate>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Choose a username">
                <span class="field-error" id="err-username"></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                <span class="field-error" id="err-email"></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="At least 6 characters">
                <span class="field-error" id="err-password"></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" id="confirm" placeholder="Repeat your password">
                <span class="field-error" id="err-confirm"></span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Create Account</button>
            <p class="form-footer">Already have an account? <a href="login.php">Log in</a></p>
        </form>
    </div>
</div>

<script>
// jQuery client-side validation
$('#registerForm').on('submit', function(e) {
    let valid = true;

    function showError(id, msg) {
        $('#' + id).text(msg).fadeIn();
        valid = false;
    }
    function clearErrors() {
        $('.field-error').text('').hide();
    }

    clearErrors();

    if (!$('#username').val().trim()) showError('err-username', 'Username is required.');
    const email = $('#email').val().trim();
    if (!email) {
        showError('err-email', 'Email is required.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('err-email', 'Enter a valid email address.');
    }
    const pw = $('#password').val();
    if (!pw) {
        showError('err-password', 'Password is required.');
    } else if (pw.length < 6) {
        showError('err-password', 'Password must be at least 6 characters.');
    }
    if ($('#confirm').val() !== pw) {
        showError('err-confirm', 'Passwords do not match.');
    }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
