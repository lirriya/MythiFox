<?php
session_start();

$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}

$old_username = $_SESSION['user'];
$new_username = $_POST['new_username'] ?? '';
$new_bio = $_POST['new_bio'] ?? '';

$upload_dir = __DIR__ . '/../uploads/profile_pics/';
$new_profile_pic_path = null;

if (isset($_FILES['new_profile_pic']) && $_FILES['new_profile_pic']['error'] == 0) {
  $tmp_name = $_FILES['new_profile_pic']['tmp_name'];
  $original_name = $_FILES['new_profile_pic']['name'];
  $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
  $allowed_exts = ["jpg", "jpeg", "png", "gif"];

  if (in_array($file_ext, $allowed_exts)) {
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    
    $new_file_name = uniqid('profile_') . '.' . $file_ext;
    $destination = $upload_dir . $new_file_name;

    if (move_uploaded_file($tmp_name, $destination)) {
      $new_profile_pic_path = 'uploads/profile_pics/' . $new_file_name;
    } else {
      echo "Failed to upload file.";
      exit();
    }
  } else {
    echo "Invalid file type.";
    exit();
  }
}

if ($new_profile_pic_path) {
  $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ?, profile_pic = ? WHERE username = ?");
  $stmt->bind_param("ssss", $new_username, $new_bio, $new_profile_pic_path, $old_username);
} else {
  $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ? WHERE username = ?");
  $stmt->bind_param("sss", $new_username, $new_bio, $old_username);
}

if ($stmt->execute()) {
  $_SESSION['user'] = $new_username;
  header("Location: ../profile.php?user=" . urlencode($new_username));
  exit();
} else {
  echo "Update failed.";
}

$stmt->close();
$conn->close();
?>

