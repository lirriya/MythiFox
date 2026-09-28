<?php

declare(strict_types=1);

require_once __DIR__ . '/include/session_boot.php';
require_once __DIR__ . '/include/db_config.php';
require_once __DIR__ . '/include/account.php';
require_once __DIR__ . '/include/profile_validation.php';
require_once __DIR__ . '/include/profile_state.php';
require_once __DIR__ . '/include/csrf.php';

$categories = ['music', 'art', 'quotes', 'games', 'anime', 'coding'];
$requestedUsername = isset($_GET['user']) && is_string($_GET['user']) ? trim($_GET['user']) : null;
$sessionUsername = isset($_SESSION['user']) && is_string($_SESSION['user']) ? $_SESSION['user'] : null;
$username = $requestedUsername ?? $sessionUsername;

if ($username === null || !profile_username_is_valid($username)) {
    http_response_code(404);
    echo 'Profile not found.';
    exit;
}

$conn = db_connect();
$user = account_user_by_username($conn, $username);

if ($user === null) {
    $conn->close();
    http_response_code(404);
    echo 'Profile not found.';
    exit;
}

$currentUserId = account_current_user_id($conn);
$isMe = $currentUserId !== null && $currentUserId === $user['id'];
$selectedCategory = null;
$requestedCategory = $_GET['category'] ?? null;

if (is_string($requestedCategory) && in_array($requestedCategory, $categories, true)) {
    $selectedCategory = $requestedCategory;
}

$posts = [];
$postQuery = 'SELECT p.id, p.user_id, p.content, p.category, p.image, p.created_at, p.likes, u.username, u.profile_pic '
    . 'FROM posts p INNER JOIN users u ON u.id = p.user_id WHERE p.user_id = ?';

if ($selectedCategory !== null) {
    $postQuery .= ' AND p.category = ?';
}

$postQuery .= ' ORDER BY p.created_at DESC LIMIT 100';
$stmt = $conn->prepare($postQuery);

if ($stmt === false) {
    $conn->close();
    http_response_code(500);
    echo 'Profile posts are temporarily unavailable.';
    exit;
}

if ($selectedCategory !== null) {
    $categoryValue = $selectedCategory;
    $stmt->bind_param('ii', $user['id'], $categoryValue);
} else {
    $stmt->bind_param('i', $user['id']);
}

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    http_response_code(500);
    echo 'Profile posts are temporarily unavailable.';
    exit;
}

$postResult = $stmt->get_result();
$posts = $postResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stats = [
    'posts' => 0,
    'likes' => 0,
    'categories' => 0,
];
$statsQuery = $conn->prepare(
    'SELECT COUNT(*) AS post_count, COALESCE(SUM(likes), 0) AS like_count, COUNT(DISTINCT category) AS category_count '
    . 'FROM posts WHERE user_id = ?'
);

if ($statsQuery !== false) {
    $statsQuery->bind_param('i', $user['id']);
    if ($statsQuery->execute()) {
        $statsResult = $statsQuery->get_result();
        $statsRow = $statsResult->fetch_assoc();
        if ($statsRow !== null) {
            $stats = [
                'posts' => (int) $statsRow['post_count'],
                'likes' => (int) $statsRow['like_count'],
                'categories' => (int) $statsRow['category_count'],
            ];
        }
    }
    $statsQuery->close();
}

$conn->close();
$formState = profile_form_state_take();
$errors = is_array($formState['errors'] ?? null) ? $formState['errors'] : [];
$formValues = is_array($formState['values'] ?? null) ? $formState['values'] : [];
$editorOpen = $errors !== [];
$flash = profile_flash_take();
$formUsername = is_string($formValues['username'] ?? null) ? $formValues['username'] : $user['username'];
$formBio = is_string($formValues['bio'] ?? null) ? $formValues['bio'] : $user['bio'];
$formEmail = is_string($formValues['email'] ?? null) ? $formValues['email'] : $user['email'];
$profileUrl = 'profile.php?user=' . rawurlencode($user['username']);
$avatarUrl = $user['profile_pic'] !== '' ? $user['profile_pic'] : 'assets/default.jpg';
$hasEmail = $user['email'] !== '';
$emailVerified = $hasEmail && $user['email_verified_at'] !== null;
$fieldError = static function (string $key) use ($errors): string {
    return isset($errors[$key]) && is_string($errors[$key]) ? $errors[$key] : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> | MythiFox</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20">
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
</head>
<body class="profile-page">
<?php include __DIR__ . '/include/header.php'; ?>
<?php include __DIR__ . '/include/sidebar.php'; ?>

<main class="profile-shell">
  <?php if ($flash !== null): ?>
    <div class="toast show profile-toast" role="status" data-auto-toast>
      <?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <section class="profile-hero" aria-labelledby="profile-title">
    <div class="profile-hero-glow" aria-hidden="true"></div>
    <div class="profile-avatar-wrap">
      <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> profile picture" class="profile-avatar">
      <?php if ($emailVerified): ?>
        <span class="profile-verified" title="Verified email" aria-label="Verified email">✓</span>
      <?php endif; ?>
    </div>
    <div class="profile-identity">
      <p class="profile-kicker">MythiFox profile</p>
      <h1 id="profile-title" class="profile-title"><?= htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
      <p class="profile-bio">
        <?= $user['bio'] !== '' ? nl2br(htmlspecialchars($user['bio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) : '<span class="profile-muted">No bio yet.</span>' ?>
      </p>
      <div class="profile-meta">
        <span>Joined <?= htmlspecialchars(date('F j, Y', strtotime($user['created_at'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        <?php if ($user['pending_email'] !== ''): ?>
          <span class="profile-meta-status pending">Email change pending</span>
        <?php elseif ($emailVerified): ?>
          <span class="profile-meta-status verified">Email verified</span>
        <?php elseif ($hasEmail): ?>
          <span class="profile-meta-status">Email not verified</span>
        <?php else: ?>
          <span class="profile-meta-status">No email on file</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($isMe): ?>
      <button class="btn btn-primary profile-edit-trigger" type="button" data-profile-open aria-haspopup="dialog" aria-controls="profile-editor" <?= $editorOpen ? '' : '' ?>>Edit profile</button>
    <?php endif; ?>
  </section>

  <section class="profile-stats" aria-label="Profile statistics">
    <div class="profile-stat">
      <strong><?= number_format($stats['posts']) ?></strong>
      <span>Posts</span>
    </div>
    <div class="profile-stat">
      <strong><?= number_format($stats['likes']) ?></strong>
      <span>Likes received</span>
    </div>
    <div class="profile-stat">
      <strong><?= number_format($stats['categories']) ?></strong>
      <span>Categories</span>
    </div>
  </section>

  <nav class="profile-tabs" aria-label="Profile post filters">
    <a class="profile-tab<?= $selectedCategory === null ? ' active' : '' ?>" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">All posts</a>
    <?php foreach ($categories as $category): ?>
      <?php $categoryUrl = $profileUrl . '&category=' . rawurlencode($category); ?>
      <a class="profile-tab<?= $selectedCategory === $category ? ' active' : '' ?>" href="<?= htmlspecialchars($categoryUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($category), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
    <?php endforeach; ?>
  </nav>

  <section class="profile-posts-section" aria-labelledby="profile-posts-title">
    <div class="profile-section-heading">
      <div>
        <p class="profile-kicker"><?= $selectedCategory === null ? 'All activity' : htmlspecialchars(ucfirst($selectedCategory), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <h2 id="profile-posts-title"><?= $selectedCategory === null ? 'Posts' : htmlspecialchars(ucfirst($selectedCategory) . ' posts', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
      </div>
      <span class="profile-post-count"><?= number_format(count($posts)) ?> shown</span>
    </div>

    <?php if ($posts !== []): ?>
      <div class="profile-posts-grid">
        <?php foreach ($posts as $post): ?>
          <article class="profile-post-card" data-post-id="<?= (int) $post['id'] ?>">
            <header class="profile-post-header">
              <img src="<?= htmlspecialchars($post['profile_pic'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="" class="profile-pic-small">
              <div>
                <a class="profile-post-author" href="<?= htmlspecialchars('profile.php?user=' . rawurlencode($post['username']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($post['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
                <time datetime="<?= htmlspecialchars((string) $post['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars(date('M j, Y · H:i', strtotime($post['created_at'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></time>
              </div>
              <span class="profile-post-category"><?= htmlspecialchars((string) $post['category'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </header>
            <div class="profile-post-content"><?= nl2br(htmlspecialchars((string) $post['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></div>
            <?php if (!empty($post['image'])): ?>
              <img src="<?= htmlspecialchars((string) $post['image'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Post image" class="profile-post-image">
            <?php endif; ?>
            <div class="profile-post-footer">
              <span class="profile-post-likes">♡ <?= (int) $post['likes'] ?> likes</span>
              <?php include __DIR__ . '/include/post_actions.php'; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="profile-empty-state">
        <div class="profile-empty-icon" aria-hidden="true">✦</div>
        <h3>No posts here yet</h3>
        <p><?= $isMe ? 'Share something with the community from the home page.' : 'This profile has no posts in this view yet.' ?></p>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php if ($isMe): ?>
  <div class="profile-editor" data-profile-editor id="profile-editor" role="dialog" aria-modal="true" aria-labelledby="profile-editor-title" <?= $editorOpen ? '' : 'hidden' ?>>
    <div class="profile-editor-backdrop" data-profile-close></div>
    <section class="profile-editor-card">
      <header class="profile-editor-header">
        <div>
          <p class="profile-kicker">Account settings</p>
          <h2 id="profile-editor-title">Edit profile</h2>
        </div>
        <button class="profile-editor-close" type="button" data-profile-close aria-label="Close profile editor">×</button>
      </header>

      <?php if (isset($errors['form'])): ?>
        <p class="form-msg error" role="alert"><?= htmlspecialchars((string) $errors['form'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
      <?php endif; ?>

      <form class="profile-edit-form" action="./include/edit_profile.php" method="POST" enctype="multipart/form-data" data-profile-form>
        <?php echo csrf_field(); ?>

        <div class="profile-avatar-editor">
          <div class="profile-avatar-editor-preview">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Current profile picture" data-avatar-preview>
            <span class="profile-avatar-editor-hint">Crop and resize before saving</span>
          </div>
          <div class="profile-avatar-editor-controls">
            <label class="profile-upload-button" for="profile-avatar-input">Choose a new picture</label>
            <input id="profile-avatar-input" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" data-avatar-input>
            <label class="profile-remove-picture">
              <input type="checkbox" name="remove_profile_pic" value="1" data-remove-avatar>
              <span>Use the default picture</span>
            </label>
            <?php if ($fieldError('profile_picture') !== ''): ?>
              <p class="field-error" role="alert"><?= htmlspecialchars($fieldError('profile_picture'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
            <div class="avatar-crop-panel" data-avatar-crop-panel hidden>
              <canvas width="320" height="320" data-avatar-canvas aria-label="Profile picture crop preview"></canvas>
              <label class="avatar-zoom-label" for="avatar-zoom">Zoom</label>
              <input id="avatar-zoom" type="range" min="1" max="3" step="0.01" value="1" data-avatar-zoom>
              <div class="avatar-crop-actions">
                <button class="btn btn-ghost btn-sm" type="button" data-avatar-reset>Reset crop</button>
                <button class="btn btn-primary btn-sm" type="button" data-avatar-apply>Use this crop</button>
              </div>
            </div>
          </div>
        </div>

        <div class="profile-form-grid">
          <div class="profile-field">
            <label for="profile-username">Username</label>
            <input id="profile-username" name="new_username" type="text" value="<?= htmlspecialchars($formUsername, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" minlength="3" maxlength="20" pattern="[A-Za-z0-9_]{3,20}" autocomplete="username" required data-username-input>
            <p class="field-help">3–20 characters: letters, numbers, or underscores.</p>
            <?php if ($fieldError('username') !== ''): ?>
              <p class="field-error" role="alert"><?= htmlspecialchars($fieldError('username'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>

          <div class="profile-field">
            <label for="profile-email">Email address</label>
            <input id="profile-email" name="new_email" type="email" value="<?= htmlspecialchars($formEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" maxlength="254" autocomplete="email" placeholder="you@example.com">
            <p class="field-help"><?= $user['pending_email'] !== '' ? 'A new address is waiting for confirmation.' : 'Changing your email requires a confirmation link.' ?></p>
            <?php if ($user['pending_email'] !== ''): ?>
              <label class="profile-remove-picture">
                <input type="checkbox" name="cancel_pending_email" value="1" data-cancel-pending-email>
                <span>Cancel the pending change to <?= htmlspecialchars($user['pending_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
              </label>
            <?php endif; ?>
            <?php if ($fieldError('email') !== ''): ?>
              <p class="field-error" role="alert"><?= htmlspecialchars($fieldError('email'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="profile-field">
          <div class="profile-field-label-row">
            <label for="profile-bio">Bio</label>
            <span class="character-count" data-bio-count><?= profile_bio_length($formBio) ?>/<?= PROFILE_BIO_MAX_LENGTH ?></span>
          </div>
          <textarea id="profile-bio" name="new_bio" rows="5" maxlength="<?= PROFILE_BIO_MAX_LENGTH ?>" data-bio-input><?= htmlspecialchars($formBio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
          <?php if ($fieldError('bio') !== ''): ?>
            <p class="field-error" role="alert"><?= htmlspecialchars($fieldError('bio'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>

        <div class="profile-field">
          <label for="profile-current-password">Current password <span class="field-optional">Required for username or email changes</span></label>
          <input id="profile-current-password" name="current_password" type="password" autocomplete="current-password">
          <?php if ($fieldError('current_password') !== ''): ?>
            <p class="field-error" role="alert"><?= htmlspecialchars($fieldError('current_password'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>

        <footer class="profile-editor-footer">
          <button class="btn btn-ghost" type="button" data-profile-close>Cancel</button>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </footer>
      </form>
    </section>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/include/authModal.php'; ?>
<script src="./assets/script.js?v=20"></script>
<script src="./assets/profile.js?v=1"></script>
</body>
</html>
