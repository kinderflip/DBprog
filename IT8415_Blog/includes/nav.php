<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
// Fetch categories for nav dropdown — cache in session for 5 minutes
// so we don't query the DB on every page render.
$_navCats = $_SESSION['nav_cats_cache'] ?? null;
$_navCatsExpiry = $_SESSION['nav_cats_expiry'] ?? 0;
if (!is_array($_navCats) || time() > $_navCatsExpiry) {
    $_navCats = [];
    if (isset($conn)) {
        $_navCatResult = mysqli_query($conn, "SELECT cat_id, cat_name FROM dbProj_categories ORDER BY cat_name");
        if ($_navCatResult) {
            while ($_navCatRow = mysqli_fetch_assoc($_navCatResult)) {
                $_navCats[] = $_navCatRow;
            }
        }
    }
    $_SESSION['nav_cats_cache']  = $_navCats;
    $_SESSION['nav_cats_expiry'] = time() + 300; // 5 minutes
}
// Active page detection
$_navCurrent = basename($_SERVER['PHP_SELF']);
$_navDir     = basename(dirname($_SERVER['PHP_SELF']));
function _navActive($file, $dir = null) {
    global $_navCurrent, $_navDir;
    if ($dir !== null) return ($_navDir === $dir) ? 'active' : '';
    return ($_navCurrent === $file) ? 'active' : '';
}

// Unread notification count for the bell icon (only for logged-in users)
$_navUnread = 0;
if (isset($_SESSION['uid']) && isset($conn)) {
    $_unreadStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM dbProj_notifications WHERE recipient_uid = ? AND is_read = 0");
    if ($_unreadStmt) {
        mysqli_stmt_bind_param($_unreadStmt, 'i', $_SESSION['uid']);
        mysqli_stmt_execute($_unreadStmt);
        $_unreadRow = mysqli_fetch_assoc(mysqli_stmt_get_result($_unreadStmt));
        $_navUnread = (int)($_unreadRow['c'] ?? 0);
    }
}
?>
<nav class="navbar">
    <div class="nav-brand"><a href="/~u202200881/IT8415_Blog/index.php">&#128196; The Blog</a></div>
    <ul class="nav-links">
        <li><a class="<?= _navActive('index.php') ?>" href="/~u202200881/IT8415_Blog/index.php">&#127968; Home</a></li>
        <li class="nav-dropdown">
            <a href="#">&#128194; Categories</a>
            <ul class="dropdown-menu">
                <?php foreach ($_navCats as $_cat): ?>
                    <li><a href="/~u202200881/IT8415_Blog/index.php?cat=<?= $_cat['cat_id'] ?>"><?= htmlspecialchars($_cat['cat_name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <li><a class="<?= _navActive('search.php') ?>" href="/~u202200881/IT8415_Blog/search.php">&#128269; Search</a></li>
        <?php if (isset($_SESSION['uid'])): ?>
            <?php if ($_SESSION['role'] === 'creator' || $_SESSION['role'] === 'admin'): ?>
                <li><a class="<?= _navActive(null, 'creator') ?>" href="/~u202200881/IT8415_Blog/creator/dashboard.php">&#9997; My Posts</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a class="<?= _navActive(null, 'admin') ?>" href="/~u202200881/IT8415_Blog/admin/dashboard.php">&#9881; Admin</a></li>
            <?php endif; ?>
            <li class="nav-bell-wrap">
                <a class="nav-bell <?= _navActive('notifications.php') ?>" href="/~u202200881/IT8415_Blog/notifications.php" title="Notifications">
                    &#128276;
                    <?php if ($_navUnread > 0): ?>
                        <span class="nav-bell-badge"><?= $_navUnread > 99 ? '99+' : $_navUnread ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="/~u202200881/IT8415_Blog/logout.php">&#128682; Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
        <?php else: ?>
            <li><a class="<?= _navActive('login.php') ?>" href="/~u202200881/IT8415_Blog/login.php">&#128274; Login</a></li>
            <li><a class="<?= _navActive('register.php') ?>" href="/~u202200881/IT8415_Blog/register.php">&#128100; Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
