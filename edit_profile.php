<?php
session_start();

$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['user'];

$stmt = $conn->prepare("SELECT username, email, bio, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['username']); ?> | Edit Profile</title>
</head>
<body>

<?php include 'include/header.php'; ?>
<?php include 'include/sidebar.php'; ?>

<main class="edit-profile-form" style="margin: 6% auto 0; max-width: 600px;">
    <h2>Edit Profile</h2>
    <form action="update_profile.php" method="POST" enctype="multipart/form_data">

        <label for="username">Username:</label><br />
        <input type="text" id="username" name=username" value="<?php echo htmlspecialchars($user['username']); ?>" required /><br /><br />

        <label for="email">Email:</label><br />
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required /><br /><br />

        <label for="bio">Bio:</label><br />
        <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?>" required /><br /><br />
        
        <label for="profile_pic">Profile Picture:</label><br />
        <input type="file" id="profile_pic" name="profile_pic" accept="image/*" /><br />
        <?php if (!empty($user['profile_pic'])) :?>
            <p>Current picture:</p>
            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" style="height:80px; border-radius:50%;" />
        <?php endif; ?>
        <br /><br />

        <button type="submit">Save Changes</button>
    </form>
</main>
<script src="./assets/script.js"></script>
</body>
</html>