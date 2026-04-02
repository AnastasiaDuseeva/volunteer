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

<div class="profile-overlay" id="profileOverlay">

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
        <a href="../pages/my_event.php" class="profile-drawer-link"><img src="../img/profile/icon_my_event.png" alt="мероприятие">Мои мероприятия и смены</a>
        <button type="button" class="profile-drawer-link profile-drawer-link-button" id="openSettingsModal">
            <img src="../img/profile/icon_settings.png" alt="настройки">
            Настройки
        </button>
        <a href="../pages/logout.php" class="profile-drawer-back">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg> 
            Выйти из профиля
        </a>  
    </nav>
</aside>
</div>

<!-- МОДАЛЬНОЕ ОКНО НАСТРОЕК -->
<div class="settings-modal-overlay" id="settingsModalOverlay"></div>

<div class="settings-modal" id="settingsModal">
    <div class="settings-modal-header">
        <h3>Редактирование профиля</h3>
        <button type="button" class="settings-modal-close" id="closeSettingsModal" aria-label="Закрыть">
            ×
        </button>
    </div>

    <form action="../pages/update_volonteer_profile.php" method="POST" class="settings-form">
    <!--Скрытое поле, которое определяет с какой странице была открыта форма-->
    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
        <div class="settings-form-group">
            <label for="last_name">Фамилия</label>
            <input 
                type="text" 
                id="last_name" 
                name="last_name"
                value="<?php echo htmlspecialchars($profileUser['last_name'] ?? ''); ?>"
                maxlength="100"
            >
        </div>

        <div class="settings-form-group">
            <label for="first_name">Имя</label>
            <input 
                type="text" 
                id="first_name" 
                name="first_name"
                value="<?php echo htmlspecialchars($profileUser['first_name'] ?? ''); ?>"
                maxlength="100"
            >
        </div>

        <div class="settings-form-group">
            <label for="middle_name">Отчество</label>
            <input 
                type="text" 
                id="middle_name" 
                name="middle_name"
                value="<?php echo htmlspecialchars($profileUser['middle_name'] ?? ''); ?>"
                maxlength="100"
            >
        </div>

        <div class="settings-form-group">
            <label for="birth_date">Дата рождения</label>
            <input 
                type="date" 
                id="birth_date" 
                name="birth_date"
                value="<?php echo htmlspecialchars($profileUser['birth_date'] ?? ''); ?>"
            >
        </div>

        <div class="settings-form-group">
            <label for="city">Город</label>
            <input 
                type="text" 
                id="city" 
                name="city"
                value="<?php echo htmlspecialchars($profileUser['city'] ?? ''); ?>"
                maxlength="100"
            >
        </div>

        <div class="settings-form-group">
            <label for="phone">Телефон</label>
            <input 
                type="text" 
                id="phone" 
                name="phone"
                value="<?php echo htmlspecialchars($profileUser['phone'] ?? ''); ?>"
                maxlength="30"
            >
        </div>

        <div class="settings-form-group">
            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email"
                value="<?php echo htmlspecialchars($profileUser['email'] ?? ''); ?>"
                maxlength="255"
                required
            >
        </div>

        <div class="settings-form-actions">
            <button type="button" class="settings-cancel-btn" id="cancelSettingsModal">Отмена</button>
            <button type="submit" class="settings-save-btn">Сохранить</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('profileMenuOpen');
    const closeBtn = document.getElementById('profileMenuClose');
    const drawer = document.getElementById('profileDrawer');
    const overlay = document.getElementById('profileOverlay');

    const openSettingsBtn = document.getElementById('openSettingsModal');
    const closeSettingsBtn = document.getElementById('closeSettingsModal');
    const cancelSettingsBtn = document.getElementById('cancelSettingsModal');
    const settingsModal = document.getElementById('settingsModal');
    const settingsModalOverlay = document.getElementById('settingsModalOverlay');

    if (openBtn && closeBtn && drawer && overlay) {
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
    }

    function openSettingsModal() {
        settingsModal.classList.add('open');
        settingsModalOverlay.classList.add('open');
    }

    function closeSettingsModalFunc() {
        settingsModal.classList.remove('open');
        settingsModalOverlay.classList.remove('open');
    }

    if (openSettingsBtn && settingsModal && settingsModalOverlay) {
        openSettingsBtn.addEventListener('click', function () {
            openSettingsModal();
        });
    }

    if (closeSettingsBtn) {
        closeSettingsBtn.addEventListener('click', closeSettingsModalFunc);
    }

    if (cancelSettingsBtn) {
        cancelSettingsBtn.addEventListener('click', closeSettingsModalFunc);
    }

    if (settingsModalOverlay) {
        settingsModalOverlay.addEventListener('click', closeSettingsModalFunc);
    }
});
</script>