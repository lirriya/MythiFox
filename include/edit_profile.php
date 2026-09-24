<?php
require_once __DIR__ . '/session_boot.php';

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/upload_helper.php';
$conn = db_connect();

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
  [$valid, $error] = validate_uploaded_image($_FILES['new_profile_pic']);
  if (!$valid) {
    echo $error;
    exit();
  }

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
  }

  $file_ext = strtolower(pathinfo($_FILES['new_profile_pic']['name'], PATHINFO_EXTENSION));
  $new_file_name = uniqid('profile_') . '.' . $file_ext;
  $destination = $upload_dir . $new_file_name;

  if (move_uploaded_file($_FILES['new_profile_pic']['tmp_name'], $destination)) {
    $new_profile_pic_path = 'uploads/profile_pics/' . $new_file_name;
  } else {
    echo "Failed to upload file.";
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

