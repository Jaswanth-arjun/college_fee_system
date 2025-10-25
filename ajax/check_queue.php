<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];

header('Content-Type: application/json');

// Check if student is already in queue
$sql = "SELECT q.id, q.counter_id, q.position, c.name as counter_name 
        FROM queue q 
        JOIN counters c ON q.counter_id = c.id 
        WHERE q.student_id = ? AND q.status = 'waiting'";
$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$queue = $stmt->fetch();

echo json_encode([
    'in_queue' => !empty($queue),
    'queue' => $queue
]);
?>