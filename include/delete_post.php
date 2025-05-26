<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $postId = intval($_POST['post_id']);

    if (!isset($_SESSION['user'])) {
        die('You must be logged in to delete a post.');
    }

    $conn = new mysqli("localhost", "root", "", "MythiFox");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the user owns the post
    $stmt = $conn->prepare("SELECT posts.id FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? AND users.username = ?");
    $stmt->bind_param("is", $postId, $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("You are not allowed to delete this post.");
    }

    // Delete it
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: ../index.php");
    exit;
} else {
    echo "Invalid request.";
}
?>
