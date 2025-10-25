<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$student_id = $_SESSION['student_id'];
$counter_id = isset($_POST['counter_id']) ? $_POST['counter_id'] : null;
$fee_types_json = isset($_POST['fee_types']) ? $_POST['fee_types'] : null;

// Debug logging
error_log("Join Queue Attempt - Student: $student_id, Counter: $counter_id");

if (!$counter_id || !$fee_types_json) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    $fee_types = json_decode($fee_types_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid fee types format');
    }

    // Check if student is already in queue
    $check_sql = "SELECT id FROM queue WHERE student_id = ? AND status = 'waiting'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$student_id]);

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already in a queue!']);
        exit;
    }

    // Calculate position in queue
    $position_sql = "SELECT COALESCE(MAX(position), 0) + 1 as new_position 
                     FROM queue 
                     WHERE counter_id = ? AND status = 'waiting'";
    $position_stmt = $conn->prepare($position_sql);
    $position_stmt->execute([$counter_id]);
    $position_result = $position_stmt->fetch();
    $position = $position_result['new_position'];

    // Insert into queue
    $sql = "INSERT INTO queue (student_id, counter_id, fee_types, position, status) 
            VALUES (?, ?, ?, ?, 'waiting')";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([$student_id, $counter_id, $fee_types_json, $position]);

    if ($success) {
        error_log("Student $student_id joined queue at counter $counter_id, position $position");
        echo json_encode([
            'success' => true,
            'message' => 'Joined queue successfully!',
            'position' => $position
        ]);
    } else {
        throw new Exception('Database insertion failed');
    }

} catch (Exception $e) {
    error_log("Queue join error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to join queue: ' . $e->getMessage()
    ]);
}
?>