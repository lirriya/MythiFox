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

if (!ctype_digit($postIdValue) || (int) $postIdValue < 1) {
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

$postId = (int) $postIdValue;
$stmt = $conn->prepare('SELECT id, image FROM posts WHERE id = ? AND user_id = ? LIMIT 1');

if ($stmt === false) {
    $conn->close();
    http_response_code(500);
    exit;
}

$stmt->bind_param('ii', $postId, $userId);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    http_response_code(500);
    exit;
}

$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    $conn->close();
    http_response_code(403);
    exit;
}

$delete = $conn->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');

if ($delete === false) {
    $conn->close();
    http_response_code(500);
    exit;
}

$delete->bind_param('ii', $postId, $userId);

if (!$delete->execute() || $delete->affected_rows !== 1) {
    $delete->close();
    $conn->close();
    http_response_code(403);
    exit;
}

$delete->close();
$conn->close();

$imagePath = is_string($post['image'] ?? null) ? $post['image'] : '';

if ($imagePath !== '' && str_starts_with($imagePath, 'uploads/')) {
    $file = __DIR__ . '/../' . $imagePath;
    if (is_file($file)) {
        unlink($file);
    }
}

header('Location: ../index.php');
exit;
