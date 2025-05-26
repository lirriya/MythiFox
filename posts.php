<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>

<?php
$category = isset($_GET['category']) ? $_GET['category'] : null;    //on récupère la catégorie depuis l'URL (null si non fournie)
$valid_categories = ['Music', 'Art', 'Quotes', 'Games', 'Anime', 'Coding'];   //liste des catégories valides

//on redirige vers la page des posts si il n y'q pas de catégorie ou catégorie invalide 
if (!$category || !in_array($category, $valid_categories)) {
  header("Location: posts.php");
  exit();
}

$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);    //vérification si la connexion échoue
}

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
  <link rel="stylesheet" href="./assets/styles.css">
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

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

<script src="./assets/script.js"></script>
</body>
</html>
