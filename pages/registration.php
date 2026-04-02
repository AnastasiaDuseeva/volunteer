<?php
session_start();
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ../pages/events.php');
    exit;
}

// Обработка формы (если форма отправлена)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';

    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $errors = [];
    
    // валидация
    if (empty($last_name)) {
        $errors[] = 'Фамилия обязательна для заполнения';
    }
    
    if (empty($first_name)) {
        $errors[] = 'Имя обязательно для заполнения';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email';
    }
    
    if (empty($birth_date)) {
        $errors[] = 'Дата рождения обязательна';
    }
    
    if (empty($password)) {
        $errors[] = 'Пароль обязателен';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    // сохраняем
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Пользователь с таким email уже зарегистрирован';
            } else {
                $pdo->beginTransaction();
                
                try {
                    // тут создаем пользователя в таблице users
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (email, password_hash, role, is_active) 
                        VALUES (?, ?, 'VOLUNTEER', 1)
                    ");
                    $stmt->execute([$email, $password_hash]);
                    
                    // ID созданного пользователя
                    $user_id = $pdo->lastInsertId();
                    
                    // тут создаем профиль волонтёра в таблице volunteer_profiles
                    $stmt = $pdo->prepare("
                        INSERT INTO volunteer_profiles 
                        (user_id, last_name, first_name, middle_name, birth_date, city, phone, email_public) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $last_name,
                        $first_name,
                        $middle_name ?: null,
                        $birth_date,
                        $city ?: null,
                        $phone ?: null,
                        $email 
                    ]);
                    
                    $pdo->commit();
                   session_regenerate_id(true);

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'VOLUNTEER';
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['last_activity'] = time();
                    
                    header('Location: volunteers/test.php');
                    exit;
                    
                } catch(PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = 'Ошибка при регистрации: ' . $e->getMessage();
                }
            }
        } catch(PDOException $e) {
            $errors[] = 'Ошибка проверки email: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поможем вместе - Регистрация</title>
    <link rel="stylesheet" href="/css/registration.css">
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
            <h2 class="form-title">РЕГИСТРАЦИЯ</h2>

            <?php if (!empty($errors)): ?>
                <div class="error-message" style="color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 5px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="registration.php" method="post" class="login-form">
                <div class="input-group">
                    <input 
                        type="text" 
                        name="first_name" 
                        placeholder="Введите имя" 
                        required 
                        autocomplete="first_name"
                    >
                </div>
                <div class="input-group">
                    <input 
                        type="text" 
                        name="last_name" 
                        placeholder="Введите фамилию" 
                        required 
                        autocomplete="last-name"
                    >
                </div>
                 <div class="input-group">
                    <input 
                        type="text" 
                        name="middle_name" 
                        placeholder="Введите отчество" 
                        autocomplete="middle-name"
                    >
                </div>
                 <div class="input-group">
                    <input 
                        type="text" 
                        name="city" 
                        placeholder="Введите город" 
                        required 
                        autocomplete="city"
                    >
                </div>
                 <div class="input-group">
                    <input 
                        type="date" 
                        name="birth_date" 
                        placeholder="Введите дату рождения" 
                        required 
                        autocomplete="birth_date"
                    >
                </div>
                <div class="input-group">
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+7 (XXX) XXX-XX-XX" 
                        required 
                        autocomplete="tel"
                        value="+7 "
                    >
                </div>

                <script>
                    const phoneInput = document.getElementById('phone');
                    
                    // Блокируем ввод всего кроме цифр
                    phoneInput.addEventListener('keydown', function(e) {
                        // Разрешаем: цифры, Backspace, Delete, Tab, стрелки, Ctrl+A, Ctrl+C, Ctrl+V
                        const allowedKeys = [
                            'Backspace', 'Delete', 'Tab', 
                            'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                            'Home', 'End'
                        ];
                        
                        const isNumber = (e.key >= '0' && e.key <= '9');
                        const isAllowedKey = allowedKeys.includes(e.key);
                        const isCtrlCmd = e.ctrlKey || e.metaKey; // Ctrl+A, Ctrl+C, Ctrl+V и т.д.
                        
                        phoneInput.addEventListener('keydown', function(e) {
                            const allowedKeys = [
                                'Backspace', 'Delete', 'Tab', 
                                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                                'Home', 'End'
                            ];
                            
                            const isNumber = (e.key >= '0' && e.key <= '9');
                            const isAllowedKey = allowedKeys.includes(e.key);
                            const isCtrlCmd = e.ctrlKey || e.metaKey;

                            // Разрешаем удаление всегда
                            if (e.key === 'Backspace' || e.key === 'Delete') {
                                return;
                            }

                            if (!isNumber && !isAllowedKey && !isCtrlCmd) {
                                e.preventDefault();
                            }
                        });
                        
                        // Блокируем всё кроме цифр и разрешённых клавиш
                        if (!isNumber && !isAllowedKey && !isCtrlCmd) {
                            e.preventDefault();
                        }
                    });
                    
                    // Форматирование при вводе
                    phoneInput.addEventListener('input', function(e) {
                        let value = this.value.replace(/\D/g, '');
                        
                        // Если удалили всё, возвращаем +7
                        if (value.length === 0) {
                            this.value = '+7 ';
                            return;
                        }
                        
                        // Ограничиваем длину (11 цифр: 7 + 10 цифр номера)
                        if (value.length > 11) {
                            value = value.substring(0, 11);
                        }
                        
                        // Форматируем: +7 (XXX) XXX-XX-XX
                        let formatted = '+7';
                        
                        if (value.length > 1) {
                            formatted += ' (' + value.substring(1, 4);
                        }
                        if (value.length >= 4) {
                            formatted += ') ' + value.substring(4, 7);
                        }
                        if (value.length >= 7) {
                            formatted += '-' + value.substring(7, 9);
                        }
                        if (value.length >= 9) {
                            formatted += '-' + value.substring(9, 11);
                        }
                        
                        this.value = formatted;
                    });
                    
                    // Блокируем вставку текста (только цифры)
                    phoneInput.addEventListener('paste', function(e) {
                        e.preventDefault();
                        
                        // Получаем вставляемый текст
                        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                        
                        // Извлекаем только цифры
                        const digits = pastedText.replace(/\D/g, '');
                        
                        if (digits.length > 0) {
                            // Вставляем как будто набрали вручную
                            const currentDigits = this.value.replace(/\D/g, '');
                            const newDigits = currentDigits + digits;
                            
                            // Создаём событие input для форматирования
                            this.value = newDigits;
                            this.dispatchEvent(new Event('input'));
                        }
                    });
                </script>
                    
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
                        id="password"
                        placeholder="Введите пароль" 
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>

                <div class="input-group">
                    <input 
                        type="password" 
                        name="password_confirm" 
                        id="password_confirm"
                        placeholder="Повторите пароль" 
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>
                <span class="error-message" id="error" style="color: red; font-size: 12px;margin-top: -3%; display: none;">
                Пароли не совпадают
                </span>
               
            

                <button type="submit" class="btn-login">
                    Зарегистрироваться
                </button>
            </form>

            <script>
                const password = document.getElementById('password');
                const passwordConfirm = document.getElementById('password_confirm');
                const error = document.getElementById('error');

                passwordConfirm.addEventListener('input', function() {
                    if (password.value !== passwordConfirm.value) {
                        error.style.display = 'block';
                        passwordConfirm.setCustomValidity('Пароли не совпадают');
                    } else {
                        error.style.display = 'none';
                        passwordConfirm.setCustomValidity('');
                    }
                });

                password.addEventListener('input', function() {
                    if (passwordConfirm.value && password.value !== passwordConfirm.value) {
                        error.style.display = 'block';
                        passwordConfirm.setCustomValidity('Пароли не совпадают');
                    } else {
                        error.style.display = 'none';
                        passwordConfirm.setCustomValidity('');
                    }
                });
            </script>
        </div>
    </div>

</body>
</html>