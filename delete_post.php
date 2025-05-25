<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$post_id = $_POST['post_id'] ?? null;
if (!$post_id) { die("No post ID."); }


$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) { die("Post not found."); }

$user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$user_stmt->bind_param("i", $post['user_id']);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

if (!isset($_SESSION['user']) || $_SESSION['user'] !== $user_data['username']) {
    die("Unauthorized.");
}


$delete_stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
$delete_stmt->bind_param("i", $post_id);
if ($delete_stmt->execute()) {
    header("Location: profile.php?user=" . urlencode($_SESSION['user']));
    exit();
} else {
    die("Failed to delete post.");
}
?>
