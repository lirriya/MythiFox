<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Connect to DB
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info
$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, username, bio, profile_pic, `created_at` FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();
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

<main class="profile">
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
      <form action="edit_profile.php" method="get" class="edit-form">
        <button type="submit" class="edit">Edit Profile</button>
      </form>
    </div>
  </div>

  <section class="user-posts">
    <h3>Your Posts</h3>
    <p>(Coming soon...)</p>
  </section>
</main>


</body>
</html>
