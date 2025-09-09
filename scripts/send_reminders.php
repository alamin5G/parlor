<?php
// Run this script via browser or cron! (e.g. http://localhost/parlor/scripts/send_reminders.php)
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/email_functions.php'; // must provide sendEmail($to, $subject, $body)

date_default_timezone_set('Asia/Dhaka'); // Set as needed

$now = new DateTime();
$now_fmt = $now->format('Y-m-d H:i:s');

// Reminder types: 1-day and 30-min
$reminder_windows = [
    [
        'label' => '1day',
        'diff_min' => 1440, // 24*60
        'window_min' => 45, // ±45min window
        'subject' => "Your parlor appointment is tomorrow",
        'body_tpl' => "Dear {NAME},<br>Your appointment for {SERVICE} is on {DATE} at {TIME}.<br>We look forward to seeing you!",
    ],
    [
        'label' => '30min',
        'diff_min' => 30,
        'window_min' => 10, // ±10min window
        'subject' => "Your appointment is soon!",
        'body_tpl' => "Dear {NAME},<br>This is a reminder: your appointment for {SERVICE} is today at {TIME}.",
    ],
];

foreach ($reminder_windows as $rw) {
    // Look for appointments within the reminder window that do NOT already have a reminder sent
    $sql = "
    SELECT a.id AS appointment_id, a.scheduled_at, u.email, u.name, s.name as service_name
    FROM appointments a
    JOIN users u ON a.customer_id = u.id
    JOIN services s ON a.service_id = s.id
    WHERE a.status = 'booked'
      AND a.scheduled_at > NOW()
      AND ABS(TIMESTAMPDIFF(MINUTE, NOW(), a.scheduled_at) - ?) <= ?
      AND NOT EXISTS (
        SELECT 1 FROM appointment_reminders ar 
        WHERE ar.appointment_id = a.id
          AND ar.status = 'sent'
          AND ar.sent_at > DATE_SUB(a.scheduled_at, INTERVAL (?+?+5) MINUTE) -- Prevent duplicates
      )
    ";
    $stmt = $conn->prepare($sql);
    // Reminder time, window size, left/right window for duplicate check
    $reminder_min = $rw['diff_min'];
    $window_min = $rw['window_min'];
    $window_total = $rw['window_min'] * 2;
    $stmt->bind_param("iii", $reminder_min, $window_min, $window_total, $reminder_min);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Compose email
        $email = $row['email'];
        $subject = $rw['subject'];
        $body = str_replace(
            ['{NAME}', '{SERVICE}', '{DATE}', '{TIME}'],
            [htmlspecialchars($row['name']), htmlspecialchars($row['service_name']), date('d M Y', strtotime($row['scheduled_at'])), date('h:i A', strtotime($row['scheduled_at']))],
            $rw['body_tpl']
        );
        // Send email
        $sent = sendEmail($email, $subject, $body);
        // Log in appointment_reminders
        $status = $sent ? 'sent' : 'failed';
        $stmt2 = $conn->prepare("INSERT INTO appointment_reminders (appointment_id, status) VALUES (?, ?)");
        $stmt2->bind_param("is", $row['appointment_id'], $status);
        $stmt2->execute();
        $stmt2->close();
        echo "Sent to: $email | $subject | Status: $status<br>";
    }
    $stmt->close();
}
echo "<br>Done.";
?>
