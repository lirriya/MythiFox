<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>

<body>
<?php
$conn = new mysqli("localhost", "root", "", "login");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, pwd) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hash); // fixed here

    if ($stmt->execute()) {
        header("Location: login.php"); // redirect to login page
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?> 

<div class="form-container">
    <form method="POST" action="">
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Register</button>
    </form>
    <a href="login.html">Already have an account? Login here.</a>
</div>
    
    <p><a href="../index.html">Back to main page</a></p>

</body>

</html>