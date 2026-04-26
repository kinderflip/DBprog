<?php
// ============================================================
// view_post.php — Single post, comments, star rating (jQuery AJAX)
// ============================================================
session_start();
require_once 'DBConn.php';

$post_id = (int)($_GET['id'] ?? 0);
if (!$post_id) { header('Location: index.php'); exit; }

// Fetch post
$stmt = mysqli_prepare($conn, "
    SELECT p.*, u.username AS author, c.cat_name AS category,
           ROUND(AVG(r.rating), 1) AS avg_rating,
           COUNT(r.rating_id)      AS total_ratings
    FROM dbProj_posts p
    LEFT JOIN dbProj_users      u ON p.uid    = u.uid
    LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
    LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
    WHERE p.post_id = ? AND p.published = 1
    GROUP BY p.post_id
");
mysqli_stmt_bind_param($stmt, 'i', $post_id);
mysqli_stmt_execute($stmt);
$post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$post) { echo '<p>Post not found.</p>'; exit; }

// Current user's rating (if logged in)
$userRating = 0;
if (isset($_SESSION['uid'])) {
    $rStmt = mysqli_prepare($conn, "SELECT rating FROM dbProj_ratings WHERE post_id = ? AND uid = ?");
    mysqli_stmt_bind_param($rStmt, 'ii', $post_id, $_SESSION['uid']);
    mysqli_stmt_execute($rStmt);
    $rRow = mysqli_fetch_assoc(mysqli_stmt_get_result($rStmt));
    $userRating = $rRow['rating'] ?? 0;
}

// Handle comment submission
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['uid'])) {
    $text = trim($_POST['comment_text'] ?? '');
    if (!$text) {
        $commentError = 'Comment cannot be empty.';
    } elseif (strlen($text) > 1000) {
        $commentError = 'Comment must be under 1000 characters.';
    } else {
        $cStmt = mysqli_prepare($conn, "INSERT INTO dbProj_comments (post_id, uid, comment_text) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($cStmt, 'iis', $post_id, $_SESSION['uid'], $text);
        mysqli_stmt_execute($cStmt);
        header("Location: view_post.php?id=$post_id");
        exit;
    }
}

// Fetch comments
$cFetch = mysqli_prepare($conn, "
    SELECT c.*, u.username
    FROM dbProj_comments c
    JOIN dbProj_users u ON c.uid = u.uid
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
mysqli_stmt_bind_param($cFetch, 'i', $post_id);
mysqli_stmt_execute($cFetch);
$comments = mysqli_stmt_get_result($cFetch);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($post['title']) ?> — The Blog</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <div class="post-detail">
        <?php if (!empty($post['image_path']) && file_exists($post['image_path'])): ?>
            <img class="post-hero-img" src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image">
        <?php endif; ?>

        <div class="post-detail-body">
            <div class="post-detail-main">
                <span class="badge"><?= htmlspecialchars($post['category'] ?? 'Uncategorised') ?></span>
                <h1><?= htmlspecialchars($post['title']) ?></h1>
                <p style="color:var(--color-text-muted); font-size:0.9rem; margin-top:0.4rem;">
                    By <strong style="color:var(--color-text);"><?= htmlspecialchars($post['author']) ?></strong> &middot;
                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                </p>

                <div class="post-content">
                    <?= nl2br(htmlspecialchars($post['full_content'])) ?>
                </div>

                <?php if ($post['pdf_path']): ?>
                    <p style="margin-top:1.8rem;">
                        <a href="<?= htmlspecialchars($post['pdf_path']) ?>" class="btn btn-accent" download>&#128229; Download PDF</a>
                    </p>
                <?php endif; ?>
            </div>

            <aside class="post-detail-side">
                <h4 style="margin-bottom:0.7rem; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Post Info</h4>

                <div class="meta-row">
                    <span class="label">Author</span>
                    <span class="value"><?= htmlspecialchars($post['author']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="label">Category</span>
                    <span class="value"><?= htmlspecialchars($post['category'] ?? '—') ?></span>
                </div>
                <div class="meta-row">
                    <span class="label">Published</span>
                    <span class="value"><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                </div>

                <h4 style="margin-top:1.2rem; margin-bottom:0.4rem; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Rate this post</h4>
                <div class="star-rating" id="starWidget" data-post="<?= $post_id ?>" data-user="<?= $_SESSION['uid'] ?? 0 ?>">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <span class="star <?= ($s <= $userRating) ? 'active' : '' ?>" data-val="<?= $s ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <div class="avg-rating" id="avgRating" style="margin-left:0;">
                    <?= $post['avg_rating'] ? '&#11088; ' . $post['avg_rating'] . ' (' . $post['total_ratings'] . ' ratings)' : 'No ratings yet' ?>
                </div>
                <?php if (!isset($_SESSION['uid'])): ?>
                    <p style="font-size:0.8rem; color:var(--color-text-muted); margin-top:0.5rem;"><a href="login.php">Log in</a> to rate.</p>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <!-- Comments -->
    <div class="comments-section">
        <h3>&#128172; Comments (<?= mysqli_num_rows($comments) ?>)</h3>

        <?php if (mysqli_num_rows($comments) === 0): ?>
            <div class="empty-state">
                <div class="icon">&#128172;</div>
                <p>No comments yet. Be the first to share your thoughts!</p>
            </div>
        <?php else: ?>
            <?php while ($c = mysqli_fetch_assoc($comments)): ?>
            <div class="comment-card" id="comment-<?= $c['comment_id'] ?>">
                <div class="avatar-circle"><?= strtoupper(substr($c['username'], 0, 1)) ?></div>
                <div class="comment-body">
                    <span class="comment-author"><?= htmlspecialchars($c['username']) ?></span>
                    <span class="comment-date"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <button class="btn btn-danger btn-sm delete-comment" data-id="<?= $c['comment_id'] ?>" style="float:right;">Delete</button>
                    <?php endif; ?>
                    <p class="comment-text"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Add Comment Form -->
        <?php if (isset($_SESSION['uid'])): ?>
        <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--color-border);">
            <h4 style="margin-bottom:0.7rem; font-size:1rem;">Add a Comment</h4>
            <?php if ($commentError): ?>
                <div class="alert alert-error"><?= htmlspecialchars($commentError) ?></div>
            <?php endif; ?>
            <form id="commentForm" method="POST" action="view_post.php?id=<?= $post_id ?>">
                <div class="form-group">
                    <textarea name="comment_text" id="commentText" maxlength="1000" placeholder="Write your comment..."></textarea>
                    <div class="char-counter"><span id="charCount">0</span>/1000</div>
                </div>
                <button type="submit" class="btn btn-primary">Post Comment</button>
            </form>
        </div>
        <?php else: ?>
            <p style="margin-top:1.2rem; color:var(--color-text-muted);"><a href="login.php">Log in</a> to leave a comment.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// ---- Star rating via jQuery AJAX ----
<?php if (isset($_SESSION['uid'])): ?>
$('.star').on('mouseover', function() {
    const val = $(this).data('val');
    $('.star').each(function() {
        $(this).toggleClass('hover', $(this).data('val') <= val);
    });
}).on('mouseout', function() {
    $('.star').removeClass('hover');
}).on('click', function() {
    const rating  = $(this).data('val');
    const post_id = $('#starWidget').data('post');

    $.ajax({
        url: 'ajax/rate.php',
        method: 'POST',
        data: { post_id: post_id, rating: rating },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('.star').each(function() {
                    $(this).toggleClass('active', $(this).data('val') <= rating);
                });
                $('#avgRating').html('⭐ ' + res.avg + ' (' + res.count + ' ratings)');
            }
        }
    });
});
<?php else: ?>
$('.star').css('cursor', 'default');
<?php endif; ?>

// ---- Character counter ----
$('#commentText').on('input', function() {
    $('#charCount').text($(this).val().length);
});

// ---- Admin: delete comment via AJAX ----
$('.delete-comment').on('click', function() {
    if (!confirm('Delete this comment?')) return;
    const id = $(this).data('id');
    const btn = $(this);
    $.ajax({
        url: 'ajax/delete_comment.php',
        method: 'POST',
        data: { comment_id: id },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                btn.closest('.comment-card').fadeOut(300, function() { $(this).remove(); });
            }
        }
    });
});
</script>
</body>
</html>
