<?php
session_start();
header('Content-Type: application/x-www-form-urlencoded');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    echo "success=false&message=Vous devez être connecté pour aimer un post.";
    exit;
}

// Vérifie si l'ID du post est envoyé
if (!isset($_POST['post_id'])) {
    echo "success=false&message=ID du post manquant.";
    exit;
}

$post_id = intval($_POST['post_id']);

// Initialise la liste des posts likés en session si elle n'existe pas
if (!isset($_SESSION['liked_posts'])) {
    $_SESSION['liked_posts'] = [];
}

// Vérifie si déjà liké
if (in_array($post_id, $_SESSION['liked_posts'])) {
    echo "success=false&message=already_liked";
    exit;
}

// Connexion à la base
$conn = new mysqli("localhost", "root", "", "MythiFox");
if ($conn->connect_error) {
    echo "success=false&message=Échec de la connexion à la base de données.";
    exit;
}

// Incrémente les likes
$sql = "UPDATE posts SET likes = likes + 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();

// Récupère le nouveau nombre de likes
$sql = "SELECT likes FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$likes = $row['likes'];

// Ajoute à la session
$_SESSION['liked_posts'][] = $post_id;

// Retourne le résultat
echo "success=true&likes=" . $likes;

$conn->close();
?>