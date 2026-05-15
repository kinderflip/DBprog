<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'], $_POST['role'])) {
    requireCsrf();
    $targetUid = (int)$_POST['uid'];
    $newRole   = $_POST['role'];
    if (in_array($newRole, ['admin','creator','viewer'])) {
        $upd = mysqli_prepare($conn, "UPDATE dbProj_users SET role = ? WHERE uid = ?");
        mysqli_stmt_bind_param($upd, 'si', $newRole, $targetUid);
        mysqli_stmt_execute($upd);
    }
    header('Location: manage_users.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    requireCsrf();
    $del = (int)$_POST['delete'];
    if ($del !== $_SESSION['uid']) { // prevent self-delete
        $stmt = mysqli_prepare($conn, "DELETE FROM dbProj_users WHERE uid = ?");
        mysqli_stmt_bind_param($stmt, 'i', $del);
        mysqli_stmt_execute($stmt);
    }
    header('Location: manage_users.php');
    exit;
}

$users = mysqli_query($conn, "SELECT * FROM dbProj_users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>&#128100; Manage Users</h2>
        <a href="dashboard.php" class="btn">&larr; Back to Dashboard</a>
    </div>

    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
            <td style="color:var(--color-text-muted); font-weight:500;"><?= $u['uid'] ?></td>
            <td>
                <div style="display:flex; align-items:center; gap:0.55rem;">
                    <div class="avatar-circle" style="width:32px; height:32px; font-size:0.82rem;"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                </div>
            </td>
            <td style="color:var(--color-text-muted);"><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                    <select name="role" onchange="this.form.submit()" class="status-pill role-<?= $u['role'] ?>" style="border:none; cursor:pointer; padding:0.25rem 0.6rem; appearance:none; -webkit-appearance:none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M3 5l3 3 3-3' stroke='%236b7280' stroke-width='1.5' fill='none'/%3E%3C/svg%3E&quot;); background-repeat:no-repeat; background-position:right 0.4rem center; padding-right:1.4rem;">
                        <option value="viewer"  <?= $u['role']==='viewer'  ? 'selected':'' ?>>Viewer</option>
                        <option value="creator" <?= $u['role']==='creator' ? 'selected':'' ?>>Creator</option>
                        <option value="admin"   <?= $u['role']==='admin'   ? 'selected':'' ?>>Admin</option>
                    </select>
                </form>
            </td>
            <td style="color:var(--color-text-muted); font-size:0.85rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td style="text-align:right;">
                <?php if ($u['uid'] !== $_SESSION['uid']): ?>
                    <form method="POST" action="manage_users.php" style="display:inline;" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="delete" value="<?= $u['uid'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                <?php else: ?>
                    <span class="status-pill role-creator">You</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
