<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_POST['content'])) {
  $postId = (int) $_POST['post_id'];
  $newContent = trim($_POST['content']);

  if (!isset($_SESSION['user'])) {
    die('You must be logged in.');
  }

  $conn = new mysqli("localhost", "root", "", "MythiFox");
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $check = $conn->prepare("SELECT posts.id FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? AND users.username = ?");
  $check->bind_param("is", $postId, $_SESSION['user']);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows == 0) {
    die("Not allowed.");
  }

  $update = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
  $update->bind_param("si", $newContent, $postId);
  $update->execute();

  $check->close();
  $update->close();
  $conn->close();

  header("Location: ../index.php");
  exit;
} else {
  echo "Invalid request.";
}
?>