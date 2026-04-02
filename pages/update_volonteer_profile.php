<?php
session_start();
require_once 'config.php';


if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

$redirectUrl = trim($_POST['redirect_url'] ?? '/pages/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

$lastName   = trim($_POST['last_name'] ?? '');
$firstName  = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$birthDate  = trim($_POST['birth_date'] ?? '');
$city       = trim($_POST['city'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$email      = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['profile_error'] = 'Введите корректный email.';
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $pdo->beginTransaction();

    $sqlUpdateUser = "
        UPDATE users
        SET email = :email
        WHERE id = :user_id
    ";
    $stmtUpdateUser = $pdo->prepare($sqlUpdateUser);
    $stmtUpdateUser->execute([
        ':email' => $email,
        ':user_id' => $currentUserId
    ]);

    $sqlUpdateProfile = "
        UPDATE volunteer_profiles
        SET
            last_name = :last_name,
            first_name = :first_name,
            middle_name = :middle_name,
            birth_date = :birth_date,
            city = :city,
            phone = :phone,
            email_public = :email_public
        WHERE user_id = :user_id
    ";
    $stmtUpdateProfile = $pdo->prepare($sqlUpdateProfile);
    $stmtUpdateProfile->execute([
        ':last_name' => $lastName !== '' ? $lastName : null,
        ':first_name' => $firstName !== '' ? $firstName : null,
        ':middle_name' => $middleName !== '' ? $middleName : null,
        ':birth_date' => $birthDate !== '' ? $birthDate : null,
        ':city' => $city !== '' ? $city : null,
        ':phone' => $phone !== '' ? $phone : null,
        ':email_public' => $email !== '' ? $email  : null,
        ':user_id' => $currentUserId

    ]);

    $pdo->commit();

    $_SESSION['profile_success'] = 'Профиль успешно обновлен.';
} catch (PDOException $e) {
    $pdo->rollBack();
    // Если в БД email уникальный, и пользователь ввел уже занятый email,
    // ошибка попадет сюда
    $_SESSION['profile_error'] = 'Не удалось сохранить профиль. Возможно, такой email уже занят.';
}

header('Location: ' . $redirectUrl);
exit;
