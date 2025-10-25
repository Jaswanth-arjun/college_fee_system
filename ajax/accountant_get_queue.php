<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_accountant_auth();
$db = new Database();
$conn = $db->getConnection();

$counter_id = $_SESSION['counter_id'];

// Get current queue
$sql = "SELECT q.id as queue_id, q.position, s.id as student_id, s.full_name, s.roll_number
        FROM queue q 
        JOIN students s ON q.student_id = s.id 
        WHERE q.counter_id = ? AND q.status = 'waiting' 
        ORDER BY q.position ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([$counter_id]);
$queue = $stmt->fetchAll();

// Get queue statistics
$stats_sql = "SELECT 
              COUNT(*) as waiting,
              (SELECT COUNT(*) FROM transactions t 
               JOIN queue q ON t.student_id = q.student_id 
               WHERE t.accountant_id = ? AND DATE(t.payment_date) = CURDATE()) as completed_today";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute([$_SESSION['accountant_id']]);
$stats = $stats_stmt->fetch();

header('Content-Type: application/json');
echo json_encode([
    'queue' => $queue,
    'stats' => $stats
]);
?>