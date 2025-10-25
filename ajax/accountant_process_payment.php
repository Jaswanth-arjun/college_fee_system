<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_accountant_auth();
$db = new Database();
$conn = $db->getConnection();

$queue_id = $_POST['queue_id'];
$student_id = $_POST['student_id'];
$accountant_id = $_SESSION['accountant_id'];
$counter_id = $_SESSION['counter_id'];

try {
    $conn->beginTransaction();

    // Get queue details
    $queue_sql = "SELECT fee_types FROM queue WHERE id = ?";
    $queue_stmt = $conn->prepare($queue_sql);
    $queue_stmt->execute([$queue_id]);
    $queue_data = $queue_stmt->fetch();
    $fee_types = json_decode($queue_data['fee_types'], true);

    $receipt_number = generateReceiptNumber();
    $total_amount = 0;
    $processed_fees = [];

    // Process each fee payment
    foreach ($fee_types as $fee) {
        $fee_type_id = $fee['fee_type_id'];
        $amount_paid = $fee['amount'];
        $total_amount += $amount_paid;

        // Update student_fees table
        $update_sql = "UPDATE student_fees 
                      SET paid_amount = paid_amount + ?, 
                          remaining_amount = remaining_amount - ? 
                      WHERE student_id = ? AND fee_type_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$amount_paid, $amount_paid, $student_id, $fee_type_id]);

        // Insert transaction
        $transaction_sql = "INSERT INTO transactions 
                           (student_id, accountant_id, counter_id, fee_type_id, amount_paid, receipt_number) 
                           VALUES (?, ?, ?, ?, ?, ?)";
        $transaction_stmt = $conn->prepare($transaction_sql);
        $transaction_stmt->execute([$student_id, $accountant_id, $counter_id, $fee_type_id, $amount_paid, $receipt_number]);

        // Get fee name for receipt
        $fee_name_sql = "SELECT name FROM fee_types WHERE id = ?";
        $fee_name_stmt = $conn->prepare($fee_name_sql);
        $fee_name_stmt->execute([$fee_type_id]);
        $fee_name = $fee_name_stmt->fetch()['name'];

        $processed_fees[] = [
            'fee_name' => $fee_name,
            'amount_paid' => $amount_paid
        ];
    }

    // Update queue status
    $queue_update_sql = "UPDATE queue SET status = 'completed' WHERE id = ?";
    $queue_update_stmt = $conn->prepare($queue_update_sql);
    $queue_update_stmt->execute([$queue_id]);

    // Recalculate positions for remaining students
    recalculate_queue_positions_after_payment($conn, $counter_id);

    $conn->commit();

    // Prepare receipt data
    $receipt_data = [
        'receipt_number' => $receipt_number,
        'payment_date' => date('d/m/Y H:i:s'),
        'college_name' => 'Your College Name',
        'college_address' => 'College Address, City - Pincode',
        'student_name' => get_student_name($conn, $student_id),
        'roll_number' => get_student_roll($conn, $student_id),
        'year_branch' => get_student_year_branch($conn, $student_id),
        'counter_name' => get_counter_name($conn, $counter_id),
        'fees' => $processed_fees,
        'total_amount' => $total_amount
    ];

    echo json_encode([
        'success' => true,
        'receipt' => $receipt_data
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage()
    ]);
}
?>

<?php
function recalculate_queue_positions_after_payment($conn, $counter_id)
{
    // Get all waiting students in this counter ordered by joined_at
    $sql = "SELECT id FROM queue WHERE counter_id = ? AND status = 'waiting' ORDER BY joined_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$counter_id]);
    $students = $stmt->fetchAll();

    // Update positions
    $position = 1;
    foreach ($students as $student) {
        $update_sql = "UPDATE queue SET position = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$position, $student['id']]);
        $position++;
    }
}

function get_student_name($conn, $student_id)
{
    $sql = "SELECT full_name FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetch()['full_name'];
}

function get_student_roll($conn, $student_id)
{
    $sql = "SELECT roll_number FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetch()['roll_number'];
}

function get_student_year_branch($conn, $student_id)
{
    $sql = "SELECT academic_year, branch FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    return $student['academic_year'] . ' - ' . $student['branch'];
}

function get_counter_name($conn, $counter_id)
{
    $sql = "SELECT name FROM counters WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$counter_id]);
    return $stmt->fetch()['name'];
}
?>