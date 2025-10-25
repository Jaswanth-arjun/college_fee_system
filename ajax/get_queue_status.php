<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];

header('Content-Type: application/json');

// Get current queue status
$sql = "SELECT q.position, 
               (SELECT COUNT(*) FROM queue WHERE counter_id = q.counter_id AND status = 'waiting') as total_in_queue,
               c.name as counter_name
        FROM queue q 
        JOIN counters c ON q.counter_id = c.id 
        WHERE q.student_id = ? AND q.status = 'waiting'";
$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$queue = $stmt->fetch();

if ($queue) {
    // Calculate estimated wait time (assuming 2 minutes per student)
    $students_ahead = $queue['position'] - 1;
    $wait_time = $students_ahead * 2;

    echo json_encode([
        'position' => $queue['position'],
        'total_in_queue' => $queue['total_in_queue'],
        'wait_time' => $wait_time,
        'counter_name' => $queue['counter_name']
    ]);
} else {
    echo json_encode(['error' => 'Not in queue']);
}
?>