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

    // Get the position before deletion for recalculation
    $position_sql = "SELECT position FROM queue WHERE id = ?";
    $position_stmt = $conn->prepare($position_sql);
    $position_stmt->execute([$queue_id]);
    $position = $position_stmt->fetch()['position'];

    // Remove student from queue
    $delete_sql = "DELETE FROM queue WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->execute([$queue_id]);

    // Recalculate positions for remaining students
    recalculate_queue_positions_after_removal($conn, $counter_id, $position);

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove student: ' . $e->getMessage()
    ]);
}
?>

<?php
function recalculate_queue_positions_after_removal($conn, $counter_id, $removed_position)
{
    // Get all waiting students in this counter with position greater than removed position
    $sql = "SELECT id FROM queue 
            WHERE counter_id = ? AND status = 'waiting' AND position > ? 
            ORDER BY position ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$counter_id, $removed_position]);
    $students = $stmt->fetchAll();

    // Update positions
    $new_position = $removed_position;
    foreach ($students as $student) {
        $update_sql = "UPDATE queue SET position = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$new_position, $student['id']]);
        $new_position++;
    }
}
?>