<?php

declare(strict_types=1);

require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/profile_validation.php';

header('Content-Type: application/x-www-form-urlencoded; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'success=false&message=method_not_allowed';
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo 'success=false&message=login_required';
    exit;
}

$csrfToken = profile_request_string($_POST, 'csrf');
if ($csrfToken === '' && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = is_string($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
}

if (!csrf_verify($csrfToken)) {
    http_response_code(403);
    echo 'success=false&message=invalid_token';
    exit;
}

$postIdValue = profile_request_string($_POST, 'post_id');

if (!ctype_digit($postIdValue) || (int) $postIdValue < 1) {
    echo 'success=false&message=invalid_post';
    exit;
}

$postId = (int) $postIdValue;
$likedPosts = $_SESSION['liked_posts'] ?? [];

if (!is_array($likedPosts)) {
    $likedPosts = [];
}

if (in_array($postId, array_map('intval', $likedPosts), true)) {
    echo 'success=false&message=already_liked';
    exit;
}

$conn = db_connect();
$exists = $conn->prepare('SELECT id FROM posts WHERE id = ? LIMIT 1');

if ($exists === false) {
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$exists->bind_param('i', $postId);

if (!$exists->execute()) {
    $exists->close();
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$existsResult = $exists->get_result();
$postExists = $existsResult->fetch_assoc();
$exists->close();

if (!$postExists) {
    $conn->close();
    echo 'success=false&message=post_not_found';
    exit;
}

$update = $conn->prepare('UPDATE posts SET likes = likes + 1 WHERE id = ?');

if ($update === false) {
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$update->bind_param('i', $postId);

if (!$update->execute()) {
    $update->close();
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$update->close();
$select = $conn->prepare('SELECT likes FROM posts WHERE id = ? LIMIT 1');

if ($select === false) {
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$select->bind_param('i', $postId);

if (!$select->execute()) {
    $select->close();
    $conn->close();
    http_response_code(500);
    echo 'success=false&message=server_error';
    exit;
}

$selectResult = $select->get_result();
$row = $selectResult->fetch_assoc();
$select->close();
$conn->close();

if (!$row) {
    echo 'success=false&message=post_not_found';
    exit;
}

$likedPosts[] = $postId;
$_SESSION['liked_posts'] = $likedPosts;

echo 'success=true&likes=' . (int) $row['likes'];
