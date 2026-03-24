<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора — Поможем вместе</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <img src="../assets/img/icon/log_main.png" alt="Логотип">
        <span class="admin-logo-text">Поможем<br>вместе</span>
    </a>

    <span class="admin-badge">Администратор</span>

    <!-- Имя администратора. сюда PHP:
        <?= htmlspecialchars($_SESSION['user']['name']) ?>
    -->
    <span class="admin-header-name">Иванов Иван Иванович</span>

     <div class="admin-header-right">
        <a href="../logout.php" class="btn-logout">
            <!-- Иконка «выход» -->
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Выйти
        </a>
    </div>
</header>

<main class="admin-main">

    <h1 class="page-heading">Управление системой</h1>
    <div class="admin-sections">

        <!-- СЕКЦИЯ 1: МЕРОПРИЯТИЯ  -->
        <div class="admin-section">
            <h2 class="section-title">Мероприятия</h2>
            <a href="events.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «список» (три горизонтальные линии) -->
                    <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                </span>
                Просмотр всех мероприятий
            </a>

            <a href="event_create.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «плюс» -->
                    <svg viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </span>
                Создать мероприятие
            </a>

            <a href="shift_create.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «календарь с плюсом» -->
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <line x1="12" y1="14" x2="12" y2="18"/>
                        <line x1="10" y1="16" x2="14" y2="16"/>
                    </svg>
                </span>
                Создать смену
            </a>
        </div><!-- /admin-section Мероприятия -->


        <!-- СЕКЦИЯ 2: ВОЛОНТЁРЫ -->
        <div class="admin-section">
            <h2 class="section-title">Волонтёры</h2>

            <a href="volunteers.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «люди» -->
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                Просмотреть список волонтёров
            </a>
        </div><!-- /admin-section Волонтёры -->


        <!--  СЕКЦИЯ 3: ОРГАНИЗАТОРЫ -->
        <div class="admin-section">
            <h2 class="section-title">Организаторы</h2>

            <a href="organizers.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «здание/организация» -->
                    <svg viewBox="0 0 24 24">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </span>
                Просмотреть всех организаторов
            </a>

            <a href="organizer_add.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <!-- Иконка «добавить человека» -->
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                </span>
                Добавить организатора
            </a>
        </div><!-- /admin-section Организаторы -->

    </div><!-- /admin-sections -->

</main>

<footer class="admin-footer">
    <a href="../privacy.php">Политика конфиденциальности</a>
    <a href="../terms.php">Политика использования</a>
    <a href="mailto:info@pomozhem.ru">info@pomozhem.ru</a>
</footer>

</body>
</html>