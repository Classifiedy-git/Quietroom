<?php
/**
 * API: change the profile picture
 *
 * The picture arrives as a data URL in a urlencoded POST. A file upload
 * would need multipart/form-data, which some shared hosts refuse with a
 * 403 before the request ever reaches PHP.
 */
api_guard('avatar', 20, 3600);

$uid = (int) Auth::id();

$dataUrl = trim((string) post('image', ''));
if ($dataUrl === '') {
    json_error('Please choose an image first.');
}

$result = save_avatar_data_url($uid, $dataUrl);
if (!$result['ok']) {
    json_error($result['error'] !== '' ? $result['error'] : 'Could not upload your profile picture. Please try a different image.', 422);
}

DB::exec('UPDATE users SET avatar = ? WHERE id = ?', [$result['path'], $uid]);

json_out(['ok' => true, 'avatar' => asset_url($result['path'])]);
