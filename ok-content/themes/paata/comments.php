<?php
/**
 * კომენტარების შაბლონი
 */
global $ok_db, $ok_post_in_loop;

// ვიღებთ ამ პოსტის დამტკიცებულ კომენტარებს
$comments = $ok_db->get_results("
    SELECT * FROM comments 
    WHERE comment_post_id = ? AND comment_approved = 'approved' 
    ORDER BY comment_date ASC
", [$ok_post_in_loop->id]);
?>

<div id="comments" class="comments-area mt">

    <?php if (!empty($comments)): ?>
        <h4 class="mb-4">
            კომენტარები (<?php echo count($comments); ?>)
        </h4>

        <ul class="list-unstyled">
            <?php foreach ($comments as $comment): ?>
                <li class="comment mb-4 d-flex">
                    <div class="flex-shrink-0 me-3">
                        <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($comment->comment_author_email))); ?>?s=60&d=mp" class="rounded-circle">
                    </div>
                    <div class="flex-grow-1">
                        <strong class="comment-author"><?php echo htmlspecialchars($comment->comment_author); ?></strong>
                        <small class="text-muted d-block"><?php echo date('d M, Y H:i', strtotime($comment->comment_date)); ?></small>
                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($comment->comment_content)); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div id="respond" class="comment-respond">
        <h4 id="reply-title" class="comment-reply-title mb-4">დატოვე კომენტარი</h4>
        <form action="/ok-comment-post.php" method="post" id="commentform" class="comment-form">
            <p class="comment-notes"><span id="email-notes">თქვენი იმეილი არ გამოქვეყნდება.</span> სავალდებულო ველები მონიშნულია <span class="required">*</span></p>
            
            <div class="mb-3">
                <label for="comment" class="form-label">კომენტარი <span class="required">*</span></label>
                <textarea id="comment" name="comment" class="form-control" cols="45" rows="8" required></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="author" class="form-label">სახელი <span class="required">*</span></label>
                    <input id="author" name="author" type="text" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">იმეილი <span class="required">*</span></label>
                    <input id="email" name="email" type="email" class="form-control" required>
                </div>
                 <div class="col-md-4 mb-3">
                    <label for="url" class="form-label">ვებ-გვერდი</label>
                    <input id="url" name="url" type="url" class="form-control">
                </div>
            </div>

            <p class="form-submit">
                <button name="submit" type="submit" id="submit" class="btn btn-primary">კომენტარის გამოქვეყნება</button>
                <input type="hidden" name="comment_post_id" value="<?php echo $ok_post_in_loop->id; ?>" id="comment_post_ID">
            </p>
        </form>
    </div></div>