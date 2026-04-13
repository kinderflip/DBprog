<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<nav class="navbar">
    <div class="nav-brand"><a href="/~u202200881/IT8415_Blog/index.php">The Blog</a></div>
    <ul class="nav-links">
        <li><a href="/~u202200881/IT8415_Blog/index.php">Home</a></li>
        <li><a href="/~u202200881/IT8415_Blog/search.php">Search</a></li>
        <?php if (isset($_SESSION['uid'])): ?>
            <?php if ($_SESSION['role'] === 'creator' || $_SESSION['role'] === 'admin'): ?>
                <li><a href="/~u202200881/IT8415_Blog/creator/dashboard.php">My Posts</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="/~u202200881/IT8415_Blog/admin/dashboard.php">Admin</a></li>
            <?php endif; ?>
            <li><a href="/~u202200881/IT8415_Blog/logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
        <?php else: ?>
            <li><a href="/~u202200881/IT8415_Blog/login.php">Login</a></li>
            <li><a href="/~u202200881/IT8415_Blog/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
