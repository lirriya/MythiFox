<?php
session_start();
require_once __DIR__ . '/../include/db_config.php';
$conn = db_connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  if (strlen($username) < 3) {
    $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'Username must be at least 3 characters.'];
    header("Location: ../index.php");
    exit();
  }
  if (strlen($password) < 6) {
    $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'Password must be at least 6 characters.'];
    header("Location: ../index.php");
    exit();
  }

  //prépare une requête pour vérifier si le nom d'utilisateur existe déjà en base
  $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
  $check->bind_param("s", $username);
  $check->execute();
  $check->store_result();

  //si au moins un résultat est trouvé, l'utilisateur existe déjà donc redirection avec erreur
  if ($check->num_rows > 0) {
    $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'This username is already taken. Please choose another.'];
    header("Location: ../index.php");
    exit();
  }

  $check->close();

  //génère un hash sécurisé du mot de passe saisi
  $hash = password_hash($password, PASSWORD_DEFAULT);
  //prépare l’insertion du nouvel utilisateur avec son nom et son mot de passe haché
  $stmt = $conn->prepare("INSERT INTO users (username, pwd) VALUES (?, ?)");
  $stmt->bind_param("ss", $username, $hash);
  $stmt->execute();
  $stmt->close();

  //stocke le nom d’utilisateur en session pour le connecter automatiquement après inscription
  $_SESSION['user'] = $username;
  header("Location: ../index.php");
  exit();
}
?>


