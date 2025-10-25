<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_accountant_auth();
$db = new Database();
$conn = $db->getConnection();

$queue_id = $_POST['queue_id'];
$counter_id = $_SESSION['counter_id'];

try {
    $conn->beginTransaction();

    // Get current position
    $position_sql = "SELECT position FROM queue WHERE id = ?";
    $position_stmt = $conn->prepare($position_sql);
    $position_stmt->execute([$queue_id]);
    $current_position = $position_stmt->fetch()['position'];

    // Get max position
    $max_sql = "SELECT MAX(position) as max_position FROM queue WHERE counter_id = ? AND status = 'waiting'";
    $max_stmt = $conn->prepare($max_sql);
    $max_stmt->execute([$counter_id]);
    $max_position = $max_stmt->fetch()['max_position'];

    // Move to end of queue
    $update_sql = "UPDATE queue SET position = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$max_position + 1, $queue_id]);

    // Recalculate positions
    recalculate_queue_positions_after_skip($conn, $counter_id, $current_position);

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to skip student: ' . $e->getMessage()
    ]);
}
?>

<?php
function recalculate_queue_positions_after_skip($conn, $counter_id, $skipped_position)
{
    // Update positions for students who were behind the skipped student
    $update_sql = "UPDATE queue SET position = position - 1 
                   WHERE counter_id = ? AND status = 'waiting' AND position > ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$counter_id, $skipped_position]);

    // Now set the skipped student to the last position
    $max_sql = "SELECT MAX(position) as max_position FROM queue WHERE counter_id = ? AND status = 'waiting'";
    $max_stmt = $conn->prepare($max_sql);
    $max_stmt->execute([$counter_id]);
    $max_position = $max_stmt->fetch()['max_position'];

    $final_sql = "UPDATE queue SET position = ? 
                  WHERE counter_id = ? AND status = 'waiting' AND position > ?";
    $final_stmt = $conn->prepare($final_sql);
    $final_stmt->execute([$max_position, $counter_id, $max_position - 1]);
}
?>