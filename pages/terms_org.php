<?php
session_start();
require_once 'config.php';
requireRole('ORGANIZER');
$userId = (int)$_SESSION['user_id'];
include 'profile_org.php';

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
    
    <link rel="stylesheet" href="../css/style_index.css">
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="stylesheet" href="../css/style_policy.css">
    <link rel="stylesheet" href="../css/profile_org.css">

    <link rel="icon" type="image/png" href="../img/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../img/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../img/favicon/site.webmanifest" />
</head>

<body>

<!-- HEADER -->
<header>
    <a href="../pages/index_org.php" class="logo">
        <div class="logo-icon" >
                <img src="../img/log_main.png" alt="Login" width="47" height="47">
        </div>
        <span class="logo-text">Поможем
вместе</span>
    </a>
            <nav class="button-header">
                <a href="../pages/events_org.php">Мероприятия</a>
                <a href="../pages/list_val_org.php">Волонтеры</a>
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
                    <a href="login.php" class="btn-login"> 
                        <img src="../img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
</header>

<div class="container policy-page">
    <h1>Политика использования сайта</h1>

    <h2>1. Общие положения</h2>
    <p>Настоящая Политика использования сайта (далее — «Политика») регулирует отношения между ООО «Поможем вместе» (далее — «Администрация», «Мы») и любым лицом, использующим сайт https://pomozhem-vmeste.ru (далее — «Пользователь», «Вы»).</p>

    <p>Используя сайт, Пользователь подтверждает, что ознакомился с настоящей Политикой и принимает её условия в полном объёме.</p>

    <h2>2. Права и обязанности Пользователя</h2>
    <p>2.1. Пользователь обязуется использовать сайт только в законных целях и не нарушать права третьих лиц.</p>
    <p>2.2. Пользователю запрещается:</p>
    <ul>
        <li>размещать информацию, которая нарушает законодательство РФ, содержит угрозы, оскорбления, порнографию или пропаганду насилия;</li>
        <li>распространять спам, вирусы, троянские программы;</li>
        <li>пытаться получить несанкционированный доступ к данным других пользователей или серверам сайта;</li>
        <li>использовать сайт для проведения массовых рассылок без согласия Администрации.</li>
    </ul>

    <h2>3. Права и обязанности Администрации</h2>
    <p>3.1. Администрация имеет право в любое время изменять содержание сайта, условия настоящей Политики и других документов.</p>
    <p>3.2. Администрация оставляет за собой право без предупреждения удалять материалы, нарушающие настоящую Политику.</p>
    <p>3.3. Администрация не несёт ответственности за действия пользователей, совершённые с использованием сайта.</p>

    <h2>4. Интеллектуальная собственность</h2>
    <p>Все материалы, размещённые на сайте (тексты, изображения, логотипы, дизайн), являются интеллектуальной собственностью ООО «Поможем вместе» или третьих лиц. Использование материалов без письменного разрешения запрещено.</p>

    <h2>5. Заключительные положения</h2>
    <p>5.1. Настоящая Политика вступает в силу с момента её опубликования на сайте и действует бессрочно.</p>
    <p>5.2. Все споры и разногласия решаются путём переговоров, а при недостижении согласия — в судебном порядке в соответствии с законодательством Российской Федерации.</p>
    <p>5.3. Актуальная версия Политики размещена по адресу: 
        <strong>https://pomozhem-vmeste.ru/pages/terms.php</strong>
    </p>
</div>

<!-- FOOTER -->
<footer>
    <a href="../pages/privacy_org.php">Политика конфиденциальности</a>
    <a href="../pages/terms_org.php">Политика использования</a>
    <a href="../pages/requisites_org.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>

</body>
</html>