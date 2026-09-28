<?php

declare(strict_types=1);

require_once __DIR__ . '/csrf.php';

$postId = (int) ($post['id'] ?? 0);
$postOwnerId = (int) ($post['user_id'] ?? 0);
$sessionUserId = isset($_SESSION['user_id']) && ctype_digit((string) $_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;
$sessionUsername = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : '';
$postUsername = is_string($post['username'] ?? null) ? $post['username'] : '';
$isOwner = $postId > 0 && $sessionUserId > 0 && $postOwnerId === $sessionUserId;

if (!$isOwner && $sessionUsername !== '' && $postUsername !== '' && $sessionUsername === $postUsername) {
    $isOwner = true;
}

if (!$isOwner) {
    return;
}

$returnTo = basename($_SERVER['PHP_SELF'] ?? '') === 'profile.php' ? 'profile' : 'index';
?>
<div class="post-actions">
  <button class="btn btn-ghost btn-sm post-edit-toggle" type="button" data-post-edit-toggle data-post-id="<?php echo $postId; ?>" aria-expanded="false" aria-controls="post-editor-<?php echo $postId; ?>">
    Edit
  </button>
  <div class="post-editor" id="post-editor-<?php echo $postId; ?>" data-post-editor="<?php echo $postId; ?>" hidden>
    <form class="post-update-form" method="POST" action="./include/update_post.php">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
      <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <label class="sr-only" for="post-content-<?php echo $postId; ?>">Edit post content</label>
      <textarea id="post-content-<?php echo $postId; ?>" name="content" rows="4" maxlength="20000" required><?php echo htmlspecialchars((string) ($post['content'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
      <div class="post-editor-actions">
        <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
        <button class="btn btn-ghost btn-sm" type="button" data-post-edit-cancel data-post-id="<?php echo $postId; ?>">Cancel</button>
      </div>
    </form>
  </div>
  <form class="delete-form" method="POST" action="./include/delete_post.php" data-confirm-delete>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
  </form>
</div>
