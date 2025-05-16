<?php

// Connect to DB
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info
$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, username, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();
$user_id = $user['id'];
$profile_pic = $user['profile_pic'];
?>

<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $content = $_POST['content'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    $image_path = null;

    if (!$category || !$content) {
        echo '<p class="error">Please fill out all required fields.</p>';
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                echo '<p class="error">Image upload failed.</p>';
            }
        }

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO posts (user_id, category, content, image, created_at) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $category, $content, $image_path, $created_at);
            if ($stmt->execute()) {
                echo '<p class="success">Post created successfully!</p>';
            } else {
                echo '<p class="error">Something went wrong. Please try again.</p>';
            }
        } else {
            echo '<p class="error">DB error preparing statement.</p>';
        }
    }
}
?>

<section>
    <div class="create-post">
        <div class="create-post-input">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
            <textarea name="content" id="content" rows="2" placeholder="Post something.." required></textarea>
        </div>
        <div class="create-post-links">
            <li><img src="assets/camera.png" alt="add photo" /></li>
        </div>
    
    </div>
    


  <form action="makePost.php" method="post" enctype="multipart/form-data">
    
    <select name="category" id="category" required>
      <option value="">-- Select Category --</option>
      <option value="music">Music</option>
      <option value="art">Art</option>
      <option value="quotes">Quotes</option>
      <option value="games">Games</option>
      <option value="anime">Anime</option>
      <option value="coding">Coding</option>
    </select>

    <input type="file" name="image" id="image" accept="image/*">

    <button type="submit" class="btnPost">Post</button>
  </form>
</section>