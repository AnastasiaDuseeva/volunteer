<?php
// Подключаем конфиг — он лежит в корне, на уровень выше папки admin/
require_once __DIR__ . '/../config.php';

// Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ВРЕМЕННАЯ ЗАГЛУШКА — удалить когда login.php заработает!
//$_SESSION['user_id']       = 1;
//$_SESSION['user_name']     = 'Иванов Иван Иванович';
//$_SESSION['user_email']    = 'admin@volunteer.local';
//$_SESSION['user_role']     = 'ADMIN';
//$_SESSION['last_activity'] = time();
// КОНЕЦ ЗАГЛУШКИ

// Проверяем что пользователь залогинен и является администратором
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['user_role'] ?? '') !== 'ADMIN') {
    header('Location: ../index.php');
    exit;
}

// Собираем данные администратора в удобный массив
// Все страницы админки используют $admin['name'], $admin['email'] и т.д.
$admin = [
    'id'    => $_SESSION['user_id'],
    'name'  => $_SESSION['user_name']  ?? 'Администратор',
    'email' => $_SESSION['user_email'] ?? '',
    'role'  => $_SESSION['user_role']  ?? '',
];