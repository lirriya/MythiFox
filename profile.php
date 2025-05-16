<?php
session_start();

$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['user'])) {
    $username = $conn->real_escape_string($_GET['user']);
} elseif (isset($_SESSION['user'])) {
    $username = $conn->real_escape_string($_SESSION['user']);
} else {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, username, bio, profile_pic, created_at FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();
$isOwnProfile = isset($_SESSION['user']) && $_SESSION['user'] === $user['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($user['username']); ?> | Profile</title>
  <link rel="stylesheet" href="./assets/styles.css">
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

<main class="profile" style="margin: 6% auto 0;">
  <div class="profile-header">
    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
    <div>
      <h2 class="username"><?php echo htmlspecialchars($user['username']); ?></h2>
      <p class="bio">
        <strong>Bio:</strong> 
        <?php echo $user['bio'] ? htmlspecialchars($user['bio']) : '<em>No bio yet.</em>'; ?>
      </p>
      <p class="joined">
        <strong>Joined:</strong> <?php echo date("F j, Y", strtotime($user['created_at'])); ?>
      </p>

      <?php if ($isOwnProfile): ?>
        <form action="edit_profile.php" method="get" class="edit-form">
          <button type="submit" class="edit">Edit Profile</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <section class="user-posts">
    <h3><?php echo $isOwnProfile ? "Your Posts" : htmlspecialchars($user['username']) . "'s Posts"; ?></h3>

    <?php
    $uid = $user['id'];

    $posts = $conn->query("
        SELECT posts.*, users.username, users.profile_pic 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.user_id = $uid 
        ORDER BY posts.created_at DESC
    ");

    if ($posts && $posts->num_rows > 0) {
        while ($post = $posts->fetch_assoc()) {
            echo "<div class='post'>";

            echo "<div class='post-header'>";
            echo "<img src='" . htmlspecialchars($post['profile_pic']) . "' alt='Profile Pic' class='profile-pic-small' />";
            echo "<a href='profile.php?user=" . urlencode($post['username']) . "' class='username-post'>" . htmlspecialchars($post['username']) . "</a>";
            echo "<span class='post-date'>" . date('M d, Y H:i', strtotime($post['created_at'])) . "</span>";
            echo "</div>";

            echo "<div class='post-category'>Category: " . htmlspecialchars($post['category']) . "</div>";

            echo "<div class='post-content'>" . nl2br(htmlspecialchars($post['content'])) . "</div>";

            if (!empty($post['image'])) {
                echo "<img src='" . htmlspecialchars($post['image']) . "' alt='Post image' class='post-images' />";
            }

            echo "</div>"; 
        }
    } else {
        echo "<p>This user hasn't posted anything yet.</p>";
    }
    ?>
  </section>
</main>

<script src="./assets/script.js"></script>
</body>
</html>
