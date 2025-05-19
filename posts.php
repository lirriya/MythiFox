<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php 
$category = $_GET['category'] ?? null;
$valid_categories = ['Music', 'Drawings', 'Quotes', 'Games', 'Anime', 'Coding'];
if (!$category || !in_array($category, $valid_categories)) {
    header("Location: posts.php"); // Or show default content
    exit;
}
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM posts WHERE category = ? ORDER BY created_at DESC";
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
    <h2 class="cat">Category: <?php echo htmlspecialchars($category); ?></h2>
    
    <?php foreach ($posts as $post) {  
    echo "<div class='post'>";
    echo "<p>" . htmlspecialchars($post['content']) . "</p>";

    if (!empty($post['image'])) {
        echo "<img src='uploads/" . htmlspecialchars($post['image']) . "' alt='Post Image'>";
    }

    echo "<p><em>Posted on " . htmlspecialchars($post['created_at']) . "</em></p>";
    echo "</div>";
}
?>
<?php if (count($posts) === 0): ?>
  <p>No posts found in this category.</p>
<?php endif; ?>



    <script src="./assets/script.js"></script>
  </body>
</html>