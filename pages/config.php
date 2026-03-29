<?php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'volunteer_db');
define('DB_USER', 'root'); // Замените на ваш логин
define('DB_PASS', '');     // Замените на ваш пароль

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для проверки роли
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Админ имеет доступ ко всему
    if ($user_role === 'ADMIN') {
        return true;
    }
    
    return $user_role === $required_role;
}

// Функция для редиректа неавторизованных пользователей
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Функция для редиректа при отсутствии прав
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        http_response_code(403);
        die('Доступ запрещён. У вас нет прав для просмотра этой страницы.');
    }
}

// Функция проверки активности и автовыхода
function checkSessionTimeout() {
    $timeout = 60; // 30 минут в секундах (можно изменить: 900=15мин, 3600=1час)
    
    if (isLoggedIn()) {
        // Проверяем время последней активности
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            
            if ($elapsed > $timeout) {
                // Время истекло — уничтожаем сессию
                session_unset();
                session_destroy();
                header('Location: login.php?timeout=1');
                exit;
            }
        }
        
        // Обновляем время последней активности
        $_SESSION['last_activity'] = time();
    }
}
?>