<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Start output buffering to catch any accidental output
ob_start();

try {
    check_student_auth();
    $db = new Database();
    $conn = $db->getConnection();

    $counter_id = isset($_GET['counter_id']) ? intval($_GET['counter_id']) : 0;
    $student_id = $_SESSION['student_id'];

    if ($counter_id <= 0) {
        throw new Exception('Invalid counter ID');
    }

    // Get fee types available at this counter that student has pending
    $sql = "SELECT ft.id as fee_type_id, ft.name as fee_name, 
                   sf.total_amount, sf.paid_amount, sf.remaining_amount
            FROM counter_fee_types cft
            JOIN fee_types ft ON cft.fee_type_id = ft.id
            JOIN student_fees sf ON ft.id = sf.fee_type_id
            WHERE cft.counter_id = ? AND sf.student_id = ? AND sf.remaining_amount > 0
            AND ft.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$counter_id, $student_id]);
    $fee_types = $stmt->fetchAll();

    // Clear any output that might have been generated
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode(['feeTypes' => $fee_types]);

} catch (Exception $e) {
    // Clear any output
    ob_clean();

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load fee types: ' . $e->getMessage()]);
}

// End output buffering and flush
ob_end_flush();
?>