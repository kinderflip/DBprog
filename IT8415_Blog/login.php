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
        <h2>Welcome Back</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                <span class="field-error" id="err-email"></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="Your password">
                <span class="field-error" id="err-password"></span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Log In</button>
            <p class="form-footer">No account yet? <a href="register.php">Create one</a></p>
        </form>
    </div>
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
