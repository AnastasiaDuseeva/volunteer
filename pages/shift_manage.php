<?php
session_start();
require_once 'config.php';

// Только организатор
if (!isLoggedIn() || $_SESSION['user_role'] !== 'ORGANIZER') {
    http_response_code(403);
    exit('Доступ запрещён');
}

$currentUserId = (int)$_SESSION['user_id'];
$action   = $_POST['action'] ?? '';
$eventId  = (int)($_POST['event_id'] ?? 0);
$shiftId  = (int)($_POST['shift_id'] ?? 0);

// Проверяем что мероприятие принадлежит этому организатору
function checkEventOwner(PDO $pdo, int $eventId, int $userId): bool {
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = :eid AND created_by = :uid");
    $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
    return (bool)$stmt->fetch();
}

if (!checkEventOwner($pdo, $eventId, $currentUserId)) {
    exit('Нет прав на это мероприятие');
}

// ── ДОБАВИТЬ СМЕНУ ──────────────────────────────────────────────────
if ($action === 'add') {
    $date      = $_POST['shift_date'] ?? '';
    $timeStart = $_POST['time_start'] ?? '';
    $timeEnd   = $_POST['time_end'] ?? '';
    $capacity  = (int)($_POST['capacity'] ?? 0);

    if ($date && $timeStart && $timeEnd && $capacity > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO shifts (event_id, shift_date, time_start, time_end, capacity, status, created_at, updated_at)
            VALUES (:eid, :date, :ts, :te, :cap, 'ACTIVE', NOW(), NOW())
        ");
        $stmt->execute([
            ':eid'  => $eventId,
            ':date' => $date,
            ':ts'   => $timeStart,
            ':te'   => $timeEnd,
            ':cap'  => $capacity,
        ]);
    }
}

// ── РЕДАКТИРОВАТЬ СМЕНУ ─────────────────────────────────────────────
if ($action === 'edit' && $shiftId > 0) {
    $date      = $_POST['shift_date'] ?? '';
    $timeStart = $_POST['time_start'] ?? '';
    $timeEnd   = $_POST['time_end'] ?? '';
    $capacity  = (int)($_POST['capacity'] ?? 0);

    if ($date && $timeStart && $timeEnd && $capacity > 0) {
        $stmt = $pdo->prepare("
            UPDATE shifts
            SET shift_date = :date, time_start = :ts, time_end = :te,
                capacity = :cap, updated_at = NOW()
            WHERE id = :sid AND event_id = :eid
        ");
        $stmt->execute([
            ':date' => $date,
            ':ts'   => $timeStart,
            ':te'   => $timeEnd,
            ':cap'  => $capacity,
            ':sid'  => $shiftId,
            ':eid'  => $eventId,
        ]);
    }
}

// ── ОТМЕНИТЬ СМЕНУ ──────────────────────────────────────────────────
if ($action === 'cancel' && $shiftId > 0) {
    $stmt = $pdo->prepare("
        UPDATE shifts SET status = 'CANCELED', updated_at = NOW()
        WHERE id = :sid AND event_id = :eid
    ");
    $stmt->execute([':sid' => $shiftId, ':eid' => $eventId]);
}

header("Location: event_details_org.php?id=$eventId");
exit;