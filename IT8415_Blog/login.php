<?php
// ============================================================
// login.php — Login page
// ============================================================
session_start();
if (isset($_SESSION['uid'])) { header('Location: index.php'); exit; }

require_once 'DBConn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT uid, username, password, role FROM dbProj_users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['uid']      = $user['uid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — The Blog</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container form-page">
    <h2>Log In</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="login.php" novalidate>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <span class="field-error" id="err-email"></span>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password">
            <span class="field-error" id="err-password"></span>
        </div>
        <button type="submit" class="btn btn-primary">Log In</button>
        <p class="form-footer">No account yet? <a href="register.php">Register</a></p>
    </form>
</div>

<script>
$('#loginForm').on('submit', function(e) {
    let valid = true;
    $('.field-error').text('').hide();

    if (!$('#email').val().trim()) {
        $('#err-email').text('Email is required.').fadeIn(); valid = false;
    }
    if (!$('#password').val()) {
        $('#err-password').text('Password is required.').fadeIn(); valid = false;
    }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
