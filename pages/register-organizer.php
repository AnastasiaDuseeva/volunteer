<?php
// become-organizer.php
$isLoggedIn = $isLoggedIn ?? false;
$userName   = $userName   ?? '';

$pageTitle = 'Стать организатором';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Поможем вместе</title>
    
    <link rel="stylesheet" href="../assets/css/style_index.css">
    <link rel="stylesheet" href="../assets/css/style_header_footer.css">
    <link rel="stylesheet" href="../assets/css/style_policy.css">   <!-- используем общий + добавим свои стили -->
    <link rel="stylesheet" href="../assets/css/style_organizer.css"> <!-- новый файл для этих страниц -->

    <link rel="icon" type="image/png" href="../assets/img/favicon/favicon-96x96.png" sizes="96x96" />
</head>

<body>

<!-- HEADER -->
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
                <a href="../pages/volunteers.php">Волонтеры</a>
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
                        <img src="../assets/img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
</header>

<div class="container organizer-page">
    <h1>Стать организатором</h1>
    
    <p class="intro-text">
        Мы рады сотрудничеству с активными людьми и организациями, которые хотят проводить добрые дела и помогать тем, кто в этом нуждается.
    </p>

    <h2>Направления, в которых мы работаем</h2>
    
    <div class="categories-grid">
        <div class="category-card">Дети и молодёжь</div>
        <div class="category-card">Животные</div>
        <div class="category-card">Культура и искусство</div>
        <div class="category-card">Медицина</div>
        <div class="category-card">Помощь пожилым</div>
        <div class="category-card">Спорт</div>
        <div class="category-card">Экология</div>
        <div class="category-card">Другое</div>
    </div>

    <div class="contact-block">
        <h2>Хотите стать нашим партнёром или организатором?</h2>
        <p>Напишите нам или позвоните — мы подробно расскажем, как начать сотрудничество и провести ваше мероприятие на нашей платформе.</p>
        
        <div class="contact-info">
            <p><strong>Электронная почта:</strong> <a href="mailto:info@pomozhem-vmeste.ru">info@pomozhem-vmeste.ru</a></p>
            <p><strong>Телефон:</strong> <a href="tel:+7XXXXXXXXXX">+7 (XXX) XXX-XX-XX</a></p>
        </div>
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