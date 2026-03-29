<?php
session_start();
require_once 'config.php';

// Проверка авторизации
$isLoggedIn = isLoggedIn();

$currentUserId = (int)$_SESSION['user_id'];

$sqlProfile = "
    SELECT 
        u.id,
        u.email,
        u.role,
        vp.last_name,
        vp.first_name,
        vp.middle_name,
        vp.birth_date,
        vp.city,
        vp.phone
    FROM users u
    LEFT JOIN volunteer_profiles vp ON vp.user_id = u.id
    WHERE u.id = :user_id
    LIMIT 1
";
$stmtProfile = $pdo->prepare($sqlProfile);
$stmtProfile->execute([':user_id' => $currentUserId]);
$profileUser = $stmtProfile->fetch();

if (!$profileUser) {
    return;
}

$fullName = trim(
    ($profileUser['last_name'] ?? '') . ' ' .
    ($profileUser['first_name'] ?? '') . ' ' .
    ($profileUser['middle_name'] ?? '')
);

$birthDate = 'Дата не указана';
if (!empty($profileUser['birth_date'])) {
    $birthDate = date('d.m.Y', strtotime($profileUser['birth_date']));
}

$city = !empty($profileUser['city']) ? $profileUser['city'] : 'Город не указан';
$phone = !empty($profileUser['phone']) ? $profileUser['phone'] : 'Телефон не указан';
$email = !empty($profileUser['email']) ? $profileUser['email'] : 'Email не указан';
$userIdText = 'Ваш ID: ' . (int)$profileUser['id'];
?>

<div class="profile-overlay" id="profileOverlay"></div>

<aside class="profile-drawer" id="profileDrawer">
    <button type="button" class="profile-drawer-close" id="profileMenuClose" aria-label="Закрыть меню">
        ×
    </button>

    <div class="profile-drawer-user-info">
        <h2 class="profile-drawer-name"><?php echo htmlspecialchars($fullName); ?></h2>
        <div class="profile-drawer-details">
            <p><?php echo htmlspecialchars($birthDate); ?></p>
            <p>г. <?php echo htmlspecialchars($city); ?></p>
            <p><?php echo htmlspecialchars($phone); ?></p>
            <p><?php echo htmlspecialchars($email); ?></p>
            <p><?php echo htmlspecialchars($userIdText); ?></p>
        </div>
    </div>

    <nav class="profile-drawer-nav">
        <a href="my_events.php" class="profile-drawer-link"><img src="../img/profile/icon_my_event.png" alt="мероприятие">Мои мероприятия</a>
        <a href="history.php" class="profile-drawer-link"><img src="../img/profile/icon_history_event.png" alt="история">История мероприятий</a>
        <a href="settings.php" class="profile-drawer-link"><img src="../img/profile/icon_settings.png" alt="настройки">Настройки</a>
        <a href="logout.php" class="profile-drawer-back">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg> 
            Выйти из профиля
        </a>  
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('profileMenuOpen');
    const closeBtn = document.getElementById('profileMenuClose');
    const drawer = document.getElementById('profileDrawer');
    const overlay = document.getElementById('profileOverlay');

    if (!openBtn || !closeBtn || !drawer || !overlay) return;

    openBtn.addEventListener('click', function () {
        drawer.classList.add('open');
        overlay.classList.add('open');
    });

    function closeMenu() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
    }

    closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
});
</script>