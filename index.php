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
  <link rel="stylesheet" href="./assets/styles.css">
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

<?php if (isset($_SESSION['user'])): ?>
  <?php include 'include/makePost.php'; ?>
<?php endif; ?>

<?php
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT posts.*, users.username, users.profile_pic FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC";
$result = $conn->query($sql);
?>

<?php if ($result && $result->num_rows > 0): ?>
  <div class="posts-list">
    <?php while ($post = $result->fetch_assoc()): ?>
      <div class="post" data-post-id="<?php echo $post['id']; ?>">
        <div class="post-header">
          <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile Pic" class="profile-pic-small">
          <a href="profile.php?user=<?php echo urlencode($post['username']); ?>" class="username-post">
            <?php echo htmlspecialchars($post['username']); ?>
          </a>
          <span class="post-date"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
        </div>

        <div class="post-category">
          Category: <?php echo htmlspecialchars($post['category']); ?>
        </div>

        <div class="post-content">
          <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>

        <?php if (!empty($post['image'])): ?>
          <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-images">
        <?php endif; ?>

        <?php if (isset($_SESSION['user']) && $_SESSION['user'] === $post['username']): ?>
          <div class="post-actions">
            <button id="toggleEditBtn" class="edit">Edit Post</button>

            <div class="epf-group">
              <form id="epf-form" method="POST" action="./include/update_post.php" class="epf-hidden">
                <textarea name="content" rows="3" style="width: 100%;"><?php echo htmlspecialchars($post['content']); ?></textarea>
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <button type="submit" class="edit-btn">Save</button>
              </form>
            </div>

            <form method="POST" action="./include/delete_post.php" onsubmit="return confirm('Are you sure you want to delete this post?');" style="display:inline;">
              <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
              <button type="submit" class="delete-btn">Delete</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <p>No posts yet. Be the first to post!</p>
<?php endif; ?>

<?php $conn->close(); ?>

<div class="wrapper">
  <span class="close">
    <img class="close" src="assets/close.png" alt="close">
  </span>
  <input type="checkbox" id="chk" aria-hidden="true">

  <div class="signup">
    <form method="POST" action="./authentication/register.php">
      <label for="chk" aria-hidden="true">Sign up</label>
      <input type="text" name="username" class="signupPH" placeholder="Username" required>
      <input type="password" name="password" class="signupPH" placeholder="Password" required>
      <button class="btnPopup1">Sign up</button>
    </form>
  </div>

  <div class="login">
    <form method="POST" action="./authentication/login.php">
      <label for="chk" aria-hidden="true">Login</label>
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button class="btnPopup2">Login</button>
    </form>
  </div>
</div>

<?php if (isset($_GET['error'])): ?>
  <script>
    const loginError = "<?php echo $_GET['error']; ?>";
  </script>
<?php endif; ?>

<script src="./assets/script.js"></script>
</body>
</html>



