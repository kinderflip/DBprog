<?php
// ============================================================
// notifications.php — User's notification inbox
// Auto-created by triggers on dbProj_comments and dbProj_ratings
// ============================================================
session_start();
require_once 'DBConn.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['uid'];

// Handle "Mark all read"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    requireCsrf();
    $stmt = mysqli_prepare($conn, "UPDATE dbProj_notifications SET is_read = 1 WHERE recipient_uid = ? AND is_read = 0");
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    header('Location: notifications.php');
    exit;
}

// Handle single "Mark read"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    requireCsrf();
    $nid = (int)$_POST['mark_read'];
    $stmt = mysqli_prepare($conn, "UPDATE dbProj_notifications SET is_read = 1 WHERE notif_id = ? AND recipient_uid = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $nid, $uid);
    mysqli_stmt_execute($stmt);
    header('Location: notifications.php');
    exit;
}

// Fetch all notifications (newest first), cap at 50
$stmt = mysqli_prepare($conn, "
    SELECT notif_id, type, message, link, is_read, created_at
    FROM dbProj_notifications
    WHERE recipient_uid = ?
    ORDER BY created_at DESC
    LIMIT 50
");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$notifs = mysqli_stmt_get_result($stmt);

// Count unread (for header badge)
$cnt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM dbProj_notifications WHERE recipient_uid = ? AND is_read = 0");
mysqli_stmt_bind_param($cnt, 'i', $uid);
mysqli_stmt_execute($cnt);
$unreadCount = mysqli_fetch_assoc(mysqli_stmt_get_result($cnt))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications &mdash; The Blog</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>
<div class="container" style="max-width:760px;">
    <div class="page-header">
        <h2>&#128276; Notifications<?php if ($unreadCount > 0): ?> <span class="badge" style="background:var(--color-danger-soft); color:var(--color-danger); margin-left:0.5rem;"><?= $unreadCount ?> new</span><?php endif; ?></h2>
        <?php if ($unreadCount > 0): ?>
        <form method="POST" style="display:inline;">
            <?= csrf_input() ?>
            <input type="hidden" name="mark_all_read" value="1">
            <button type="submit" class="btn btn-sm">Mark all as read</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($notifs) === 0): ?>
        <div class="empty-state">
            <div class="icon">&#128276;</div>
            <p style="font-size:1rem; margin-bottom:0.4rem;">No notifications yet.</p>
            <p style="font-size:0.85rem;">When someone comments on or rates your posts, you&apos;ll see it here.</p>
        </div>
    <?php else: ?>
        <div class="notif-list">
        <?php while ($n = mysqli_fetch_assoc($notifs)): ?>
            <div class="notif-card <?= $n['is_read'] ? 'is-read' : 'is-unread' ?>">
                <div class="notif-icon">
                    <?php
                        switch ($n['type']) {
                            case 'comment': echo '&#128172;'; break;
                            case 'rating':  echo '&#11088;';   break;
                            default:        echo '&#128239;'; break;
                        }
                    ?>
                </div>
                <div class="notif-body">
                    <p class="notif-message">
                        <?php if ($n['link']): ?>
                            <a href="<?= htmlspecialchars($n['link']) ?>"><?= htmlspecialchars($n['message']) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($n['message']) ?>
                        <?php endif; ?>
                    </p>
                    <span class="notif-date"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>
                </div>
                <?php if (!$n['is_read']): ?>
                <form method="POST" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="mark_read" value="<?= $n['notif_id'] ?>">
                    <button type="submit" class="btn btn-sm" title="Mark as read">&#10003;</button>
                </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
