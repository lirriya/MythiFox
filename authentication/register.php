<?php
session_start();
require_once __DIR__ . '/../include/db_config.php';
$conn = db_connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];

  //prépare une requête pour vérifier si le nom d’utilisateur existe déjà en base
  $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
  $check->bind_param("s", $username);
  $check->execute();
  $check->store_result();

  //si au moins un résultat est trouvé, l’utilisateur existe déjà donc redirection avec erreur
  if ($check->num_rows > 0) {
    header("Location: ../index.php?error=usertaken");
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


