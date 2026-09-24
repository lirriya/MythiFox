<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>MythiFox</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=14">
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>


<body>

  <?php include 'include/header.php'; ?>
  <?php include 'include/sidebar.php'; ?>

  <div class="layout">
    <div class="feed">
      <?php if (isset($_SESSION['user'])): ?>
        <?php include 'include/makePost.php'; ?>
      <?php endif; ?>

  <?php
  require_once __DIR__ . '/include/db_config.php';
  $conn = db_connect();

  $sql = "SELECT posts.*, users.username, users.profile_pic FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC";
  $result = $conn->query($sql);
  ?>

  <!-- Vérifie s'il y a des posts à afficher -->
  <?php if ($result && $result->num_rows > 0): ?>
    <div class="posts-list">
      <?php while ($post = $result->fetch_assoc()): ?>
        <div class="post" data-post-id="<?php echo $post['id']; ?>">
          <div class="post-header">
            <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile Pic" class="profile-pic-small">
            <!-- Lien vers le profil de l'utilisateur -->
            <a href="profile.php?user=<?php echo urlencode($post['username']); ?>" class="username-post">
              <?php echo htmlspecialchars($post['username']); ?>
            </a>
            <!-- Date de création du post -->
            <span class="post-date"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
          </div>

          <!-- Catégorie du post -->
          <div class="post-category">
            Category: <?php echo htmlspecialchars($post['category']); ?>
          </div>

          <!-- Contenu du post -->
          <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
          </div>

          <div class="post-likes">
            <?php if (isset($_SESSION['user'])): ?>
              <?php $liked_post = isset($_SESSION['user']) && in_array($post['id'], $_SESSION['liked_posts'] ?? []); ?>
              <button class="like-btn<?php echo $liked_post ? ' liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                <svg class="icon icon-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                <span class="like-count"><?php echo $post['likes']; ?></span>
              </button>
            <?php else: ?>
              <span class="login-prompt"><svg class="icon icon-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                <?php echo $post['likes']; ?> (login to like)
              </span>
            <?php endif; ?>

            <?php if (!empty($post['image'])): ?>
              <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-images">
            <?php endif; ?>
            <!-- Pop up si post déjà aimé - unique à ce post -->
            <div class="popup">You already liked that post !</div>
          </div>

          <div class="post-actions">
            <!-- Si l'utilisateur connecté est l'auteur du post on lui affiche les options d'édition -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user'] === $post['username']): ?>
              <div class="post-actions">
                <button id="toggleEditBtn" class="btn btn-ghost btn-sm">Edit Post</button>

                <div class="epf-group">
                  <!-- Formulaire pour enregistrer les modifications -->
                  <form id="epf-form" method="POST" action="./include/update_post.php" class="epf-hidden">
                    <textarea name="content" rows="3"
                      style="width: 100%;"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                  </form>
                </div>

                <!-- Formulaire pour supprimer le post -->
                <form method="POST" action="./include/delete_post.php"
                  onsubmit="return confirm('Are you sure you want to delete this post?');" style="display:inline;">
                  <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
        <!-- Message si aucun post trouvé -->
        <p class="no-posts">No posts yet. Be the first to post!</p>
      <?php endif; ?>
    </div>

    <aside class="right-rail">
      <div class="rail-card about-card">
        <img src="assets/logo.png" alt="MythiFox mascot" class="about-logo">
        <h2 class="about-title">MythiFox</h2>
        <p class="about-text">A cozy little corner of the internet. Music, art, quotes, and thoughts — shared softly.</p>
      </div>
      <div class="rail-card top-posts-card">
        <h3 class="rail-card-title">Top Liked</h3>
        <ul class="top-posts">
          <?php
          $topSql = "SELECT posts.id, posts.content, posts.likes, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.likes DESC LIMIT 5";
          $topResult = $conn->query($topSql);
          ?>
          <?php if ($topResult && $topResult->num_rows > 0): ?>
            <?php while ($top = $topResult->fetch_assoc()): ?>
              <li>
                <a href="profile.php?user=<?php echo urlencode($top['username']); ?>" class="top-post-link">
                  <?php echo htmlspecialchars(mb_substr($top['content'], 0, 60)); ?><?php echo mb_strlen($top['content']) > 60 ? '…' : ''; ?>
                </a>
                <span class="top-post-likes">
                  <svg class="icon icon-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                  <span><?php echo $top['likes']; ?></span>
                </span>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li class="top-posts-empty">No likes yet — be the first!</li>
          <?php endif; ?>
        </ul>
      </div>
    </aside>
  </div>

  <?php $conn->close(); ?>

  <!-- Fenêtre d'inscription et de connexion -->
  <div class="wrapper">
    <span class="close">
      <svg class="icon icon-close" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </span>
    <input type="checkbox" id="chk" aria-hidden="true">

    <!-- Formulaire d'inscription -->
    <div class="signup">
      <form method="POST" action="./authentication/register.php">
        <label for="chk" aria-hidden="true">Sign up</label>
        <input type="text" name="username" class="signupPH" placeholder="Username" required>
        <input type="password" name="password" class="signupPH" placeholder="Password" required>
        <button class="btn btn-primary btn-block">Sign up</button>
      </form>
    </div>

    <!-- Formulaire de connexion -->
    <div class="login">
      <form method="POST" action="./authentication/login.php">
        <label for="chk" aria-hidden="true">Login</label>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn btn-primary btn-block">Login</button>
      </form>
    </div>
  </div>

  <?php if (isset($_GET['error'])): ?>
    <script>
      const loginError = "<?php echo $_GET['error']; ?>";
    </script>
  <?php endif; ?>

  <script src="./assets/script.js?v=5"></script>
</body>

</html>