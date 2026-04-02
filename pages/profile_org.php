<?php
if (!isset($pdo) || !isLoggedIn()) return;

$currentUserId = (int)$_SESSION['user_id'];

// Получаем данные организатора
$stmt = $pdo->prepare("
    SELECT u.id, u.email, op.org_name, op.inn, op.kpp,
           op.legal_address, op.contact_phone, op.contact_email
    FROM users u
    LEFT JOIN organizer_profiles op ON op.user_id = u.id
    WHERE u.id = :uid
    LIMIT 1
");
$stmt->execute([':uid' => $currentUserId]);
$orgProfile = $stmt->fetch();

if (!$orgProfile) return;
?>

<div class="profile-overlay" id="profileOverlay"></div>

<aside class="profile-drawer" id="profileDrawer">
    <button type="button" class="profile-drawer-close" id="profileMenuClose" aria-label="Закрыть меню">×</button>

    <div class="profile-drawer-user-info">
        <h2 class="profile-drawer-name"><?= htmlspecialchars($orgProfile['org_name'] ?? 'Организация') ?></h2>
        <div class="profile-drawer-details">
            <p>ИНН: <?= htmlspecialchars($orgProfile['inn'] ?? '—') ?></p>
            <p><?= htmlspecialchars($orgProfile['contact_phone'] ?? '—') ?></p>
            <p><?= htmlspecialchars($orgProfile['contact_email'] ?? $orgProfile['email']) ?></p>
            <p>ID: <?= (int)$orgProfile['id'] ?></p>
        </div>
    </div>

    <nav class="profile-drawer-nav">

        <button type="button" class="profile-drawer-link" onclick="openOrgEditModal()">
            <img src="../img/profile/icon_settings.png" alt="">Настройки
        </button>
        <a href="logout.php" class="profile-drawer-back">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Выйти из профиля
        </a>
    </nav>
</aside>

<!-- ══ МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ ══ -->
<div id="editOrgModal" class="modal" style="display:none">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Редактировать профиль организации</h3>
            <span class="close-modal" onclick="closeOrgEditModal()">&times;</span>
        </div>
        <form id="editOrgForm" method="POST" action="save_org_profile.php">
            <div class="form-group">
                <label>Название организации *</label>
                <input type="text" name="org_name"
                       value="<?= htmlspecialchars($orgProfile['org_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>ИНН *</label>
                <input type="text" name="inn" maxlength="12"
                       value="<?= htmlspecialchars($orgProfile['inn'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>КПП</label>
                <input type="text" name="kpp" maxlength="9"
                       value="<?= htmlspecialchars($orgProfile['kpp'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Юридический адрес *</label>
                <input type="text" name="legal_address"
                       value="<?= htmlspecialchars($orgProfile['legal_address'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Контактный телефон *</label>
                <input type="tel" name="contact_phone"
                       value="<?= htmlspecialchars($orgProfile['contact_phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email для связи *</label>
                <input type="email" name="contact_email"
                       value="<?= htmlspecialchars($orgProfile['contact_email'] ?? $orgProfile['email']) ?>" required>
            </div>
            <button type="submit" class="submit-btn">Сохранить изменения</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn  = document.getElementById('profileMenuOpen');
    const closeBtn = document.getElementById('profileMenuClose');
    const drawer   = document.getElementById('profileDrawer');
    const overlay  = document.getElementById('profileOverlay');

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

function openOrgEditModal() {
    document.getElementById('editOrgModal').style.display = 'flex';
}

function closeOrgEditModal() {
    document.getElementById('editOrgModal').style.display = 'none';
}

// Закрытие по клику вне модалки
document.addEventListener('click', function(e) {
    const modal = document.getElementById('editOrgModal');
    if (e.target === modal) closeOrgEditModal();
});
</script>