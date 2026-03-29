<?php
session_start();
require_once 'config.php';

// Если уже авторизован - редирект
if (isLoggedIn()) {
    switch ($_SESSION['user_role']) {
        case 'ADMIN':
            header('Location: ../pages/admin/index.php');
            break;
        case 'ORGANIZER':
            header('Location: ../pages/organizater.php');
            break;
        case 'VOLUNTEER':
        default:
            header('Location: ../pages/events.php');
            break;
    }
    exit;
}

$error = '';

// Проверяем, был ли автовыход по таймауту
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Ваша сессия истекла. Пожалуйста, войдите снова.';
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email и пароль обязательны для заполнения';
    } else {
        try {
            // Получаем пользователя и его роль
            $stmt = $pdo->prepare("
                SELECT id, email, password_hash, role, is_active 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Проверяем, активен ли аккаунт
                if ($user['is_active'] != 1) {
                    $error = 'Ваш аккаунт заблокирован. Обратитесь к администратору.';
                } else {
                    // Получаем имя в зависимости от роли
                    $user_name = '';
                    
                    if ($user['role'] === 'VOLUNTEER') {
                        $stmt = $pdo->prepare("
                            SELECT first_name, last_name 
                            FROM volunteer_profiles 
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$user['id']]);
                        $profile = $stmt->fetch();
                        
                        if ($profile) {
                            $user_name = $profile['first_name'] . ' ' . $profile['last_name'];
                        }
                    } elseif ($user['role'] === 'ORGANIZER') {
                        $stmt = $pdo->prepare("
                            SELECT org_name 
                            FROM organizer_profiles 
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$user['id']]);
                        $profile = $stmt->fetch();
                        
                        if ($profile) {
                            $user_name = $profile['org_name'];
                        }
                    } else {
                        $user_name = 'Администратор';
                    }

                    session_regenerate_id(true);

                    // Сохраняем данные в сессию
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user_name;
                    $_SESSION['last_activity'] = time();
                    
                    // Обновляем время последнего входа
                    $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Редирект в зависимости от роли
                    switch ($user['role']) {
                        case 'ADMIN':
                            header('Location: index.php');
                            break;
                        case 'ORGANIZER':
                            header('Location: org/organizater.php');
                            break;
                        case 'VOLUNTEER':
                        default:
                            header('Location: index.php');
                            break;
                    }
                    exit;
                }
            } else {
                $error = 'Неверный email или пароль';
            }
            
        } catch(PDOException $e) {
            $error = 'Ошибка авторизации. Попробуйте позже.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поможем вместе - Вход</title>
    <link rel="stylesheet" href="../css/style_login.css">
    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
    <link rel="shortcut icon" href="favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
    <link rel="manifest" href="favicon/site.webmanifest" />
</head>
<body>

    <div class="login-container">
        <div class="logo-wrapper">
            <a href="index.php" class="back">
                <img src="../img/log_main.png" alt="Login" width="70" height="70">
            </a>
            <h1 class="title">Поможем<br>вместе</h1> 

        </div>

        <div class="form-wrapper">
            <h2 class="form-title">ВХОД</h2>
                            
            <?php if ($error): ?>
                <div class="error-message" style="color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 5px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" class="login-form">
                <div class="input-group">
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Введите электронную почту" 
                        required 
                        autocomplete="email"
                    >
                </div>

                <div class="input-group">
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Введите пароль" 
                        required 
                        autocomplete="current-password"
                    >
                </div>

                <div class="forgot-register">
                    <a href="registration.php" class="register-link">
                        Ещё нет аккаунта? <br> Зарегистрироваться волонтером
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    Войти
                </button>
            </form>
        </div>
    </div>

</body>
</html>