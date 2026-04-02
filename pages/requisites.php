<?php
// requisites.php — Реквизиты организации

$isLoggedIn = $isLoggedIn ?? false;
$userName   = $userName   ?? '';

$pageTitle = 'Реквизиты организации';
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

    <link rel="icon" type="image/png" href="../assets/img/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../assets/img/favicon/site.webmanifest" />
</head>

<body>

<!-- HEADER -->
<header>
    <a href="../index.php" class="logo">
        <div class="logo-icon">
            <img src="../assets/img/log_main.png" alt="Поможем вместе" width="47" height="47">
        </div>
        <span class="logo-text">Поможем<br>вместе</span>
    </a>

    <nav>
        <a href="../pages/events.php">Мероприятия</a>
        <a href="../pages/volunteers.php">Волонтеры</a>
    </nav>

    <div class="header-actions">
        <button class="btn-icon" title="Поиск" onclick="window.location.href='../search.php'">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </button>

        <?php if ($isLoggedIn): ?>
            <a href="../pages/events.php" class="btn-login">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <?= htmlspecialchars($userName) ?>
            </a>
        <?php else: ?>
            <a href="../login.php" class="btn-login">
                <img src="../assets/img/log_main.png" alt="Войти" width="20" height="20">
                Войти
            </a>
        <?php endif; ?>
    </div>
</header>

<div class="container policy-page requisites-page">
    <h1>Реквизиты организации</h1>

    <div class="requisites-block">
        <h2>Общество с ограниченной ответственностью «Поможем вместе»</h2>
        
        <table class="policy-table">
            <tr><th>Полное наименование</th><td>Общество с ограниченной ответственностью «Поможем вместе»</td></tr>
            <tr><th>Сокращённое наименование</th><td>ООО «Поможем вместе»</td></tr>
            <tr><th>ИНН</th><td>XXXXXXXXXXXX</td></tr>
            <tr><th>КПП</th><td>XXXXXXXXXXX</td></tr>
            <tr><th>ОГРН</th><td>XXXXXXXXXXXXXXX</td></tr>
            <tr><th>Юридический адрес</th><td>г. Москва, ул. Примерная, д. 10, офис 5</td></tr>
            <tr><th>Почтовый адрес</th><td>г. Москва, 123456, а/я 123</td></tr>
            <tr><th>Расчётный счёт</th><td>40702810XXXXXXXXXXXXXX</td></tr>
            <tr><th>Банк</th><td>ПАО Сбербанк</td></tr>
            <tr><th>БИК</th><td>044525225</td></tr>
            <tr><th>Корреспондентский счёт</th><td>30101810400000000225</td></tr>
            <tr><th>Телефон</th><td>+7 (XXX) XXX-XX-XX</td></tr>
            <tr><th>E-mail</th><td>info@pomozhem-vmeste.ru</td></tr>
        </table>

        <p class="mt-4"><strong>Генеральный директор:</strong> Иванов Иван Иванович</p>
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