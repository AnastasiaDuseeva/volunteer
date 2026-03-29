<?php
session_start();
require_once 'config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'ORGANIZER') {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$orgName      = trim($_POST['org_name']      ?? '');
$inn          = trim($_POST['inn']           ?? '');
$kpp          = trim($_POST['kpp']           ?? '');
$legalAddress = trim($_POST['legal_address'] ?? '');
$contactPhone = trim($_POST['contact_phone'] ?? '');
$contactEmail = trim($_POST['contact_email'] ?? '');

// Валидация
if (!$orgName || !$inn || !$legalAddress || !$contactPhone || !$contactEmail) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=empty');
    exit;
}

// Проверяем — есть ли уже профиль
$stmtCheck = $pdo->prepare("SELECT user_id FROM organizer_profiles WHERE user_id = :uid");
$stmtCheck->execute([':uid' => $userId]);

if ($stmtCheck->fetch()) {
    // UPDATE
    $stmt = $pdo->prepare("
        UPDATE organizer_profiles
        SET org_name      = :org_name,
            inn           = :inn,
            kpp           = :kpp,
            legal_address = :legal_address,
            contact_phone = :contact_phone,
            contact_email = :contact_email,
            updated_at    = NOW()
        WHERE user_id = :uid
    ");
} else {
    // INSERT (если профиля ещё нет)
    $stmt = $pdo->prepare("
        INSERT INTO organizer_profiles
            (user_id, org_name, inn, kpp, legal_address, contact_phone, contact_email, updated_at)
        VALUES
            (:uid, :org_name, :inn, :kpp, :legal_address, :contact_phone, :contact_email, NOW())
    ");
}

$stmt->execute([
    ':uid'           => $userId,
    ':org_name'      => $orgName,
    ':inn'           => $inn,
    ':kpp'           => $kpp,
    ':legal_address' => $legalAddress,
    ':contact_phone' => $contactPhone,
    ':contact_email' => $contactEmail,
]);

header('Location: ' . $_SERVER['HTTP_REFERER'] . '?saved=1');
exit;