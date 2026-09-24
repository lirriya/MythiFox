<?php
session_start();

require_once __DIR__ . '/include/db_config.php';
$conn = db_connect();

if (isset($_GET['user'])) {
  $username = $conn->real_escape_string($_GET['user']);
} else if (isset($_SESSION['user'])) {
  $username = $conn->real_escape_string($_SESSION['user']);
} else {
  header("Location: index.php");
  exit();
}

//préparation pour la récupération des infos utilisateur
$userQuery = $conn->prepare("SELECT id, username, bio, profile_pic, created_at FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userData = $userQuery->get_result();

//vérification si l'utilisateur existe
if ($userData->num_rows == 0) {
  echo "User not found.";
  exit();
}

//récupération des données utilisateur sous forme de tableau associatif
$user = $userData->fetch_assoc();
$userQuery->close();

//vérifie si la session correspond à cet utilisateur
$isMe = false;
if (isset($_SESSION['user'])) {
  if ($_SESSION['user'] == $user['username']) {
    $isMe = true;
  }
}

//préparation pour la récupération des posts de l'utilisateur
$postQuery = $conn->prepare("SELECT p.content, p.category, p.image, p.created_at, u.username, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
$postQuery->bind_param("i", $user['id']);
$postQuery->execute();
$posts = $postQuery->get_result();
$postQuery->close();

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($user['username']) ?> | Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=16">
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

<main class="profile" style="margin: 6% auto 0;">
  <div class="profile-header">
    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="profile-pic">
    <div>
      <h2 class="username"><?= htmlspecialchars($user['username']) ?></h2>
      <p class="bio">
        <strong>Bio:</strong>
        <?php
        if ($user['bio']) {
          echo htmlspecialchars($user['bio']);
        } else {
          echo "<em>No bio yet.</em>";
        }
        ?>
      </p>
      <p class="joined">
        <strong>Joined:</strong>
        <?= date("F j, Y", strtotime($user['created_at'])) ?>
      </p>

      <?php if ($isMe): ?>
      <button id="toggleEditBtn" class="btn btn-ghost btn-sm">Edit Profile</button>

      <form id="epf-form" action="./include/edit_profile.php" method="POST" enctype="multipart/form-data" class="epf-hidden">
        <div class="epf-group">
          <!-- Champ pour changer le nom d'utilisateur -->
          <label for="epf-username" class="epf-label">New Username:</label>
          <input type="text" id="epf-username" name="new_username" class="epf-input" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="epf-group">
          <!-- Champ pour changer la bio -->
          <label for="epf-bio" class="epf-label">New Bio:</label>
          <textarea id="epf-bio" name="new_bio" class="epf-textarea" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>

        <div class="epf-group">
          <!-- Champ pour changer la photo de profil -->
          <label for="epf-pic" class="epf-label">New Profile Picture:</label>
          <input type="file" id="epf-pic" name="new_profile_pic" class="epf-input-file" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <section class="user-posts">
    <h3>
      <?php
      if ($isMe) {
        echo "Your Posts";
      } else {
        echo htmlspecialchars($user['username']) . "'s Posts";
      }
      ?>
    </h3>

    <?php if ($posts->num_rows > 0): ?>
      <!-- Boucle d'affichage des posts -->
      <?php while ($post = $posts->fetch_assoc()): ?>
        <div class="post">
          <div class="post-header">
            <!-- Photo de profil de l'auteur du post -->
            <img src="<?= htmlspecialchars($post['profile_pic']) ?>" alt="Profile Pic" class="profile-pic-small">
            <a href="profile.php?user=<?= urlencode($post['username']) ?>" class="username-post">
              <?= htmlspecialchars($post['username']) ?>
            </a>
            <!-- Date de publication -->
            <span class="post-date">
              <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
            </span>
          </div>

          <!-- Catégorie du post -->
          <div class="post-category">
            Category: <?= htmlspecialchars($post['category']) ?>
          </div>

          <!-- Contenu du post -->
          <div class="post-content">
            <?= nl2br(htmlspecialchars($post['content'])) ?>
          </div>

          <!-- Image associée au post si elle existe -->
          <?php if (!empty($post['image'])): ?>
            <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="post-images">
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>This user hasn't posted anything yet.</p>
    <?php endif; ?>
  </section>
</main>

<?php include 'include/authModal.php'; ?>

<script src="./assets/script.js?v=6"></script>
</body>
</html>
