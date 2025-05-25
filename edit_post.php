<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$post_id = $_GET['id'] ?? null;
if (!$post_id) { die("No post specified."); }


$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) { die("Post not found."); }


if (!isset($_SESSION['user']) || $_SESSION['user'] !== getUsername($post['user_id'], $conn)) {
    die("Unauthorized.");
}

function getUsername($user_id, $conn) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['username'] ?? null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Post</title>
</head>
<body>
<h2>Edit Post</h2>
<form method="POST" action="update_post.php">
    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
    <label>Content:</label><br>
    <textarea name="content" rows="4" cols="50" required><?php echo htmlspecialchars($post['content']); ?></textarea><br><br>
    <button type="submit">Save Changes</button>
</form>
</body>
</html>
