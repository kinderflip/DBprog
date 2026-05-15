<?php
// ============================================================
// admin/manage_categories.php — Admin CRUD for blog categories
// ============================================================
session_start();
require_once '../DBConn.php';
requireRole('admin');

$error   = '';
$success = '';

// Invalidate the cached nav categories so the navbar reflects changes immediately
function _invalidateNavCatsCache() {
    unset($_SESSION['nav_cats_cache']);
    unset($_SESSION['nav_cats_expiry']);
}

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_name'])) {
    requireCsrf();
    $name = trim($_POST['add_name']);
    if ($name === '') {
        $error = 'Category name cannot be empty.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Category name is too long (max 100 characters).';
    } else {
        // Prevent duplicates (case-insensitive)
        $chk = mysqli_prepare($conn, "SELECT cat_id FROM dbProj_categories WHERE LOWER(cat_name) = LOWER(?)");
        mysqli_stmt_bind_param($chk, 's', $name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = 'A category with that name already exists.';
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO dbProj_categories (cat_name) VALUES (?)");
            mysqli_stmt_bind_param($ins, 's', $name);
            if (mysqli_stmt_execute($ins)) {
                _invalidateNavCatsCache();
                $success = 'Category "' . htmlspecialchars($name) . '" added.';
            } else {
                $error = 'Could not add category.';
            }
        }
    }
}

// Handle RENAME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_id'], $_POST['rename_name'])) {
    requireCsrf();
    $renameId   = (int)$_POST['rename_id'];
    $renameName = trim($_POST['rename_name']);
    if ($renameName === '') {
        $error = 'Category name cannot be empty.';
    } elseif (mb_strlen($renameName) > 100) {
        $error = 'Category name is too long.';
    } else {
        $upd = mysqli_prepare($conn, "UPDATE dbProj_categories SET cat_name = ? WHERE cat_id = ?");
        mysqli_stmt_bind_param($upd, 'si', $renameName, $renameId);
        if (mysqli_stmt_execute($upd)) {
            _invalidateNavCatsCache();
            $success = 'Category renamed.';
        } else {
            $error = 'Rename failed.';
        }
    }
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    requireCsrf();
    $del = (int)$_POST['delete'];
    // FK on dbProj_posts.cat_id is ON DELETE SET NULL — posts in this category become uncategorised, not deleted.
    $stmt = mysqli_prepare($conn, "DELETE FROM dbProj_categories WHERE cat_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $del);
    if (mysqli_stmt_execute($stmt)) {
        _invalidateNavCatsCache();
        $success = 'Category deleted. Any posts in that category are now uncategorised.';
    } else {
        $error = 'Delete failed.';
    }
}

// Fetch all categories with post counts
$cats = mysqli_query($conn, "
    SELECT c.cat_id, c.cat_name, COUNT(p.post_id) AS post_count
    FROM dbProj_categories c
    LEFT JOIN dbProj_posts p ON p.cat_id = c.cat_id AND p.is_deleted = 0
    GROUP BY c.cat_id
    ORDER BY c.cat_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Categories</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>&#128194; Manage Categories</h2>
        <a href="dashboard.php" class="btn">&larr; Back to Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add new category -->
    <div class="section-card">
        <h3>Add a New Category</h3>
        <form method="POST" style="display:flex; gap:0.6rem; align-items:end; flex-wrap:wrap;">
            <?= csrf_input() ?>
            <div class="form-group" style="margin-bottom:0; flex:1; min-width:240px;">
                <label>Category Name</label>
                <input type="text" name="add_name" placeholder="e.g. Photography" required maxlength="100">
            </div>
            <button type="submit" class="btn btn-primary">&#43; Add Category</button>
        </form>
    </div>

    <!-- Existing categories -->
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Posts</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($cats)): ?>
        <tr>
            <td style="color:var(--color-text-muted); font-weight:500;"><?= $c['cat_id'] ?></td>
            <td>
                <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="rename_id" value="<?= $c['cat_id'] ?>">
                    <input type="text" name="rename_name" value="<?= htmlspecialchars($c['cat_name']) ?>" maxlength="100" style="flex:1; padding:0.35rem 0.55rem; border:1px solid var(--color-border-strong); border-radius:var(--radius-sm); font-size:0.88rem;">
                    <button type="submit" class="btn btn-sm">Save</button>
                </form>
            </td>
            <td>
                <?php if ($c['post_count'] > 0): ?>
                    <span class="badge"><?= $c['post_count'] ?> post<?= $c['post_count'] == 1 ? '' : 's' ?></span>
                <?php else: ?>
                    <span style="color:var(--color-text-subtle); font-size:0.82rem;">empty</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right;">
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category? Any posts in it will become uncategorised.')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="delete" value="<?= $c['cat_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <p style="margin-top:1rem; font-size:0.85rem; color:var(--color-text-muted);">
        &#9432; Deleting a category sets its posts' category to &quot;Uncategorised&quot; (does not delete the posts).
    </p>
</div>
</body>
</html>
