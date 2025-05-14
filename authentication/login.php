<?php
session_start();

$conn = new mysqli("localhost", "root", "", "login");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password_input = $_POST['password']; // Note: name="password" in HTML

    $stmt = $conn->prepare("SELECT pwd FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($password_hashed);

    if ($stmt->fetch() && password_verify($password_input, $password_hashed)) {
        $_SESSION['username'] = $username;
        header("Location: ../index.php");
        exit(); // always exit after header redirect
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>login</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <!-- <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?> -->
    <div class="form-container">
    <form method="POST" action="" >
        Username: <input name="username" required><br>
        Password: <input name="password" type="password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="register.html">New here? Register here.</a>
    </div>
    </body>
</html>