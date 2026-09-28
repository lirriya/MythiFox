<?php

declare(strict_types=1);

require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/account.php';
require_once __DIR__ . '/profile_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit;
}

$postIdValue = profile_request_string($_POST, 'post_id');
$content = trim(profile_request_string($_POST, 'content'));
if (!ctype_digit($postIdValue) || (int) $postIdValue < 1) {
    http_response_code(422);
    exit;
}

if ($content === '' || profile_bio_length($content) > 20000) {
    http_response_code(422);
    exit;
}

$conn = db_connect();
$userId = account_current_user_id($conn);

if ($userId === null) {
    $conn->close();
    http_response_code(401);
    exit;
}

$stmt = $conn->prepare('UPDATE posts SET content = ? WHERE id = ? AND user_id = ?');

if ($stmt === false) {
    $conn->close();
    http_response_code(500);
    exit;
}

$postId = (int) $postIdValue;
$stmt->bind_param('sii', $content, $postId, $userId);

if (!$stmt->execute() || $stmt->affected_rows < 1) {
    $stmt->close();
    $conn->close();
    http_response_code(403);
    exit;
}

$stmt->close();
$returnTo = profile_request_string($_POST, 'return_to');
$conn->close();

if ($returnTo === 'profile') {
    header('Location: ../profile.php');
} else {
    header('Location: ../index.php');
}
exit;
