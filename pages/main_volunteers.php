<?php
$pageTitle = 'Хочу помочь';
session_start();
require_once '../config.php';
include '../pages/profile.php';
// Проверка авторизации
$isLoggedIn = isLoggedIn();
// Имя пользователя (если залогинен)
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Поможем вместе</title>
    
    <link rel="stylesheet" href="../assets/css/style_index.css">
    <link rel="stylesheet" href="../assets/css/style_header_footer.css">
    <link rel="stylesheet" href="../assets/css/style_policy.css">
    <link rel="stylesheet" href="../assets/css/style_organizer.css">
    <link rel="stylesheet" href="../assets/css/profile.css">

    <link rel="icon" type="image/png" href="../assets/img/favicon/favicon-96x96.png" sizes="96x96" />
</head>

<body>

<!-- HEADER (тот же) -->
<header>
    <a href="../index.php" class="logo">
        <div class="logo-icon" >
                <img src="../assets/img/log_main.png" alt="Login" width="47" height="47">
        </div>
        <span class="logo-text">Поможем
вместе</span>
    </a>
            <nav class="button-header">
                <a href="../pages/events.php">Мероприятия</a>
                <a href="../pages/list_of_val.php">Волонтеры</a>
            </nav>

    <div class="header-actions">
        <button class="btn-icon" title="Поиск" onclick="window.location.href='search.php'">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </button>

   <?php if ($isLoggedIn): ?>
                    <button type="button" class="btn-login" id="profileMenuOpen">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?= $userName ?>
                    </button> 
                <?php else: ?>
                    <a href="/login.php" class="btn-login"> 
                        <img src="../assets/img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
</header>

<div class="container help-page">
    <h1>Хочу помочь</h1>
    
    <div class="help-content">
        <p class="big-text">
            Каждое доброе дело начинается с желания помочь. Даже небольшая поддержка может изменить чью-то жизнь к лучшему.
        </p>

        <h2>Почему важно помогать?</h2>
        <p>Благотворительность — это не только финансовая помощь. Это проявление человечности, солидарности и заботы о тех, кто оказался в трудной ситуации. Помогая другим, мы делаем мир добрее и гармоничнее.</p>

        <h2>Как вы можете помочь нам?</h2>
        <p>Ваши пожертвования идут на организацию мероприятий, помощь подопечным, закупку необходимых вещей, корм для животных, медикаменты и многое другое.</p>

        <div class="donate-block">
            <h3>Реквизиты для переводов</h3>
            <table class="policy-table">
                <tr><th>Получатель</th><td>ООО «Поможем вместе»</td></tr>
                <tr><th>ИНН</th><td>XXXXXXXXXXXX</td></tr>
                <tr><th>Расчётный счёт</th><td>40702810XXXXXXXXXXXXXX</td></tr>
                <tr><th>Банк</th><td>ПАО Сбербанк</td></tr>
                <tr><th>БИК</th><td>044525225</td></tr>
                <tr><th>Назначение платежа</th><td>Благотворительное пожертвование (НДС не облагается)</td></tr>
            </table>
            
            <p class="note">При переводе обязательно укажите в назначении платежа «Благотворительное пожертвование».</p>
        </div>

        <p>Спасибо, что вы с нами! Вместе мы можем сделать гораздо больше.</p>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="../pages/requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>

</body>
</html>