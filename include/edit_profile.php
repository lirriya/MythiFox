<?php

declare(strict_types=1);

require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/account.php';
require_once __DIR__ . '/profile_validation.php';
require_once __DIR__ . '/profile_state.php';
require_once __DIR__ . '/upload_helper.php';
require_once __DIR__ . '/email_change.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit;
}

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
    profile_form_state_save(
        [
            'username' => profile_request_string($_POST, 'new_username'),
            'bio' => profile_request_string($_POST, 'new_bio'),
            'email' => profile_request_string($_POST, 'new_email'),
        ],
        ['form' => 'Your form expired. Reload the page and try again.']
    );
    header('Location: ../profile.php');
    exit;
}

$conn = db_connect();
$userId = account_current_user_id($conn);
$user = $userId === null ? null : account_user_by_id($conn, $userId);

if ($user === null) {
    $conn->close();
    profile_form_state_save([], ['form' => 'Your session is no longer valid. Please sign in again.']);
    header('Location: ../index.php');
    exit;
}

$currentUsername = $user['username'];
$currentEmail = $user['email'];
$newUsername = profile_username_normalize(profile_request_string($_POST, 'new_username'));
$newBio = profile_bio_normalize(profile_request_string($_POST, 'new_bio'));
$submittedEmail = email_change_normalize(profile_request_string($_POST, 'new_email'));
$newEmail = $submittedEmail === '' && $currentEmail !== '' ? $currentEmail : $submittedEmail;
$currentPassword = profile_request_string($_POST, 'current_password');
$removeProfilePicture = profile_request_string($_POST, 'remove_profile_pic') === '1';
$cancelPendingEmail = profile_request_string($_POST, 'cancel_pending_email') === '1'
    && $user['pending_email'] !== '';
$errors = [];
$values = [
    'username' => $newUsername,
    'bio' => $newBio,
    'email' => $newEmail,
];

if (!profile_username_is_valid($newUsername)) {
    $errors['username'] = 'Username must be 3-20 characters: letters, numbers, or underscores only.';
}

if (!profile_bio_is_valid($newBio)) {
    $errors['bio'] = 'Bio must be ' . PROFILE_BIO_MAX_LENGTH . ' characters or fewer.';
}

$emailChanged = $newEmail !== $currentEmail;

if ($cancelPendingEmail) {
    $newEmail = $currentEmail;
    $emailChanged = false;
    $values['email'] = $currentEmail;
}

if ($newEmail !== '' && !email_change_is_valid($newEmail)) {
    $errors['email'] = 'Enter a valid email address.';
}

$identityChanged = $newUsername !== $currentUsername || $emailChanged;

if ($identityChanged && !password_verify($currentPassword, $user['pwd'])) {
    $errors['current_password'] = 'Confirm your password to change your username or email.';
}

if ($newUsername !== $currentUsername) {
    $usernameCheck = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');

    if ($usernameCheck === false) {
        $errors['form'] = 'We could not validate your username right now. Please try again.';
    } else {
        $usernameCheck->bind_param('si', $newUsername, $userId);
        if (!$usernameCheck->execute()) {
            $errors['form'] = 'We could not validate your username right now. Please try again.';
        } else {
            $usernameResult = $usernameCheck->get_result();
            $usernameTaken = $usernameResult->fetch_assoc();
            $usernameCheck->close();

            if ($usernameTaken) {
                $errors['username'] = 'That username is already taken.';
            }
        }
    }
}

if ($emailChanged && !isset($errors['email'])) {
    $emailCheck = $conn->prepare(
        'SELECT id FROM users WHERE (email = ? OR pending_email = ?) AND id <> ? LIMIT 1'
    );

    if ($emailCheck === false) {
        $errors['form'] = 'We could not validate that email address right now. Please try again.';
    } else {
        $emailCheck->bind_param('ssi', $newEmail, $newEmail, $userId);
        if (!$emailCheck->execute()) {
            $errors['form'] = 'We could not validate that email address right now. Please try again.';
        } else {
            $emailResult = $emailCheck->get_result();
            $emailTaken = $emailResult->fetch_assoc();
            $emailCheck->close();

            if ($emailTaken) {
                $errors['email'] = 'That email address cannot be used right now.';
            }
        }
    }
}

$newProfilePicturePath = null;
$newProfilePictureFile = null;
$avatarChanged = false;
$upload = $_FILES['profile_picture'] ?? $_FILES['new_profile_pic'] ?? null;

if (!$removeProfilePicture && is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    [$valid, $uploadError] = validate_uploaded_image($upload);

    if (!$valid) {
        $errors['profile_picture'] = $uploadError;
    } else {
        $extension = uploaded_image_extension($upload);

        if ($extension === null) {
            $errors['profile_picture'] = 'The profile picture could not be processed.';
        } else {
            $uploadDirectory = __DIR__ . '/../uploads/profile_pics/';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                $errors['profile_picture'] = 'The profile picture directory is not available.';
            } else {
                $filename = 'profile_' . bin2hex(random_bytes(16)) . '.' . $extension;
                $destination = $uploadDirectory . $filename;

                if (move_uploaded_file($upload['tmp_name'], $destination)) {
                    $newProfilePicturePath = 'uploads/profile_pics/' . $filename;
                    $newProfilePictureFile = $destination;
                    $avatarChanged = true;
                } else {
                    $errors['profile_picture'] = 'The profile picture could not be saved.';
                }
            }
        }
    }
}

if ($removeProfilePicture) {
    $newProfilePicturePath = 'assets/default.jpg';
    $avatarChanged = true;
}

if ($errors !== []) {
    if ($newProfilePictureFile !== null && is_file($newProfilePictureFile)) {
        unlink($newProfilePictureFile);
    }

    profile_form_state_save($values, $errors);
    $conn->close();
    header('Location: ../profile.php');
    exit;
}

if ($cancelPendingEmail && $avatarChanged) {
    $stmt = $conn->prepare(
        'UPDATE users SET username = ?, bio = ?, pending_email = NULL, profile_pic = ? WHERE id = ?'
    );
    $stmt->bind_param('sssi', $newUsername, $newBio, $newProfilePicturePath, $userId);
} elseif ($cancelPendingEmail) {
    $stmt = $conn->prepare('UPDATE users SET username = ?, bio = ?, pending_email = NULL WHERE id = ?');
    $stmt->bind_param('ssi', $newUsername, $newBio, $userId);
} elseif ($emailChanged && $avatarChanged) {
    $stmt = $conn->prepare(
        'UPDATE users SET username = ?, bio = ?, pending_email = ?, profile_pic = ? WHERE id = ?'
    );
    $stmt->bind_param('ssssi', $newUsername, $newBio, $newEmail, $newProfilePicturePath, $userId);
} elseif ($emailChanged) {
    $stmt = $conn->prepare('UPDATE users SET username = ?, bio = ?, pending_email = ? WHERE id = ?');
    $stmt->bind_param('sssi', $newUsername, $newBio, $newEmail, $userId);
} elseif ($avatarChanged) {
    $stmt = $conn->prepare('UPDATE users SET username = ?, bio = ?, profile_pic = ? WHERE id = ?');
    $stmt->bind_param('sssi', $newUsername, $newBio, $newProfilePicturePath, $userId);
} else {
    $stmt = $conn->prepare('UPDATE users SET username = ?, bio = ? WHERE id = ?');
    $stmt->bind_param('ssi', $newUsername, $newBio, $userId);
}

if ($stmt === false || !$stmt->execute()) {
    if ($newProfilePictureFile !== null && is_file($newProfilePictureFile)) {
        unlink($newProfilePictureFile);
    }
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->close();
    profile_form_state_save($values, ['form' => 'We could not save your profile right now. Please try again.']);
    header('Location: ../profile.php');
    exit;
}

$stmt->close();
$_SESSION['user_id'] = $userId;
$_SESSION['user'] = $newUsername;

if ($avatarChanged) {
    $oldProfilePicture = $user['profile_pic'];
    if (
        $oldProfilePicture !== $newProfilePicturePath
        && str_starts_with($oldProfilePicture, 'uploads/profile_pics/')
    ) {
        $oldProfilePictureFile = __DIR__ . '/../' . $oldProfilePicture;
        if (is_file($oldProfilePictureFile)) {
            unlink($oldProfilePictureFile);
        }
    }
}

$verificationSent = false;

if ($cancelPendingEmail) {
    $invalidate = $conn->prepare(
        'UPDATE email_change_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL'
    );
    if ($invalidate !== false) {
        $invalidate->bind_param('i', $userId);
        $invalidate->execute();
        $invalidate->close();
    }
}

if ($emailChanged) {
    try {
        $mailConfig = \MythiFox\Mailer\mail_config();
        $mailer = \MythiFox\Mailer\MailerFactory::fromEnvironment();
        $mailResult = email_change_issue(
            $conn,
            $userId,
            $newEmail,
            $newUsername,
            $mailConfig['app_url'],
            $mailer
        );
        $verificationSent = $mailResult->sent;
    } catch (Throwable $exception) {
        error_log('MythiFox profile email change setup failed: ' . $exception::class);
    }
}

$conn->close();

if ($cancelPendingEmail) {
    profile_flash_set('success', 'Pending email change cancelled.');
} elseif ($emailChanged && !$verificationSent) {
    profile_flash_set(
        'error',
        'Your profile was saved, but the confirmation email could not be sent. Try the email change again.'
    );
} elseif ($emailChanged) {
    profile_flash_set('success', 'Profile saved. Check your new email address to activate it.');
} else {
    profile_flash_set('success', 'Profile updated.');
}

header('Location: ../profile.php?user=' . rawurlencode($newUsername));
exit;
