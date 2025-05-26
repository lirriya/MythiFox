<?php
session_start();

// 1. Connect to database
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// 2. Decide which username to show: URL ?user= or the logged-in user
if (isset($_GET['user'])) {
  $username = $conn->real_escape_string($_GET['user']);
} elseif (isset($_SESSION['user'])) {
  $username = $conn->real_escape_string($_SESSION['user']);
} else {
  // no user to show → back to home
  header("Location: index.php");
  exit;
}

// 3. Fetch user info
$userStmt = $conn->prepare("
    SELECT id, username, bio, profile_pic, created_at
    FROM users
    WHERE username = ?
");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
  // user not found
  echo "User not found.";
  exit;
}

$user = $userResult->fetch_assoc();
$userStmt->close();

// Am I viewing my own profile?
$isOwnProfile = (isset($_SESSION['user']) && $_SESSION['user'] === $user['username']);

// 4. Fetch this user’s posts
$postSql = "
    SELECT p.content, p.category, p.image, p.created_at,
           u.username, u.profile_pic
    FROM posts AS p
    JOIN users AS u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
";
$postStmt = $conn->prepare($postSql);
$postStmt->bind_param("i", $user['id']);
$postStmt->execute();
$postsResult = $postStmt->get_result();
$postStmt->close();

// 5. Close DB connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($user['username']) ?> | Profile</title>
  <link rel="stylesheet" href="./assets/styles.css">
</head>

<body>

  <?php include 'include/header.php'; ?>
  <?php include 'include/sidebar.php'; ?>

  <main class="profile" style="margin: 6% auto 0;">
    <!-- Profile header -->
    <div class="profile-header">
      <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="profile-pic" />
      <div>
        <h2 class="username"><?= htmlspecialchars($user['username']) ?></h2>
        <p class="bio">
          <strong>Bio:</strong>
          <?php if ($user['bio']): ?>
            <?= htmlspecialchars($user['bio']) ?>
          <?php else: ?>
            <em>No bio yet.</em>
          <?php endif; ?>
        </p>
        <p class="joined">
          <strong>Joined:</strong>
          <?= date("F j, Y", strtotime($user['created_at'])) ?>
        </p>

        <?php if ($isOwnProfile): ?>
          <!-- Toggle Button -->
          <button id="toggleEditBtn" class="edit">Edit Profile</button>

          <!-- Hidden Edit Form -->
          <form id="epf-form" action="./include/edit_profile.php" method="POST" enctype="multipart/form-data" class="epf-hidden">
            <div class="epf-group">
              <label for="epf-username" class="epf-label">New Username:</label>
              <input type="text" id="epf-username" name="new_username" class="epf-input"
                value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="epf-group">
              <label for="epf-bio" class="epf-label">New Bio:</label>
              <textarea id="epf-bio" name="new_bio" class="epf-textarea"
                rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <div class="epf-group">
              <label for="epf-pic" class="epf-label">New Profile Picture:</label>
              <input type="file" id="epf-pic" name="new_profile_pic" class="epf-input-file" accept="image/*">
            </div>

            <button type="submit" class="edit">Save Changes</button>
          </form>

        <?php endif; ?>

      </div>
    </div>

    <!-- User posts -->
    <section class="user-posts">
      <h3>
        <?= $isOwnProfile
          ? "Your Posts"
          : htmlspecialchars($user['username']) . "'s Posts" ?>
      </h3>

      <?php if ($postsResult->num_rows > 0): ?>
        <?php while ($post = $postsResult->fetch_assoc()): ?>
          <div class="post">
            <div class="post-header">
              <img src="<?= htmlspecialchars($post['profile_pic']) ?>" alt="Profile Pic"
                class="profile-pic-small" />
              <a href="profile.php?user=<?= urlencode($post['username']) ?>" class="username-post">
                <?= htmlspecialchars($post['username']) ?>
              </a>
              <span class="post-date">
                <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
              </span>
            </div>

            <div class="post-category">
              Category: <?= htmlspecialchars($post['category']) ?>
            </div>

            <div class="post-content">
              <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>

            <?php if (!empty($post['image'])): ?>
              <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="post-images" />
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>This user hasn't posted anything yet.</p>
      <?php endif; ?>
    </section>
  </main>

  <script src="./assets/script.js"></script>
</body>

</html>