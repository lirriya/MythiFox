<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, pwd) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hash);
    $stmt->execute();
    $stmt->close();

    $_SESSION['user'] = $username; // ✅ match with header.php
    header("Location: ../index.php");
    exit(); // ✅ go back to home after registration
   
}
?>
