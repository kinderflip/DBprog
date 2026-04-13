<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<nav class="navbar">
    <div class="nav-brand"><a href="/~u202200881/IT8415_Blog/index.php">&#128196; The Blog</a></div>
    <ul class="nav-links">
        <li><a href="/~u202200881/IT8415_Blog/index.php">&#127968; Home</a></li>
        <li><a href="/~u202200881/IT8415_Blog/search.php">&#128269; Search</a></li>
        <?php if (isset($_SESSION['uid'])): ?>
            <?php if ($_SESSION['role'] === 'creator' || $_SESSION['role'] === 'admin'): ?>
                <li><a href="/~u202200881/IT8415_Blog/creator/dashboard.php">&#9997; My Posts</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="/~u202200881/IT8415_Blog/admin/dashboard.php">&#9881; Admin</a></li>
            <?php endif; ?>
            <li><a href="/~u202200881/IT8415_Blog/logout.php">&#128682; Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
        <?php else: ?>
            <li><a href="/~u202200881/IT8415_Blog/login.php">&#128274; Login</a></li>
            <li><a href="/~u202200881/IT8415_Blog/register.php">&#128100; Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
