<?php
require_once 'auth_check.php';
$db = getDB();

$errors = [];
$form   = ['org_name'=>'','inn'=>'','kpp'=>'','legal_address'=>'','contact_phone'=>'','contact_email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form['org_name']      = trim($_POST['org_name']      ?? '');
    $form['inn']           = trim($_POST['inn']           ?? '');
    $form['kpp']           = trim($_POST['kpp']           ?? '');
    $form['legal_address'] = trim($_POST['legal_address'] ?? '');
    $form['contact_phone'] = trim($_POST['contact_phone'] ?? '');
    $form['contact_email'] = trim($_POST['contact_email'] ?? '');
    $password              = $_POST['password']           ?? '';
    $passwordConfirm       = $_POST['password_confirm']   ?? '';

    // Валидация
    if (empty($form['org_name']))      $errors['org_name']      = 'Введите наименование';
    if (empty($form['inn']))           $errors['inn']           = 'Введите ИНН';
    elseif (!preg_match('/^\d{10}$|^\d{12}$/', $form['inn'])) $errors['inn'] = 'ИНН — 10 или 12 цифр';
    if (empty($form['kpp']))           $errors['kpp']           = 'Введите КПП';
    elseif (!preg_match('/^\d{9}$/', $form['kpp']))            $errors['kpp'] = 'КПП — ровно 9 цифр';
    if (empty($form['legal_address'])) $errors['legal_address'] = 'Введите юридический адрес';
    if (empty($form['contact_phone'])) $errors['contact_phone'] = 'Введите телефон';

    if (empty($form['contact_email'])) {
        $errors['contact_email'] = 'Введите email';
    } elseif (!filter_var($form['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['contact_email'] = 'Некорректный формат email';
    } else {
        // Проверяем уникальность email в таблице users
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$form['contact_email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['contact_email'] = 'Пользователь с таким email уже существует';
        }
    }

    if (empty($password))              $errors['password']         = 'Введите пароль';
    elseif (strlen($password) < 10)    $errors['password']         = 'Минимум 10 символов';
    elseif ($password !== $passwordConfirm) $errors['password_confirm'] = 'Пароли не совпадают';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // 1. Создаём аккаунт в users
            // email организатора хранится в users.email (для входа)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'ORGANIZER')");
            $stmt->execute([$form['contact_email'], $passwordHash]);
            $newUserId = $db->lastInsertId(); // это users.id

            // 2. Создаём профиль в organizer_profiles
            // organizer_profiles.user_id ссылается на users.id
            $stmt = $db->prepare("
                INSERT INTO organizer_profiles (user_id, org_name, inn, kpp, legal_address, contact_phone, contact_email)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $newUserId,
                $form['org_name'],
                $form['inn'],
                $form['kpp'],
                $form['legal_address'],
                $form['contact_phone'],
                $form['contact_email'],
            ]);

            $db->commit();
            header('Location: organizers.php?success=1');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = 'Ошибка при сохранении: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить организатора — Администратор</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <img src="../img/log_main.png" alt="Логотип">
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

    <a href="organizers.php" class="back-link">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Назад к списку организаторов
    </a>

    <?php if (!empty($errors['general'])): ?>
    <div style="background:#FDEAEA;border:1px solid #E05252;border-radius:10px;padding:14px 18px;margin-bottom:20px;color:#c0392b;font-weight:600;">
        <?= htmlspecialchars($errors['general']) ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <h1 class="form-title">Добавить организатора</h1>

        <form action="" method="post">

            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="org_name">Наименование организации <span class="required">*</span></label>
                    <input type="text" id="org_name" name="org_name" class="form-input"
                           placeholder="Полное наименование"
                           value="<?= htmlspecialchars($form['org_name']) ?>" required maxlength="255"
                           style="<?= isset($errors['org_name']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['org_name'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['org_name'] ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="inn">ИНН <span class="required">*</span></label>
                    <input type="text" id="inn" name="inn" class="form-input"
                           placeholder="1234567890"
                           value="<?= htmlspecialchars($form['inn']) ?>" required maxlength="12"
                           style="<?= isset($errors['inn']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['inn'])): ?>
                        <span style="color:#E05252;font-size:12px;"><?= $errors['inn'] ?></span>
                    <?php else: ?>
                        <span style="font-size:12px;color:#888;">10 или 12 цифр</span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="kpp">КПП <span class="required">*</span></label>
                    <input type="text" id="kpp" name="kpp" class="form-input"
                           placeholder="123456789"
                           value="<?= htmlspecialchars($form['kpp']) ?>" required maxlength="9"
                           style="<?= isset($errors['kpp']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['kpp'])): ?>
                        <span style="color:#E05252;font-size:12px;"><?= $errors['kpp'] ?></span>
                    <?php else: ?>
                        <span style="font-size:12px;color:#888;">9 цифр</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="legal_address">Юридический адрес <span class="required">*</span></label>
                    <textarea id="legal_address" name="legal_address" class="form-textarea" style="min-height:80px;" required
                              style="<?= isset($errors['legal_address']) ? 'border-color:#E05252' : '' ?>"
                              ><?= htmlspecialchars($form['legal_address']) ?></textarea>
                    <?php if (isset($errors['legal_address'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['legal_address'] ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_phone">Телефон <span class="required">*</span></label>
                    <input type="tel" id="contact_phone" name="contact_phone" class="form-input"
                           placeholder="+7 (XXX) XXX-XX-XX"
                           value="<?= htmlspecialchars($form['contact_phone']) ?>" required maxlength="30"
                           style="<?= isset($errors['contact_phone']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['contact_phone'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['contact_phone'] ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_email">Email (логин для входа) <span class="required">*</span></label>
                    <input type="email" id="contact_email" name="contact_email" class="form-input"
                           placeholder="org@example.ru"
                           value="<?= htmlspecialchars($form['contact_email']) ?>" required maxlength="255"
                           style="<?= isset($errors['contact_email']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['contact_email'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['contact_email'] ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Временный пароль <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="Минимум 10 символов" required minlength="10"
                           style="<?= isset($errors['password']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['password'])): ?>
                        <span style="color:#E05252;font-size:12px;"><?= $errors['password'] ?></span>
                    <?php else: ?>
                        <span style="font-size:12px;color:#888;">Организатор сможет сменить в профиле</span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirm">Подтвердить пароль <span class="required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                           placeholder="Повторите пароль" required
                           style="<?= isset($errors['password_confirm']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['password_confirm'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['password_confirm'] ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Добавить организатора</button>
                <a href="organizers.php" class="btn-cancel">Отмена</a>
            </div>

        </form>
    </div>

</main>

<footer class="admin-footer">
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="mailto:info@pomozhem.ru">info@pomozhem.ru</a>
</footer>

<script>
document.getElementById('inn').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'');});
document.getElementById('kpp').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'');});
</script>
</body>
</html>
