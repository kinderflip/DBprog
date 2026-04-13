<?php
session_start();
require_once '../DBConn.php';
requireRole('admin');

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'], $_POST['role'])) {
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
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
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
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>Manage Users</h2>
        <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
    </div>

    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
            <td><?= $u['uid'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                    <select name="role" onchange="this.form.submit()">
                        <option value="viewer"  <?= $u['role']==='viewer'  ? 'selected':'' ?>>Viewer</option>
                        <option value="creator" <?= $u['role']==='creator' ? 'selected':'' ?>>Creator</option>
                        <option value="admin"   <?= $u['role']==='admin'   ? 'selected':'' ?>>Admin</option>
                    </select>
                </form>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['uid'] !== $_SESSION['uid']): ?>
                    <a href="manage_users.php?delete=<?= $u['uid'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</a>
                <?php else: ?>
                    <span style="color:#999;font-size:0.8rem;">(you)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
