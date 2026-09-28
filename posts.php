<?php
require_once __DIR__ . '/include/session_boot.php';
?>

<?php
$category = isset($_GET['category']) ? $_GET['category'] : null;    //on récupère la catégorie depuis l'URL (null si non fournie)
$valid_categories = ['music', 'art', 'quotes', 'games', 'anime', 'coding'];   //liste des catégories valides

//on redirige vers la page des posts si il n y'q pas de catégorie ou catégorie invalide 
if (!$category || !in_array($category, $valid_categories)) {
  header("Location: posts.php");
  exit();
}

require_once __DIR__ . '/include/db_config.php';
$conn = db_connect();

//préparation de la requête SQL pour récupérer les posts de la catégorie choisie
$query = "SELECT posts.*, users.username, users.profile_pic FROM posts JOIN users ON posts.user_id = users.id WHERE posts.category = ? ORDER BY posts.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $category);
$stmt->execute();
$res = $stmt->get_result();
$posts = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MythiFox</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20">
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

<div class="layout">
  <div class="feed">
    <div class="posts">
      <h2>Category: <?php echo htmlspecialchars($category); ?></h2>
      <?php
      if (count($posts) == 0) {
        echo "<p>No posts found in this category.</p>";
      }
      ?>
    </div>

    <div class="posts-list">
  <?php foreach ($posts as $post): ?>
    <div class="post">
      <div class="post-header">
        <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile Pic" class="profile-pic-small">
        <a href="profile.php?user=<?php echo urlencode($post['username']); ?>" class="username-post">
          <?php echo htmlspecialchars($post['username']); ?>
        </a>
        <span class="post-date">
          <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
        </span>
      </div>

      <div class="post-content">
        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
      </div>

      <?php
      if (!empty($post['image'])) {
        echo '<img src="' . htmlspecialchars($post['image']) . '" alt="Post image" class="post-images">';
      }
      ?>
    </div>
  <?php endforeach; ?>
    </div>
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

<?php include 'include/authModal.php'; ?>

<script src="./assets/script.js?v=6"></script>
</body>
</html>
