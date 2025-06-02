<?php
session_start();

header('Content-Type: application/json');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour aimer un post.']);
    exit;
}

// Vérifie si l'ID du post est envoyé
if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du post manquant.']);
    exit;
}

$post_id = intval($_POST['post_id']);

// Initialise la liste des posts likés en session si elle n'existe pas encore
if (!isset($_SESSION['liked_posts'])) {
    $_SESSION['liked_posts'] = [];
}

// Vérifie si l'utilisateur a déjà liké ce post dans cette session
if (in_array($post_id, $_SESSION['liked_posts'])) {
    echo json_encode(['success' => false, 'message' => 'already_liked']);
    exit;
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "MythiFox");

// Gestion d'erreur de connexion
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Échec de la connexion à la base de données.']);
    exit;
}

// Incrémente le compteur de likes pour le post
$sql = "UPDATE posts SET likes = likes + 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();

// Récupère le nombre de likes mis à jour
$sql = "SELECT likes FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$likes = $result->fetch_assoc()['likes'];

// Ajoute ce post à la liste des posts likés en session
$_SESSION['liked_posts'][] = $post_id;

// Retourne la réussite avec le nouveau nombre de likes
echo json_encode(['success' => true, 'likes' => $likes]);

$conn->close();
