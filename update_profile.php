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

$newUsername = trim($_POST['username']);
$newEmail = trim($_POST['email']);
$newBio = trim($_POST['bio']);

if (empty($newUsername) || empty($newEmail)) {
    die("Username and email are required.");

}

if ($newUsername !== $currentUsername) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $newUsername);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Username already taken, please choose another.");
    }
    $stmt->close();
}

$profilePicPath = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
    $fileName = basename($_FILES['profile_pic']['name']);
    $fileSize = $_FILES['profile_pic']['size'];
    $fileType = mime_content_type($fileTmpPath);

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

if (!in_array($fileType, $allowedTypes)) {
        die("Only JPG, PNG and GIF files are allowed for profile pictures.");
    }

if ($fileSize > 2 * 1024 * 1024) {
        die("Profile picture must be less than 2MB.");
    }

$newFileName = uniqid('profile_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
    $uploadDir = 'uploads/profile_pics/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmpPath, $destPath)) {
        die("Failed to upload profile picture.");
    }
    $profilePicPath = $destPath;
}

if ($profilePicPath) {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ?, profile_pic = ? WHERE username = ?");
    $stmt->bind_param("sssss", $newUsername, $newEmail, $newBio, $profilePicPath, $currentUsername);
} else {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE username = ?");
    $stmt->bind_param("ssss", $newUsername, $newEmail, $newBio, $currentUsername);
}

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['user'] = $newUsername;
    header("Location: profile.php?user=" . urlencode($newUsername));
    exit();
} else {
    die("Error updating profile: " . $conn->error);
}