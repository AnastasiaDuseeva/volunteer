<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/mail_service.php';

date_default_timezone_set(APP_TIMEZONE);

function buildReminderUniqueKey(int $shiftId, int $userId, string $shiftDate): string
{
    return 'REMINDER_1DAY:' . $shiftId . ':' . $userId . ':' . $shiftDate;
}

function notificationExists(PDO $pdo, string $uniqueKey): bool
{
    $sql = "
        SELECT id
        FROM email_notifications
        WHERE unique_key = :unique_key
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':unique_key' => $uniqueKey
    ]);

    return (bool)$stmt->fetchColumn();
}

function createNotificationLog(
    PDO $pdo,
    int $userId,
    ?int $eventId,
    int $shiftId,
    string $uniqueKey,
    string $emailTo,
    string $subject
): int {
    $sql = "
        INSERT INTO email_notifications (
            user_id,
            event_id,
            shift_id,
            type,
            unique_key,
            email_to,
            subject,
            send_status
        ) VALUES (
            :user_id,
            :event_id,
            :shift_id,
            'REMINDER_1DAY',
            :unique_key,
            :email_to,
            :subject,
            'PENDING'
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':event_id' => $eventId,
        ':shift_id' => $shiftId,
        ':unique_key' => $uniqueKey,
        ':email_to' => $emailTo,
        ':subject' => $subject
    ]);

    return (int)$pdo->lastInsertId();
}

function markNotificationSent(PDO $pdo, int $notificationId): void
{
    $sql = "
        UPDATE email_notifications
        SET send_status = 'SENT',
            sent_at = NOW(),
            error_message = NULL
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $notificationId
    ]);
}

function markNotificationFailed(PDO $pdo, int $notificationId, string $errorMessage): void
{
    $sql = "
        UPDATE email_notifications
        SET send_status = 'FAILED',
            error_message = :error_message
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $notificationId,
        ':error_message' => $errorMessage
    ]);
}

$tomorrow = (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))
    ->modify('+1 day')
    ->format('Y-m-d');

$sql = "
    SELECT
        r.volunteer_user_id AS user_id,
        s.id AS shift_id,
        s.event_id,
        s.shift_date,
        s.time_start,
        s.time_end,
        e.title AS event_title,
        vp.email_public AS email,
        vp.last_name,
        vp.first_name,
        vp.middle_name
    FROM shifts s
    INNER JOIN registrations r
        ON r.shift_id = s.id
       AND r.status = 'ACTIVE'
    INNER JOIN events e
        ON e.id = s.event_id
    INNER JOIN volunteer_profiles vp
        ON vp.user_id = r.volunteer_user_id
    WHERE s.shift_date = :tomorrow
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':tomorrow' => $tomorrow
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $userId = (int)$row['user_id'];
    $eventId = !empty($row['event_id']) ? (int)$row['event_id'] : null;
    $shiftId = (int)$row['shift_id'];
    $shiftDate = $row['shift_date'];
    $emailTo = trim((string)$row['email']);

    $fullName = trim(
        $row['last_name'] . ' ' .
        $row['first_name'] . ' ' .
        $row['middle_name']
    );

    $uniqueKey = buildReminderUniqueKey($shiftId, $userId, $shiftDate);

    if (notificationExists($pdo, $uniqueKey)) {
        continue;
    }

    $dateText = formatShiftDate($shiftDate);
    $timeStart = substr((string)$row['time_start'], 0, 5);
    $timeEnd = substr((string)$row['time_end'], 0, 5);
    $eventTitle = (string)$row['event_title'];

    $subject = 'Напоминание о завтрашней смене';

    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeEventTitle = htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8');

    $htmlBody = "
        <p>Здравствуйте, {$safeName}!</p>
        <p>Напоминаем, что вы записаны на завтрашнюю смену.</p>
        <p>
            <strong>Мероприятие:</strong> {$safeEventTitle}<br>
            <strong>Дата:</strong> {$dateText}<br>
            <strong>Время:</strong> {$timeStart}–{$timeEnd}
        </p>
    ";

    $textBody = "Здравствуйте, {$fullName}!\n"
        . "Напоминаем, что вы записаны на завтрашнюю смену.\n"
        . "Мероприятие: {$eventTitle}\n"
        . "Дата: {$dateText}\n"
        . "Время: {$timeStart}–{$timeEnd}";

    $notificationId = createNotificationLog(
        $pdo,
        $userId,
        $eventId,
        $shiftId,
        $uniqueKey,
        $emailTo,
        $subject
    );

    $result = sendEmailMessage(
        $emailTo,
        $fullName,
        $subject,
        $htmlBody,
        $textBody
    );

    if ($result['success']) {
        markNotificationSent($pdo, $notificationId);
    } else {
        markNotificationFailed($pdo, $notificationId, (string)$result['error']);
    }

}
