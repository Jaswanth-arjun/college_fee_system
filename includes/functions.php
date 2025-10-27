<?php
// Only define functions if they don't already exist

if (!function_exists('clean_input')) {
    function clean_input($data)
    {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}

if (!function_exists('generateOTP')) {
    function generateOTP()
    {
        return rand(100000, 999999);
    }
}

if (!function_exists('generateReceiptNumber')) {
    function generateReceiptNumber()
    {
        return "RCP" . date('Ymd') . rand(1000, 9999);
    }
}

if (!function_exists('calculateRemainingAmount')) {
    function calculateRemainingAmount($total, $paid)
    {
        return max(0, $total - $paid);
    }
}

if (!function_exists('getQueuePosition')) {
    function getQueuePosition($conn, $counter_id)
    {
        $sql = "SELECT COUNT(*) as position FROM queue WHERE counter_id = ? AND status = 'waiting'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);
        $result = $stmt->fetch();
        return $result['position'] + 1;
    }
}

// Student related functions
if (!function_exists('get_student_details')) {
    function get_student_details($conn, $student_id)
    {
        $sql = "SELECT * FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('get_fee_summary')) {
    function get_fee_summary($conn, $student_id)
    {
        $sql = "SELECT ft.name, sf.total_amount, sf.paid_amount, sf.remaining_amount 
                FROM student_fees sf 
                JOIN fee_types ft ON sf.fee_type_id = ft.id 
                WHERE sf.student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_available_counters')) {
    function get_available_counters($conn)
    {
        $sql = "SELECT c.id, c.name, c.location, 
                       GROUP_CONCAT(ft.name SEPARATOR ', ') as fee_types
                FROM counters c
                LEFT JOIN counter_fee_types cft ON c.id = cft.counter_id
                LEFT JOIN fee_types ft ON cft.fee_type_id = ft.id
                WHERE c.is_active = 1
                GROUP BY c.id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Accountant related functions
if (!function_exists('get_accountant_details')) {
    function get_accountant_details($conn, $accountant_id)
    {
        $sql = "SELECT * FROM accountants WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$accountant_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('get_counter_details')) {
    function get_counter_details($conn, $counter_id)
    {
        $sql = "SELECT * FROM counters WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('get_current_queue')) {
    function get_current_queue($conn, $counter_id)
    {
        $sql = "SELECT q.id as queue_id, q.position, s.id as student_id, s.full_name, s.roll_number, s.academic_year, s.branch
                FROM queue q 
                JOIN students s ON q.student_id = s.id 
                WHERE q.counter_id = ? AND q.status = 'waiting' 
                ORDER BY q.position ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_recent_accountant_transactions')) {
    function get_recent_accountant_transactions($conn, $accountant_id)
    {
        $sql = "SELECT t.receipt_number, s.full_name, t.amount_paid, t.payment_date, 
                       GROUP_CONCAT(ft.name SEPARATOR ', ') as fee_types
                FROM transactions t 
                JOIN students s ON t.student_id = s.id 
                JOIN fee_types ft ON t.fee_type_id = ft.id 
                WHERE t.accountant_id = ? 
                GROUP BY t.id 
                ORDER BY t.payment_date DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$accountant_id]);
        $transactions = $stmt->fetchAll();

        if (empty($transactions)) {
            return '<p>No recent transactions</p>';
        }

        $html = '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th style="padding: 10px; border-bottom: 1px solid #ddd;">Receipt No</th><th style="padding: 10px; border-bottom: 1px solid #ddd;">Student</th><th style="padding: 10px; border-bottom: 1px solid #ddd;">Amount</th><th style="padding: 10px; border-bottom: 1px solid #ddd;">Date</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($transactions as $transaction) {
            $html .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$transaction['receipt_number']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$transaction['full_name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₹" . number_format($transaction['amount_paid'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . date('M d, H:i', strtotime($transaction['payment_date'])) . "</td>
            </tr>";
        }

        $html .= '</tbody></table>';
        return $html;
    }
}

// Admin functions
if (!function_exists('get_students_count')) {
    function get_students_count($conn)
    {
        $sql = "SELECT COUNT(*) as count FROM students WHERE email_verified = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}

if (!function_exists('get_accountants_count')) {
    function get_accountants_count($conn)
    {
        $sql = "SELECT COUNT(*) as count FROM accountants WHERE is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}

if (!function_exists('get_total_collected')) {
    function get_total_collected($conn)
    {
        $sql = "SELECT SUM(amount_paid) as total FROM transactions";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ? $result['total'] : 0;
    }
}

if (!function_exists('get_pending_queue_count')) {
    function get_pending_queue_count($conn)
    {
        $sql = "SELECT COUNT(*) as count FROM queue WHERE status = 'waiting'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}

if (!function_exists('get_recent_transactions')) {
    function get_recent_transactions($conn)
    {
        $sql = "SELECT t.receipt_number, s.full_name, t.amount_paid, t.payment_date, c.name as counter_name 
                FROM transactions t 
                JOIN students s ON t.student_id = s.id 
                JOIN counters c ON t.counter_id = c.id 
                ORDER BY t.payment_date DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        $html = '';
        foreach ($transactions as $transaction) {
            $html .= "<tr>
                <td>{$transaction['receipt_number']}</td>
                <td>{$transaction['full_name']}</td>
                <td>₹" . number_format($transaction['amount_paid'], 2) . "</td>
                <td>" . date('M d, Y H:i', strtotime($transaction['payment_date'])) . "</td>
                <td>{$transaction['counter_name']}</td>
            </tr>";
        }

        if (empty($html)) {
            $html = "<tr><td colspan='5' style='text-align: center;'>No transactions found</td></tr>";
        }

        return $html;
    }
}

// Database schema check function
if (!function_exists('check_database_tables')) {
    function check_database_tables($conn)
    {
        $required_tables = ['students', 'admins', 'accountants', 'counters', 'fee_types', 'counter_fee_types', 'student_fees', 'queue', 'transactions', 'otp_verification'];
        $missing_tables = [];

        foreach ($required_tables as $table) {
            $check_sql = "SHOW TABLES LIKE '$table'";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }

        return $missing_tables;
    }
}

// Queue management functions
if (!function_exists('recalculate_queue_positions')) {
    function recalculate_queue_positions($conn, $counter_id)
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
}

if (!function_exists('assign_default_fees')) {
    function assign_default_fees($conn, $student_id, $academic_year)
    {
        // Get all active fee types for the student's academic year
        $fee_sql = "SELECT * FROM fee_types WHERE is_active = 1 AND (academic_year = ? OR academic_year = 'All' OR academic_year = '2024')";
        $fee_stmt = $conn->prepare($fee_sql);
        $fee_stmt->execute([$academic_year]);
        $fee_types = $fee_stmt->fetchAll();

        // Assign fees to student
        foreach ($fee_types as $fee) {
            // Check if fee already assigned
            $check_sql = "SELECT id FROM student_fees WHERE student_id = ? AND fee_type_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$student_id, $fee['id']]);

            if ($check_stmt->rowCount() == 0) {
                $insert_sql = "INSERT INTO student_fees (student_id, fee_type_id, total_amount, paid_amount, remaining_amount, academic_year) 
                              VALUES (?, ?, ?, 0, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->execute([$student_id, $fee['id'], $fee['total_amount'], $fee['total_amount'], $academic_year]);
            }
        }
    }
}

// Validation functions - MOVED FROM auth.php
if (!function_exists('validate_student_login')) {
    function validate_student_login($conn, $username, $password)
    {
        $sql = "SELECT * FROM students WHERE roll_number = ? AND email_verified = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password'])) {
            return $student;
        }
        return false;
    }
}

if (!function_exists('validate_admin_login')) {
    function validate_admin_login($conn, $username, $password)
    {
        $sql = "SELECT * FROM admins WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        return false;
    }
}

if (!function_exists('validate_accountant_login')) {
    function validate_accountant_login($conn, $username, $password)
    {
        $sql = "SELECT * FROM accountants WHERE accountant_id = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $accountant = $stmt->fetch();

        if ($accountant && password_verify($password, $accountant['password'])) {
            return $accountant;
        }
        return false;
    }
}
// Add this function to includes/functions.php
if (!function_exists('assign_fee_to_students')) {
    function assign_fee_to_students($conn, $fee_type_id, $academic_year, $total_amount)
    {
        try {
            // Build query based on academic year selection
            if ($academic_year == 'All') {
                // Assign to all students regardless of academic year
                $sql = "SELECT id FROM students WHERE email_verified = 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            } else {
                // Assign only to students of specific academic year
                $sql = "SELECT id FROM students WHERE academic_year = ? AND email_verified = 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$academic_year]);
            }

            $students = $stmt->fetchAll();
            $assigned_count = 0;

            foreach ($students as $student) {
                // Check if fee already assigned to this student
                $check_sql = "SELECT id FROM student_fees WHERE student_id = ? AND fee_type_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->execute([$student['id'], $fee_type_id]);

                if ($check_stmt->rowCount() == 0) {
                    // Get student's academic year for the assignment
                    $student_year_sql = "SELECT academic_year FROM students WHERE id = ?";
                    $student_year_stmt = $conn->prepare($student_year_sql);
                    $student_year_stmt->execute([$student['id']]);
                    $student_data = $student_year_stmt->fetch();

                    $student_academic_year = $student_data['academic_year'];

                    // Assign fee to student
                    $insert_sql = "INSERT INTO student_fees (student_id, fee_type_id, total_amount, paid_amount, remaining_amount, academic_year) 
                                  VALUES (?, ?, ?, 0, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->execute([$student['id'], $fee_type_id, $total_amount, $total_amount, $student_academic_year]);
                    $assigned_count++;
                }
            }

            return $assigned_count;
        } catch (PDOException $e) {
            error_log("Error assigning fee to students: " . $e->getMessage());
            return 0;
        }
    }
}
?>