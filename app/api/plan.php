<?php
/**
 * API: build a safety plan with the user's support contact
 */
api_guard('plan', 30, 3600);

$uid = (int) Auth::id();
$user = Auth::user();

$name  = trim((string) post('contact_name', ''));
$phone = trim((string) post('contact_phone', ''));
$email = trim((string) post('contact_email', ''));

if ($name !== '') {
    DB::exec('UPDATE users SET support_contact_name = ?, support_contact_phone = ?, support_contact_email = ? WHERE id = ?', [
        mb_substr($name, 0, 110),
        mb_substr($phone, 0, 55),
        mb_substr($email, 0, 180),
        $uid,
    ]);
    $user['support_contact_name'] = $name;
    $user['support_contact_phone'] = $phone;
    $user['support_contact_email'] = $email;
}

$plan = Crisis::safetyPlan($user);

json_out(['ok' => true, 'plan' => $plan]);
