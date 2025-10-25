<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$counter_id = $_GET['counter_id'] ?? 0;
$student_id = $_GET['student_id'] ?? 0;

try {
    // Get fee types for this counter
    $sql = "SELECT ft.id as fee_type_id, ft.name as fee_name, ft.total_amount,
                   COALESCE(sf.paid_amount, 0) as paid_amount,
                   (ft.total_amount - COALESCE(sf.paid_amount, 0)) as remaining_amount
            FROM counter_fee_types cft
            JOIN fee_types ft ON cft.fee_type_id = ft.id
            LEFT JOIN student_fees sf ON ft.id = sf.fee_type_id AND sf.student_id = ?
            WHERE cft.counter_id = ? AND ft.is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id, $counter_id]);
    $feeTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['feeTypes' => $feeTypes]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load fee types']);
}
?>