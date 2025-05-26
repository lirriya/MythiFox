<?php
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$old_username = $_SESSION['user'];
$new_username = isset($_POST['new_username']) ? $_POST['new_username'] : '';
$new_bio = isset($_POST['new_bio']) ? $_POST['new_bio'] : '';

// Path to uploads folder
$upload_dir = __DIR__ . '/../uploads/profile_pics/';

// Initialize variable for profile picture path
$new_profile_pic_path = null;

// Check if a file was uploaded without errors
if (isset($_FILES['new_profile_pic']) && $_FILES['new_profile_pic']['error'] == 0) {
    $tmp_name = $_FILES['new_profile_pic']['tmp_name'];
    $original_name = $_FILES['new_profile_pic']['name'];
    $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // Allowed file extensions
    $allowed_exts = array("jpg", "jpeg", "png", "gif");

    if (in_array($file_ext, $allowed_exts)) {
        // Create uploads folder if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Create unique file name
        $new_file_name = uniqid('profile_') . '.' . $file_ext;

        $destination = $upload_dir . $new_file_name;

        // Move uploaded file to destination folder
        if (move_uploaded_file($tmp_name, $destination)) {
            // Save relative path for database
            $new_profile_pic_path = 'uploads/profile_pics/' . $new_file_name;
        } else {
            echo "Failed to move uploaded file.";
            exit();
        }
    } else {
        echo "File type not allowed. Please upload JPG, PNG, or GIF images.";
        exit();
    }
}

// Prepare SQL to update user info
if ($new_profile_pic_path !== null) {
    // Update username, bio, and profile picture
    $sql = "UPDATE users SET username = ?, bio = ?, profile_pic = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $new_username, $new_bio, $new_profile_pic_path, $old_username);
} else {
    // Update username and bio only
    $sql = "UPDATE users SET username = ?, bio = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $new_username, $new_bio, $old_username);
}

if ($stmt->execute()) {
    // Update session username if changed
    $_SESSION['user'] = $new_username;

    // Redirect to updated profile page
    header("Location: ../profile.php?user=" . urlencode($new_username));
    exit();
} else {
    echo "Error updating profile.";
}

$stmt->close();
$conn->close();
?>

