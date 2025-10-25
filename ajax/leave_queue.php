<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];

header('Content-Type: application/json');

try {
    // Remove student from queue
    $sql = "DELETE FROM queue WHERE student_id = ? AND status = 'waiting'";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([$student_id]);

    echo json_encode(['success' => $success]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>