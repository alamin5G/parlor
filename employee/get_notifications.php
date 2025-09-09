<?php
// filepath: c:\xampp\htdocs\parlor\employee\get_notifications.php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'beautician') {
    echo json_encode(['count' => 0, 'html' => '']);
    exit;
}

// Get employee_id from session
$stmt_emp = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt_emp->bind_param("i", $_SESSION['user_id']);
$stmt_emp->execute();
$employee_id = $stmt_emp->get_result()->fetch_assoc()['id'] ?? 0;
$stmt_emp->close();

if (!$employee_id) {
    echo json_encode(['count' => 0, 'html' => '']);
    exit;
}

$notifications = [];
$now = new DateTime('now', new DateTimeZone('Asia/Dhaka')); // Use your server's timezone
$now_str = $now->format('Y-m-d H:i:s');

// 1. Get new, unseen appointments
$sql_new = "SELECT id, scheduled_at, 'new' as type FROM appointments WHERE employee_id = ? AND status = 'booked' AND is_seen_by_employee = 0";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->bind_param("i", $employee_id);
$stmt_new->execute();
$result_new = $stmt_new->get_result();
while ($row = $result_new->fetch_assoc()) $notifications[] = $row;
$stmt_new->close();

// 2. Get reminders (30 min, 10 min, day before)
$sql_reminders = "SELECT id, scheduled_at,
    CASE
        WHEN scheduled_at BETWEEN ? AND DATE_ADD(?, INTERVAL 10 MINUTE) AND notified_10min = 0 THEN '10min'
        WHEN scheduled_at BETWEEN ? AND DATE_ADD(?, INTERVAL 30 MINUTE) AND notified_30min = 0 THEN '30min'
        WHEN DATE(scheduled_at) = DATE(DATE_ADD(?, INTERVAL 1 DAY)) AND notified_daybefore = 0 THEN 'day_before'
        ELSE NULL
    END as type
    FROM appointments
    WHERE employee_id = ? AND status = 'booked'
    HAVING type IS NOT NULL";
$stmt_reminders = $conn->prepare($sql_reminders);
$stmt_reminders->bind_param("sssssi", $now_str, $now_str, $now_str, $now_str, $now_str, $employee_id);
$stmt_reminders->execute();
$result_reminders = $stmt_reminders->get_result();
while ($row = $result_reminders->fetch_assoc()) $notifications[] = $row;
$stmt_reminders->close();

// Build HTML for the dropdown
$html = '';
if (empty($notifications)) {
    $html = '<li class="dropdown-item text-muted text-center">No new notifications</li>';
} else {
    // Sort notifications by time
    usort($notifications, function($a, $b) {
        return strtotime($a['scheduled_at']) - strtotime($b['scheduled_at']);
    });

    foreach ($notifications as $notif) {
        $time = date('h:i A', strtotime($notif['scheduled_at']));
        $date = date('d M', strtotime($notif['scheduled_at']));
        $message = '';
        $icon = '';

        switch ($notif['type']) {
            case 'new':
                $message = "New appointment assigned for <strong>$date at $time</strong>.";
                $icon = 'fa-plus-circle text-primary';
                break;
            case 'day_before':
                $message = "Reminder: You have an appointment tomorrow at <strong>$time</strong>.";
                $icon = 'fa-calendar-day text-info';
                break;
            case '30min':
                $message = "Reminder: Appointment in <strong>30 minutes</strong> (at $time).";
                $icon = 'fa-clock text-warning';
                break;
            case '10min':
                $message = "Reminder: Appointment in <strong>10 minutes</strong> (at $time).";
                $icon = 'fa-bell text-danger';
                break;
        }
        
        $html .= '<li><a class="dropdown-item d-flex align-items-start" href="appointment_view.php?id=' . $notif['id'] . '">';
        $html .= '<i class="fas ' . $icon . ' fa-fw mt-1 me-2"></i>';
        $html .= '<div>' . $message . '<div class="small text-muted">' . date('d M Y, h:i A', strtotime($notif['scheduled_at'])) . '</div></div>';
        $html .= '</a></li>';
    }
}

// Update notification flags after fetching them
$conn->query("UPDATE appointments SET notified_10min = 1 WHERE employee_id = $employee_id AND status = 'booked' AND scheduled_at BETWEEN '$now_str' AND DATE_ADD('$now_str', INTERVAL 10 MINUTE)");
$conn->query("UPDATE appointments SET notified_30min = 1 WHERE employee_id = $employee_id AND status = 'booked' AND scheduled_at BETWEEN '$now_str' AND DATE_ADD('$now_str', INTERVAL 30 MINUTE)");
$conn->query("UPDATE appointments SET notified_daybefore = 1 WHERE employee_id = $employee_id AND status = 'booked' AND DATE(scheduled_at) = DATE(DATE_ADD('$now_str', INTERVAL 1 DAY))");

echo json_encode(['count' => count($notifications), 'html' => $html]);
?>