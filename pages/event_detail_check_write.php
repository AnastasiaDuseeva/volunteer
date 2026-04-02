<?php
session_start();
require_once 'config.php';

checkSessionTimeout();
requireLogin();

// Получение данных из формы
$currentUserId = (int)$_SESSION['user_id'];
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$shiftIds = isset($_POST['shift_ids']) && is_array($_POST['shift_ids']) ? $_POST['shift_ids'] : [];

if ($eventId <= 0) {
    die('Некорректное мероприятие.');
}

if (!in_array($action, ['register', 'cancel'], true)) {
    die('Некорректное действие.');
}

$shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds), function ($id) {
    return $id > 0;
})));

if (empty($shiftIds)) {
    header('Location: event_details.php?id=' . $eventId);
    exit;
}

/*Запрещает запись за 24 часа до начала смены, в момент начала смены, 
во время смены, после окончания смены. Запрещает отмену за сутки*/

function isActionClosed(string $date, string $timeStart): bool
{
    $shiftStart = strtotime($date . ' ' . $timeStart);
    $deadline = strtotime('-1 day', $shiftStart);

    return time() >= $deadline;
}

$placeholders = implode(',', array_fill(0, count($shiftIds), '?'));

try {
    $pdo->beginTransaction();

    // Загружаем все выбранные смены сразу
    $stmtShifts = $pdo->prepare("
        SELECT id, event_id, shift_date, time_start, time_end, capacity, status
        FROM shifts
        WHERE event_id = ?
          AND id IN ($placeholders)
    ");
    $stmtShifts->execute(array_merge([$eventId], $shiftIds));

    $shifts = [];
    foreach ($stmtShifts->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $shifts[(int)$row['id']] = $row;
    }

    // Загружаем существующие регистрации пользователя сразу по всем выбранным сменам
    $stmtExisting = $pdo->prepare("
        SELECT id, shift_id, status
        FROM registrations
        WHERE volunteer_user_id = ?
          AND shift_id IN ($placeholders)
    ");
    $stmtExisting->execute(array_merge([$currentUserId], $shiftIds));

    $existingRegistrations = [];
    foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingRegistrations[(int)$row['shift_id']] = $row;
    }

    // Считаем количество активных регистраций сразу по всем выбранным сменам
    $stmtCounts = $pdo->prepare("
        SELECT shift_id, COUNT(*) AS registered_count
        FROM registrations
        WHERE status = 'ACTIVE'
          AND shift_id IN ($placeholders)
        GROUP BY shift_id
    ");
    $stmtCounts->execute($shiftIds);

    $registeredCounts = [];
    foreach ($stmtCounts->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $registeredCounts[(int)$row['shift_id']] = (int)$row['registered_count'];
    }

    // Подготавливаем запросы один раз
    $stmtRestore = $pdo->prepare("
        UPDATE registrations
        SET status = 'ACTIVE',
            registered_at = CURRENT_TIMESTAMP,
            canceled_at = NULL,
            cancel_reason = NULL
        WHERE id = ?
    ");

    $stmtInsert = $pdo->prepare("
        INSERT INTO registrations (shift_id, volunteer_user_id, status)
        VALUES (?, ?, 'ACTIVE')
    ");

    $stmtCancel = $pdo->prepare("
        UPDATE registrations
        SET status = 'CANCELED',
            canceled_at = CURRENT_TIMESTAMP,
            cancel_reason = 'Отмена пользователем'
        WHERE id = ?
    ");

    foreach ($shiftIds as $shiftId) {
        if (!isset($shifts[$shiftId])) {
            continue;
        }

        $shift = $shifts[$shiftId];
        $existingRegistration = $existingRegistrations[$shiftId] ?? null;
        $registeredCount = $registeredCounts[$shiftId] ?? 0;

         if ($action === 'register') {
            if (
                $shift['status'] !== 'ACTIVE' ||
                isActionClosed($shift['shift_date'], $shift['time_start']) ||
                ($existingRegistration && $existingRegistration['status'] === 'ACTIVE') ||
                $registeredCount >= (int)$shift['capacity']
            ) {
                continue;
            }

            if ($existingRegistration && $existingRegistration['status'] === 'CANCELED') {
                $stmtRestore->execute([$existingRegistration['id']]);
            } else {
                $stmtInsert->execute([$shiftId, $currentUserId]);
            }

            continue;
        }

        if ($action === 'cancel') {
            if (
                !$existingRegistration ||
                $existingRegistration['status'] !== 'ACTIVE' ||
                isActionClosed($shift['shift_date'], $shift['time_start'])
            ) {
                continue;
            }

            $stmtCancel->execute([$existingRegistration['id']]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Ошибка в event_detail_check_write.php: ' . $e->getMessage());
    die('Ошибка обработки записи на смену.');
}

header('Location: event_details.php?id=' . $eventId);
exit;