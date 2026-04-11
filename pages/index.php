<?php

session_start();
require_once 'config.php';
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
    <title>Поможем вместе - Платформа добрых дел</title>
    <link rel="stylesheet" href="../css/style_index.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="icon" type="image/png" href="../favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
    <link rel="shortcut icon" href="../favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../favicon/site.webmanifest" />
    
</head>

<body>

<!-- ═══════════ HEADER ═══════════ -->
<header>
    <a href="../index.php" class="logo">
        <div class="logo-icon" >
                <img src="../img/log_main.png" alt="Login" width="47" height="47">
        </div>
        <span class="logo-text">Поможем<br>вместе</span>
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
                    <a href="../pages/login.php" class="btn-login"> 
                        <img src="../img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
</header>

<!-- ═══════════ MAIN ═══════════ -->
<main>
    <section class="hero">
        <div class="hero-content">
            <h1>ПЛАТФОРМА<br>ДОБРЫХ ДЕЛ</h1>
            <p>внеси свой вклад в общее дело</p>
            <div class="hero-btns">
                <a href="../pages/register-organizer.php" class="btn-outline">Стать организатором</a>
                <a href="../pages/volunteers.php" class="btn-filled">Хочу помочь</a>
            </div>
        </div>

        <div class="hero-image">
            <div class="slider">
                <div class="slide active">
                    <img src="../img/index-lenta.jpg" alt="Море">
                </div>
                <div class="slide">
                    <img src="../img/index-lenta2.jpg" alt="Ветераны">
                </div>
                <div class="slide">
                    <img src="../img/index-lenta3.jpg" alt="Приют">
                </div>
            </div>

            <div class="hero-image-dots">
                <div class="dot active" onclick="goToSlide(0)"></div>
                <div class="dot" onclick="goToSlide(1)"></div>
                <div class="dot" onclick="goToSlide(2)"></div>
            </div>
        </div>
    </section>
</main>

<!-- ═══════════ FOOTER ═══════════ -->
<footer>
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="../pages/requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>
<script>
    let current = 0;
    const slides = document.querySelectorAll('.slide');
    const dots   = document.querySelectorAll('.dot');

    function goToSlide(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = n;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    // Автопереключение каждые 4 секунды
    setInterval(() => {
        goToSlide((current + 1) % slides.length);
    }, 4000);
</script>
</body>
</html>