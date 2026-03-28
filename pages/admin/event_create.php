<?php
require_once 'auth_check.php';
$db = getDB();

$errors = [];
$form   = ['title'=>'','category_id'=>'','city'=>'','location'=>'','description'=>'',
           'full_description'=>'','start_date'=>'','end_date'=>'',
           'required_volunteers'=>'','recruitment_status'=>'Открыт'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Читаем поля
    foreach (['title','city','location','description','full_description','start_date','end_date','recruitment_status'] as $f) {
        $form[$f] = trim($_POST[$f] ?? '');
    }
    $form['category_id']          = intval($_POST['category_id'] ?? 0);
    $form['required_volunteers']  = intval($_POST['required_volunteers'] ?? 0);

    // Задачи волонтёра — массив строк, сохраняем как JSON
    $tasks = array_filter(array_map('trim', $_POST['tasks'] ?? []), fn($t) => $t !== '');
    $volunteersTasksJson = !empty($tasks) ? json_encode(array_values($tasks), JSON_UNESCAPED_UNICODE) : null;

    // Валидация
    if (empty($form['title']))              $errors['title']               = 'Введите название';
    if (empty($form['city']))               $errors['city']                = 'Введите город';
    if (empty($form['description']))        $errors['description']         = 'Введите краткое описание';
    if (empty($form['start_date']))         $errors['start_date']          = 'Укажите дату начала';
    if ($form['required_volunteers'] < 1)   $errors['required_volunteers'] = 'Укажите количество волонтёров';

    // Загрузка фото
    $imagePath = null;
    if (!empty($_FILES['photo']['name'])) {
        $file     = $_FILES['photo'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($ext, $allowed)) {
            $errors['photo'] = 'Разрешены только JPG, PNG, WEBP';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = 'Файл не должен превышать 5 МБ';
        } else {
            // Генерируем уникальное имя чтобы файлы не перезаписывали друг друга
            $newName   = uniqid('event_') . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/events/';

            // Создаём папку если её нет
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                // В базу сохраняем относительный путь
                $imagePath = 'assets/events/' . $newName;
            } else {
                $errors['photo'] = 'Не удалось сохранить файл. Проверь права на папку assets/events/';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO events
                (title, category_id, description, full_description, volunteers_tasks,
                 city, location, image_path, start_date, end_date,
                 required_volunteers, recruitment_status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $form['title'],
            $form['category_id'] ?: null,
            $form['description'],
            $form['full_description'] ?: null,
            $volunteersTasksJson,
            $form['city'],
            $form['location'] ?: null,
            $imagePath,
            $form['start_date'],
            $form['end_date'] ?: null,
            $form['required_volunteers'],
            $form['recruitment_status'],
            $admin['id'],
        ]);

        header('Location: events.php?created=1');
        exit;
    }
}

// Категории для выпадающего списка — берём из БД
$categories = $db->query("SELECT id, name FROM event_categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать мероприятие — Администратор</title>
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

    <a href="events.php" class="back-link">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Назад к списку мероприятий
    </a>

    <?php if (!empty($errors['general'])): ?>
    <div style="background:#FDEAEA;border:1px solid #E05252;border-radius:10px;padding:14px 18px;margin-bottom:20px;color:#c0392b;font-weight:600;">
        <?= htmlspecialchars($errors['general']) ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <h1 class="form-title">Создать мероприятие</h1>

        <form action="" method="post" enctype="multipart/form-data" id="createEventForm">

            <!-- Название -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="title">Название <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-input"
                           placeholder="Введите название мероприятия"
                           value="<?= htmlspecialchars($form['title']) ?>" required maxlength="255"
                           style="<?= isset($errors['title']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['title'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['title'] ?></span><?php endif; ?>
                </div>
            </div>

            <!-- Категория + Город -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category_id">Категория</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">— без категории —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= $form['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="city">Город <span class="required">*</span></label>
                    <input type="text" id="city" name="city" class="form-input"
                           placeholder="Москва"
                           value="<?= htmlspecialchars($form['city']) ?>" required maxlength="100"
                           style="<?= isset($errors['city']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['city'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['city'] ?></span><?php endif; ?>
                </div>
            </div>

            <!-- Место проведения -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="location">Место проведения</label>
                    <input type="text" id="location" name="location" class="form-input"
                           placeholder="Адрес или название площадки"
                           value="<?= htmlspecialchars($form['location']) ?>" maxlength="255">
                </div>
            </div>

            <!-- Краткое описание -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="description">Краткое описание <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-textarea" style="min-height:80px;"
                              placeholder="Для карточки в списке мероприятий" required
                              style="<?= isset($errors['description']) ? 'border-color:#E05252' : '' ?>"
                              ><?= htmlspecialchars($form['description']) ?></textarea>
                    <?php if (isset($errors['description'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['description'] ?></span><?php endif; ?>
                </div>
            </div>

            <!-- Полное описание -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="full_description">Полное описание</label>
                    <textarea id="full_description" name="full_description" class="form-textarea" style="min-height:140px;"
                              placeholder="Подробное описание для страницы мероприятия"
                              ><?= htmlspecialchars($form['full_description']) ?></textarea>
                </div>
            </div>

            <!-- Задачи волонтёра -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label">Задачи волонтёра / Требования</label>
                    <p style="font-size:13px;color:#888;margin-bottom:10px;">Каждая задача — отдельная строка</p>
                    <div class="tasks-list" id="tasksList">
                        <div class="task-item">
                            <input type="text" name="tasks[]" class="form-input" placeholder="Например: координация волонтёров">
                            <button type="button" class="btn-remove-task" onclick="removeTask(this)">
                                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-task" onclick="addTask()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Добавить задачу
                    </button>
                </div>
            </div>

            <!-- Фото -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label">Фото мероприятия</label>
                    <div class="upload-area">
                        <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                        <div class="upload-icon">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                        <p class="upload-text">Нажми или перетащи изображение</p>
                        <p class="upload-hint">JPG, PNG, WEBP — не более 5 МБ</p>
                    </div>
                    <?php if (isset($errors['photo'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['photo'] ?></span><?php endif; ?>
                    <img id="photoPreview" src="" alt="" style="display:none;margin-top:12px;max-height:200px;border-radius:10px;object-fit:cover;">
                </div>
            </div>

            <!-- Даты -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">Дата начала <span class="required">*</span></label>
                    <input type="date" id="start_date" name="start_date" class="form-input"
                           value="<?= htmlspecialchars($form['start_date']) ?>" required
                           style="<?= isset($errors['start_date']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['start_date'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['start_date'] ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">Дата окончания</label>
                    <input type="date" id="end_date" name="end_date" class="form-input"
                           value="<?= htmlspecialchars($form['end_date']) ?>">
                </div>
            </div>

            <!-- Количество + статус -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="required_volunteers">Кол-во волонтёров <span class="required">*</span></label>
                    <input type="number" id="required_volunteers" name="required_volunteers" class="form-input"
                           placeholder="20" min="1" max="9999"
                           value="<?= htmlspecialchars($form['required_volunteers']) ?>" required
                           style="<?= isset($errors['required_volunteers']) ? 'border-color:#E05252' : '' ?>">
                    <?php if (isset($errors['required_volunteers'])): ?><span style="color:#E05252;font-size:12px;"><?= $errors['required_volunteers'] ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recruitment_status">Статус набора</label>
                    <select id="recruitment_status" name="recruitment_status" class="form-select">
                        <?php foreach (['Проект','Открыт','Закрыт','Завершён'] as $s): ?>
                            <option value="<?= $s ?>" <?= $form['recruitment_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Добавить мероприятие</button>
                <a href="events.php" class="btn-cancel">Отмена</a>
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
function addTask() {
    const list = document.getElementById('tasksList');
    const item = document.createElement('div');
    item.className = 'task-item';
    item.innerHTML = `<input type="text" name="tasks[]" class="form-input" placeholder="Введите задачу">
        <button type="button" class="btn-remove-task" onclick="removeTask(this)">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>`;
    list.appendChild(item);
    item.querySelector('input').focus();
}
function removeTask(btn) {
    const list = document.getElementById('tasksList');
    if (list.children.length > 1) btn.closest('.task-item').remove();
    else btn.closest('.task-item').querySelector('input').value = '';
}
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});
</script>
</body>
</html>
