<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php 
$category = $_GET['category'] ?? null;
$valid_categories = ['Music', 'Art', 'Quotes', 'Games', 'Anime', 'Coding'];
if (!$category || !in_array($category, $valid_categories)) {
    header("Location: posts.php"); // Or show default content
    exit;
}
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT posts.*, users.username, users.profile_pic 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.category = ? 
        ORDER BY posts.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $category); // "s" means string
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MythiFox</title>
    <link rel="stylesheet" href="./assets/styles.css">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    
  </head>
  <body>
    <?php include 'include/header.php'; ?>
    <?php include 'include/sidebar.php'; ?>  
    <div class="posts">
    <h2>Category: <?php echo htmlspecialchars($category); ?></h2>
    <?php if (count($posts) === 0): ?>
    <p>No posts found in this category.</p>
    <?php endif; ?>
    </div>
    </div>
    <div class="posts-list">
    <?php foreach ($posts as $post): ?>  
        <div class="post">
            <div class="post-header">
              <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile Pic" class="profile-pic-small"/>
              <a href="profile.php?user=<?php echo urlencode($post['username']); ?>" class="username-post">
                <?php echo htmlspecialchars($post['username']); ?></a>
              <span class="post-date"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
            </div>
            <div class="post-content">
              <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            <?php if ($post['image']): ?>
              <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-images" />
            <?php endif; ?>
          </div>
    <?php endforeach; ?>
    </div>
    

    <script src="./assets/script.js"></script>
  </body>
</html>