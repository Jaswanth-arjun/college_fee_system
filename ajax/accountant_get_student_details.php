<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_accountant_auth();
$db = new Database();
$conn = $db->getConnection();

$queue_id = $_GET['queue_id'];
$student_id = $_GET['student_id'];

// Get student details
$student_sql = "SELECT * FROM students WHERE id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch();

// Get fee details from queue
$queue_sql = "SELECT fee_types FROM queue WHERE id = ?";
$queue_stmt = $conn->prepare($queue_sql);
$queue_stmt->execute([$queue_id]);
$queue_data = $queue_stmt->fetch();
$fee_types = json_decode($queue_data['fee_types'], true);

// Get fee details
$fees = [];
foreach ($fee_types as $fee) {
    $fee_sql = "SELECT ft.name as fee_name, sf.total_amount, sf.paid_amount, sf.remaining_amount
                FROM fee_types ft
                JOIN student_fees sf ON ft.id = sf.fee_type_id
                WHERE ft.id = ? AND sf.student_id = ?";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->execute([$fee['fee_type_id'], $student_id]);
    $fee_data = $fee_stmt->fetch();

    if ($fee_data) {
        $fees[] = [
            'fee_name' => $fee_data['fee_name'],
            'total_amount' => $fee_data['total_amount'],
            'paid_amount' => $fee_data['paid_amount'],
            'remaining_amount' => $fee_data['remaining_amount'],
            'amount_to_pay' => $fee['amount']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'student' => $student,
    'fees' => $fees
]);
?>