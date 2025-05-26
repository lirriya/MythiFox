<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Username taken
        header("Location: ../index.php?error=usertaken");
        exit();
    }

    $check->close();

    // Proceed with registration
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, pwd) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hash);
    $stmt->execute();
    $stmt->close();

    $_SESSION['user'] = $username; 
    header("Location: ../index.php");
    exit(); 
}
?>

