<?php
require_once 'auth_check.php';
$db = getDB();

// Все счётчики — используем users.id (не user_id!)
$stats['events']      = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$stats['volunteers']  = $db->query("SELECT COUNT(*) FROM users WHERE role = 'VOLUNTEER'")->fetchColumn();
$stats['organizers']  = $db->query("SELECT COUNT(*) FROM users WHERE role = 'ORGANIZER'")->fetchColumn();
$stats['open_events'] = $db->query("SELECT COUNT(*) FROM events WHERE recruitment_status = 'Открыт'")->fetchColumn();
?>
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
        <img src="../assets/img/log_main.png" alt="Логотип">
        <span class="admin-logo-text">Поможем<br>вместе</span>
    </a>
    <span class="admin-badge">Администратор</span>
    <span class="admin-header-name"><?= htmlspecialchars($admin['email']) ?></span>
    <div class="admin-header-right">
        <a href="../logout.php" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    <div style="display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;">
        <div style="background:#fff;border:1px solid #E2E4E8;border-radius:12px;padding:14px 22px;display:flex;flex-direction:column;gap:2px;">
            <span style="font-size:26px;font-weight:700;color:#4A7FC1;"><?= $stats['events'] ?></span>
            <span style="font-size:13px;color:#888;">всего мероприятий</span>
        </div>
        <div style="background:#fff;border:1px solid #E2E4E8;border-radius:12px;padding:14px 22px;display:flex;flex-direction:column;gap:2px;">
            <span style="font-size:26px;font-weight:700;color:#3DAA6A;"><?= $stats['open_events'] ?></span>
            <span style="font-size:13px;color:#888;">открытых наборов</span>
        </div>
        <div style="background:#fff;border:1px solid #E2E4E8;border-radius:12px;padding:14px 22px;display:flex;flex-direction:column;gap:2px;">
            <span style="font-size:26px;font-weight:700;color:#4A7FC1;"><?= $stats['volunteers'] ?></span>
            <span style="font-size:13px;color:#888;">волонтёров</span>
        </div>
        <div style="background:#fff;border:1px solid #E2E4E8;border-radius:12px;padding:14px 22px;display:flex;flex-direction:column;gap:2px;">
            <span style="font-size:26px;font-weight:700;color:#4A7FC1;"><?= $stats['organizers'] ?></span>
            <span style="font-size:13px;color:#888;">организаторов</span>
        </div>
    </div>

    <div class="admin-sections">

        <div class="admin-section">
            <h2 class="section-title">Мероприятия</h2>
            <a href="events.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                Просмотр всех мероприятий
            </a>
            <a href="event_create.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
                Создать мероприятие
            </a>
            <a href="shift_create.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
                </span>
                Создать смену
            </a>
        </div>

        <div class="admin-section">
            <h2 class="section-title">Волонтёры</h2>
            <a href="volunteers.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                Просмотреть список волонтёров
            </a>
        </div>

        <div class="admin-section">
            <h2 class="section-title">Организаторы</h2>
            <a href="organizers.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </span>
                Просмотреть всех организаторов
            </a>
            <a href="organizer_add.php" class="section-btn">
                <span class="btn-icon-wrap">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </span>
                Добавить организатора
            </a>
        </div>

    </div>
</main>

<footer class="admin-footer">
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="mailto:info@pomozhem.ru">info@pomozhem.ru</a>
</footer>

</body>
</html>
