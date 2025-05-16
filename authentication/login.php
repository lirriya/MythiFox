<?php
session_start();
$conn = new mysqli("localhost", "root", "", "MythiFox");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password_input = $_POST['password'];

    $stmt = $conn->prepare("SELECT pwd FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($password_hashed);

    if ($stmt->fetch() && password_verify($password_input, $password_hashed)) {
        $_SESSION['user'] = $username; // ✅ match with header.php
        header("Location: ../index.php");
        exit();
    } else {
        header("Location: ../index.php?error=1");
        exit();
    }
}
?>

