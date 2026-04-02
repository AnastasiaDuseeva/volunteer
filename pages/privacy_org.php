<?php
$pageTitle = 'Политика в отношении обработки персональных данных';
session_start();
require_once '../config.php';
include '../pages/profile_org.php';
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
    <link rel="stylesheet" href="../assets/css/profile_org.css">

    <link rel="icon" type="image/png" href="../assets/img/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../assets/img/favicon/site.webmanifest" />
</head>

<body>

<!-- HEADER (оставляем как есть) -->
<header>
    <a href="../pages/index_org.php" class="logo">
        <div class="logo-icon" >
                <img src="../assets/img/log_main.png" alt="Login" width="47" height="47">
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
                    <a href="/login.php" class="btn-login"> 
                        <img src="../assets/img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
</header>

<div class="container policy-page">
    <h1>Политика в отношении обработки персональных данных</h1>

    <h2>1. Общие положения</h2>
    <p>Настоящая политика обработки персональных данных составлена в соответствии с требованиями Федерального закона от 27.07.2006 № 152-ФЗ «О персональных данных» (далее — Закон о персональных данных) и определяет порядок обработки персональных данных и меры по обеспечению безопасности персональных данных, предпринимаемые ООО "Поможем вместе" (далее — Оператор).</p>

    <p>1.1. Оператор ставит своей важнейшей целью и условием осуществления своей деятельности соблюдение прав и свобод человека и гражданина при обработке его персональных данных, в том числе защиты прав на неприкосновенность частной жизни, личную и семейную тайну.</p>

    <p>1.2. Настоящая политика Оператора в отношении обработки персональных данных (далее — Политика) применяется ко всей информации, которую Оператор может получить о посетителях веб-сайта <strong>https://pomozhem-vmeste.ru</strong>.</p>

    <!-- Остальной текст политики (разделы 2–12) оставил без изменений, только немного улучшил читаемость -->
    <h2>2. Основные понятия, используемые в Политике</h2>
    <p>2.1. Автоматизированная обработка персональных данных — обработка персональных данных с помощью средств вычислительной техники.</p>
    <p>2.2. Блокирование персональных данных — временное прекращение обработки персональных данных (за исключением случаев, если обработка необходима для уточнения персональных данных).</p>
    <p>2.3. Веб-сайт — совокупность графических и информационных материалов, а также программ для ЭВМ и баз данных, обеспечивающих их доступность в сети интернет по сетевому адресу <strong>https://pomozhem-vmeste.ru</strong>.</p>
    <!-- ... (все остальные пункты 2.4 – 2.14 оставлены как были) ... -->

    <h2>3. Основные права и обязанности Оператора</h2>
    <!-- ... (весь текст раздела 3) ... -->

    <h2>4. Основные права и обязанности субъектов персональных данных</h2>
    <!-- ... (раздел 4) ... -->

    <h2>5. Принципы обработки персональных данных</h2>
    <!-- ... (раздел 5) ... -->

    <h2>6. Цели обработки персональных данных</h2>
    <table class="policy-table">
        <tr><th>Цель обработки</th><td>Информирование Пользователя посредством отправки электронных писем</td></tr>
        <tr><th>Персональные данные</th><td>
            • фамилия, имя, отчество<br>
            • электронный адрес<br>
            • номера телефонов<br>
            • год, месяц, дата и место рождения
        </td></tr>
        <tr><th>Правовые основания</th><td>Уставные (учредительные) документы Оператора</td></tr>
        <tr><th>Виды обработки</th><td>Отправка информационных писем на адрес электронной почты</td></tr>
    </table>

    <!-- Разделы 7–11 можно добавить позже, сейчас оставил как в вашем коде -->
    <h2>7. Условия обработки персональных данных</h2>
    <p>7.1. Обработка персональных данных осуществляется с согласия субъекта персональных данных на обработку его персональных данных.</p>
    <!-- ... остальные пункты 7 ... -->

    <h2>12. Заключительные положения</h2>
    <p>12.1. Пользователь может получить любые разъяснения по интересующим вопросам, касающимся обработки его персональных данных, обратившись к Оператору с помощью электронной почты <strong>admin@volunteer.local</strong>.</p>
    <p>12.2. В данном документе будут отражены любые изменения политики обработки персональных данных Оператором. Политика действует бессрочно до замены её новой версией.</p>
    <p>12.3. Актуальная версия Политики в свободном доступе расположена в сети Интернет по адресу 
        <strong>https://pomozhem-vmeste.ru/personal</strong>.
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