<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password_input = $_POST['password'];

    // Check if username exists
    $stmt = $conn->prepare("SELECT pwd FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($password_hashed);

    if ($stmt->fetch()) {
        // Username found, verify password
        if (password_verify($password_input, $password_hashed)) {
            $_SESSION['user'] = $username;
            header("Location: ../index.php");
            exit();
        } else {
            // Incorrect password
            header("Location: ../index.php?error=password");
            exit();
        }
    } else {
        // Username doesn't exist
        header("Location: ../index.php?error=username");
        exit();
    }

    $stmt->close();
}
$conn->close();
?>

