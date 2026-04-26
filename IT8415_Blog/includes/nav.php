<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
// Fetch categories for nav dropdown
$_navCats = [];
if (isset($conn)) {
    $_navCatResult = mysqli_query($conn, "SELECT cat_id, cat_name FROM dbProj_categories ORDER BY cat_name");
    if ($_navCatResult) {
        while ($_navCatRow = mysqli_fetch_assoc($_navCatResult)) {
            $_navCats[] = $_navCatRow;
        }
    }
}
// Active page detection
$_navCurrent = basename($_SERVER['PHP_SELF']);
$_navDir     = basename(dirname($_SERVER['PHP_SELF']));
function _navActive($file, $dir = null) {
    global $_navCurrent, $_navDir;
    if ($dir !== null) return ($_navDir === $dir) ? 'active' : '';
    return ($_navCurrent === $file) ? 'active' : '';
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
            <li><a href="/~u202200881/IT8415_Blog/logout.php">&#128682; Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
        <?php else: ?>
            <li><a class="<?= _navActive('login.php') ?>" href="/~u202200881/IT8415_Blog/login.php">&#128274; Login</a></li>
            <li><a class="<?= _navActive('register.php') ?>" href="/~u202200881/IT8415_Blog/register.php">&#128100; Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
