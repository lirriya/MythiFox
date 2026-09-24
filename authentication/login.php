<?php
session_start();
require_once __DIR__ . '/../include/db_config.php';
$conn = db_connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //récupère le nom d’utilisateur et le mot de passe saisis par l’utilisateur
  $username = $_POST['username'];
  $password_input = $_POST['password'];

  //prépare une requête SQL pour récupérer le mot de passe (haché) associé au nom d’utilisateur
  $stmt = $conn->prepare("SELECT pwd FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($password_hashed);   //lie le résultat (le mot de passe haché) à la variable $password_hashed

  //si un utilisateur correspondant est trouvé
  if ($stmt->fetch()) {
    //vérifie si le mot de passe saisi correspond au mot de passe haché en base (vérification sécurisée)
    if (password_verify($password_input, $password_hashed)) {
      //si le mot de passe est correct, stocke l’utilisateur en session et redirige vers la page d’accueil
      $_SESSION['user'] = $username;
      //si le mot de passe est incorrect, redirige vers l’accueil avec une erreur spécifique
      header("Location: ../index.php");
      exit();
    } else {
      header("Location: ../index.php?error=password");    //si l’utilisateur n’est pas trouvé, redirige vers l’accueil avec une erreur spécifique
      exit();
    }
  } else {
    header("Location: ../index.php?error=username");
    exit();
  }

  $stmt->close();
}

$conn->close();
?>


