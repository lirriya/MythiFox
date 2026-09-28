<?php

declare(strict_types=1);

require dirname(__DIR__) . '/include/session_boot.php';
require dirname(__DIR__) . '/include/csrf.php';

$failures = [];

function render_post_actions(array $post, array $session): string
{
    $_SESSION = $session;
    ob_start();
    require dirname(__DIR__) . '/include/post_actions.php';

    return (string) ob_get_clean();
}

$ownerPost = [
    'id' => 42,
    'user_id' => 7,
    'username' => 'owner_user',
    'content' => 'hello <b>world</b>',
];

$_SERVER['PHP_SELF'] = '/index.php';

$ownerMarkup = render_post_actions($ownerPost, ['user_id' => 7, 'user' => 'owner_user']);

if ($ownerMarkup === '') {
    $failures[] = 'owner_sees_actions';
}

if (!str_contains($ownerMarkup, 'data-post-edit-toggle')) {
    $failures[] = 'owner_sees_edit_toggle';
}

if (!str_contains($ownerMarkup, 'name="post_id" value="42"')) {
    $failures[] = 'owner_form_carries_post_id';
}

if (!str_contains($ownerMarkup, 'include/update_post.php')) {
    $failures[] = 'owner_edit_targets_update_endpoint';
}

if (!str_contains($ownerMarkup, 'include/delete_post.php')) {
    $failures[] = 'owner_delete_targets_delete_endpoint';
}

if (!str_contains($ownerMarkup, 'name="csrf"')) {
    $failures[] = 'update_form_has_csrf';
}

if (!str_contains($ownerMarkup, 'value="index"')) {
    $failures[] = 'return_to_defaults_to_index';
}

if (str_contains($ownerMarkup, '<b>world</b>')) {
    $failures[] = 'post_content_escaped_in_editor';
}

if (!str_contains($ownerMarkup, '&lt;b&gt;world&lt;/b&gt;')) {
    $failures[] = 'post_content_escaped_value_present';
}

$strangerMarkup = render_post_actions($ownerPost, ['user_id' => 8, 'user' => 'someone_else']);

if ($strangerMarkup !== '') {
    $failures[] = 'non_owner_sees_no_actions';
}

$sessionOnlyMarkup = render_post_actions($ownerPost, ['user' => 'owner_user']);

if (!str_contains($sessionOnlyMarkup, 'data-post-edit-toggle')) {
    $failures[] = 'legacy_session_falls_back_to_username_ownership';
}

$anonymousMarkup = render_post_actions($ownerPost, []);

if ($anonymousMarkup !== '') {
    $failures[] = 'anonymous_sees_no_actions';
}

$_SERVER['PHP_SELF'] = '/profile.php';
$profileMarkup = render_post_actions($ownerPost, ['user_id' => 7, 'user' => 'owner_user']);

if (!str_contains($profileMarkup, 'value="profile"')) {
    $failures[] = 'return_to_profile_on_profile_page';
}

if ($failures !== []) {
    fwrite(STDERR, 'Post actions tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Post actions tests passed\n");
