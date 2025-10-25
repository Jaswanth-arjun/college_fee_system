<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

check_admin_auth();
$db = new Database();
$conn = $db->getConnection();

// Handle AJAX requests
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_fee_type':
                echo json_encode(add_fee_type($conn));
                break;
            case 'get_fee_types':
                echo json_encode(get_fee_types($conn));
                break;
            case 'update_fee_type':
                echo json_encode(update_fee_type($conn));
                break;
            case 'add_counter':
                echo json_encode(add_counter($conn));
                break;
            case 'get_counters':
                echo json_encode(get_counters($conn));
                break;
            case 'assign_fees_to_counter':
                echo json_encode(assign_fees_to_counter($conn));
                break;
            case 'register_accountant':
                echo json_encode(register_accountant($conn));
                break;
            case 'get_accountants':
                echo json_encode(get_accountants($conn));
                break;
            case 'toggle_accountant_status':
                echo json_encode(toggle_accountant_status($conn));
                break;
            case 'get_reports':
                echo json_encode(get_reports($conn));
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Fee Management Functions
function add_fee_type($conn)
{
    try {
        $fee_name = $_POST['fee_name'];
        $academic_year = $_POST['academic_year'];
        $total_amount = $_POST['total_amount'];

        $sql = "INSERT INTO fee_types (name, academic_year, total_amount, is_active) VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$fee_name, $academic_year, $total_amount])) {
            return ['success' => true, 'message' => 'Fee type added successfully'];
        }
        return ['success' => false, 'message' => 'Failed to add fee type'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function get_fee_types($conn)
{
    try {
        $sql = "SELECT id, name as fee_name, academic_year, total_amount, is_active as status 
                FROM fee_types 
                ORDER BY academic_year, name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function update_fee_type($conn)
{
    try {
        $id = $_POST['id'];
        $fee_name = $_POST['fee_name'];
        $academic_year = $_POST['academic_year'];
        $total_amount = $_POST['total_amount'];
        $status = $_POST['status'];

        $sql = "UPDATE fee_types SET name = ?, academic_year = ?, total_amount = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$fee_name, $academic_year, $total_amount, $status, $id])) {
            return ['success' => true, 'message' => 'Fee type updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update fee type'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Counter Management Functions
function add_counter($conn)
{
    try {
        $counter_name = $_POST['counter_name'];
        $location = $_POST['location'];

        $sql = "INSERT INTO counters (name, location, is_active) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$counter_name, $location])) {
            return ['success' => true, 'message' => 'Counter added successfully'];
        }
        return ['success' => false, 'message' => 'Failed to add counter'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function get_counters($conn)
{
    try {
        $sql = "SELECT c.*, GROUP_CONCAT(ft.name) as assigned_fees 
                FROM counters c 
                LEFT JOIN counter_fee_types cft ON c.id = cft.counter_id 
                LEFT JOIN fee_types ft ON cft.fee_type_id = ft.id 
                GROUP BY c.id 
                ORDER BY c.name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($counters as &$counter) {
            $counter['status'] = $counter['is_active'] ? 'Active' : 'Inactive';
            $counter['is_active'] = (bool) $counter['is_active'];
        }

        return $counters;
    } catch (PDOException $e) {
        error_log("Error in get_counters: " . $e->getMessage());
        return ['error' => 'Failed to load counters: ' . $e->getMessage()];
    }
}

function assign_fees_to_counter($conn)
{
    try {
        $counter_id = $_POST['counter_id'];
        $fee_types = isset($_POST['fee_types']) ? $_POST['fee_types'] : [];

        $sql = "DELETE FROM counter_fee_types WHERE counter_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);

        if (!empty($fee_types)) {
            $sql = "INSERT INTO counter_fee_types (counter_id, fee_type_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            foreach ($fee_types as $fee_type_id) {
                $stmt->execute([$counter_id, $fee_type_id]);
            }
        }

        return ['success' => true, 'message' => 'Fee types assigned successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Accountant Management Functions
function register_accountant($conn)
{
    try {
        $name = $_POST['name'];
        $unique_id = $_POST['unique_id'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $counter_id = $_POST['counter_id'];

        $sql = "INSERT INTO accountants (accountant_id, full_name, email, phone, password, counter_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$unique_id, $name, $email, $phone, $password, $counter_id])) {
            return ['success' => true, 'message' => 'Accountant registered successfully'];
        }
        return ['success' => false, 'message' => 'Failed to register accountant'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function get_accountants($conn)
{
    try {
        $sql = "SELECT a.id, a.full_name as name, a.accountant_id as unique_id, a.email, a.phone, 
                       c.name as counter_name, a.is_active 
                FROM accountants a 
                LEFT JOIN counters c ON a.counter_id = c.id 
                ORDER BY a.full_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $accountants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($accountants as &$accountant) {
            $accountant['status'] = $accountant['is_active'] ? 'Active' : 'Inactive';
        }

        return $accountants;
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function toggle_accountant_status($conn)
{
    try {
        $id = $_POST['id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;

        $sql = "UPDATE accountants SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$new_status, $id])) {
            return ['success' => true, 'message' => 'Accountant status updated'];
        }
        return ['success' => false, 'message' => 'Failed to update status'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Reports Functions
function get_reports($conn)
{
    try {
        $start_date = $_POST['start_date'] ?? date('Y-m-01');
        $end_date = $_POST['end_date'] ?? date('Y-m-t');

        $reports = [];

        $sql = "SELECT SUM(amount_paid) as total FROM transactions 
                WHERE DATE(payment_date) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $reports['total_collection'] = $stmt->fetch()['total'] ?? 0;

        $sql = "SELECT c.name, SUM(t.amount_paid) as total 
                FROM transactions t 
                JOIN counters c ON t.counter_id = c.id 
                WHERE DATE(t.payment_date) BETWEEN ? AND ? 
                GROUP BY c.id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $reports['by_counter'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT ft.name as fee_name, SUM(t.amount_paid) as total 
                FROM transactions t 
                JOIN fee_types ft ON t.fee_type_id = ft.id 
                WHERE DATE(t.payment_date) BETWEEN ? AND ? 
                GROUP BY ft.id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $reports['by_fee_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $reports;
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Statistics Functions
function get_students_count($conn)
{
    try {
        $sql = "SELECT COUNT(*) as count FROM students WHERE email_verified = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_accountants_count($conn)
{
    try {
        $sql = "SELECT COUNT(*) as count FROM accountants WHERE is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_total_collected($conn)
{
    try {
        $sql = "SELECT SUM(amount_paid) as total FROM transactions";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function get_pending_queue_count($conn)
{
    try {
        $sql = "SELECT COUNT(*) as count FROM queue WHERE status = 'waiting'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_recent_transactions($conn)
{
    try {
        $sql = "SELECT t.receipt_number, s.full_name, t.amount_paid, t.payment_date, c.name as counter_name 
                FROM transactions t 
                JOIN students s ON t.student_id = s.id 
                JOIN counters c ON t.counter_id = c.id 
                ORDER BY t.payment_date DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    } catch (PDOException $e) {
        return "<tr><td colspan='5' style='text-align: center; color: red;'>Error loading transactions</td></tr>";
    }
}

// Get statistics
$students_count = get_students_count($conn);
$accountants_count = get_accountants_count($conn);
$total_collected = get_total_collected($conn);
$pending_queue = get_pending_queue_count($conn);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Fee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --sidebar: #1a1f36;
            --sidebar-hover: #2d3748;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: var(--sidebar);
            color: var(--white);
            height: 100vh;
            padding: 0;
            position: fixed;
            transition: var(--transition);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 i {
            color: var(--primary);
        }

        .nav-links {
            list-style: none;
            padding: 0 15px;
        }

        .nav-links li {
            margin: 5px 0;
        }

        .nav-links a {
            color: #b0b7c3;
            text-decoration: none;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-links a:hover {
            background: var(--sidebar-hover);
            color: var(--white);
        }

        .nav-links a.active {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 6px rgba(66, 97, 238, 0.2);
        }

        .nav-links a i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            width: calc(100% - 260px);
            transition: var(--transition);
        }

        .header {
            background: var(--white);
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .header-content p {
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .stat-card.students {
            border-left-color: var(--info);
        }

        .stat-card.accountants {
            border-left-color: var(--success);
        }

        .stat-card.revenue {
            border-left-color: var(--warning);
        }

        .stat-card.queue {
            border-left-color: var(--danger);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .stat-card.students .stat-icon {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .stat-card.accountants .stat-icon {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-card.revenue .stat-icon {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .stat-card.queue .stat-icon {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .stat-card h3 {
            color: var(--secondary);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: var(--success);
        }

        .stat-trend.down {
            color: var(--danger);
        }

        .section {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }

        .section-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }

        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .table-container {
            overflow-x: auto;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: var(--secondary);
        }

        .btn-outline:hover {
            background: #f8f9fa;
        }

        .form-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--white);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(66, 97, 238, 0.1);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            background: #f8fafc;
        }

        .card-body {
            padding: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
        }

        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: var(--secondary);
            transition: var(--transition);
        }

        .close:hover {
            color: var(--dark);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert {
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .fee-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .fee-type-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: var(--transition);
        }

        .fee-type-checkbox:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }

            .sidebar-header h2 span,
            .nav-links a span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }

            .nav-links a {
                justify-content: center;
                padding: 15px;
            }

            .nav-links a i {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> <span>Fee System</span></h2>
        </div>
        <ul class="nav-links">
            <li><a href="#" class="active" onclick="showSection('dashboard')"><i class="fas fa-home"></i>
                    <span>Dashboard</span></a></li>
            <li><a href="#" onclick="showSection('fee-management')"><i class="fas fa-money-bill-wave"></i> <span>Fee
                        Management</span></a></li>
            <li><a href="#" onclick="showSection('counter-management')"><i class="fas fa-desktop"></i> <span>Counter
                        Management</span></a></li>
            <li><a href="#" onclick="showSection('accountant-management')"><i class="fas fa-user-tie"></i>
                    <span>Accountant Management</span></a></li>
            <li><a href="#" onclick="showSection('reports')"><i class="fas fa-chart-bar"></i> <span>Reports &
                        Analytics</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-content">
                <h1>Admin Dashboard</h1>
                <p>Welcome to the College Fee Management System</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div style="font-weight: 600;">Administrator</div>
                    <div style="font-size: 0.85rem; color: var(--secondary);">Admin</div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card students">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>TOTAL STUDENTS</h3>
                <div class="number"><?php echo $students_count; ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>5% from last month</span>
                </div>
            </div>
            <div class="stat-card accountants">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3>ACTIVE ACCOUNTANTS</h3>
                <div class="number"><?php echo $accountants_count; ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>2 new this month</span>
                </div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <h3>TOTAL COLLECTED</h3>
                <div class="number">₹<?php echo number_format($total_collected, 2); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>12% increase</span>
                </div>
            </div>
            <div class="stat-card queue">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>IN QUEUE</h3>
                <div class="number"><?php echo $pending_queue; ?></div>
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i>
                    <span>3 less than yesterday</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <div class="section-header">
                <h2><i class="fas fa-chart-line"></i> System Overview</h2>
                <button class="btn btn-outline">
                    <i class="fas fa-download"></i> Export Report
                </button>
            </div>
            <div class="recent-activities">
                <h3 style="margin-bottom: 15px; color: var(--dark);">Recent Transactions</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Counter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo get_recent_transactions($conn); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fee Management Section -->
        <div id="fee-management" class="section" style="display: none;">
            <div class="section-header">
                <h2><i class="fas fa-money-bill-wave"></i> Fee Type Management</h2>
            </div>

            <!-- Add Fee Type Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Add New Fee Type</h3>
                </div>
                <div class="card-body">
                    <form id="addFeeTypeForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fee_name">Fee Name</label>
                                <input type="text" id="fee_name" name="fee_name"
                                    placeholder="e.g., Exam Fee, Hostel Fee" required>
                            </div>
                            <div class="form-group">
                                <label for="academic_year">Academic Year</label>
                                <select id="academic_year" name="academic_year" required>
                                    <option value="">Select Year</option>
                                    <option value="1">First Year</option>
                                    <option value="2">Second Year</option>
                                    <option value="3">Third Year</option>
                                    <option value="4">Fourth Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="total_amount">Total Amount (₹)</label>
                                <input type="number" id="total_amount" name="total_amount" step="0.01"
                                    placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Fee Type
                        </button>
                    </form>
                </div>
            </div>

            <!-- Fee Types List -->
            <div class="card">
                <div class="card-header">
                    <h3>Existing Fee Types</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table" id="feeTypesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fee Name</th>
                                    <th>Academic Year</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Fee types will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Counter Management Section -->
        <div id="counter-management" class="section" style="display: none;">
            <div class="section-header">
                <h2><i class="fas fa-desktop"></i> Counter Management</h2>
            </div>

            <!-- Add Counter Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Add New Counter</h3>
                </div>
                <div class="card-body">
                    <form id="addCounterForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="counter_name">Counter Name</label>
                                <input type="text" id="counter_name" name="counter_name"
                                    placeholder="e.g., Counter 1, Counter 2" required>
                            </div>
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location"
                                    placeholder="e.g., Ground Floor, Admin Block" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Counter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Counters List -->
            <div class="card">
                <div class="card-header">
                    <h3>Existing Counters</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table" id="countersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Counter Name</th>
                                    <th>Location</th>
                                    <th>Assigned Fees</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Counters will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accountant Management Section -->
        <div id="accountant-management" class="section" style="display: none;">
            <div class="section-header">
                <h2><i class="fas fa-user-tie"></i> Accountant Management</h2>
            </div>

            <!-- Register Accountant Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Register New Accountant</h3>
                </div>
                <div class="card-body">
                    <form id="registerAccountantForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="acc_name">Full Name</label>
                                <input type="text" id="acc_name" name="name" placeholder="Enter full name" required>
                            </div>
                            <div class="form-group">
                                <label for="unique_id">Unique ID</label>
                                <input type="text" id="unique_id" name="unique_id" placeholder="Enter unique ID"
                                    required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="acc_email">Email</label>
                                <input type="email" id="acc_email" name="email" placeholder="Enter email address"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="acc_counter">Assign Counter</label>
                                <select id="acc_counter" name="counter_id" required>
                                    <option value="">Select Counter</option>
                                    <!-- Counters will be loaded by JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="acc_password">Password</label>
                                <input type="password" id="acc_password" name="password" placeholder="Enter password"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="acc_confirm_password">Confirm Password</label>
                                <input type="password" id="acc_confirm_password" name="confirm_password"
                                    placeholder="Confirm password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register Accountant
                        </button>
                    </form>
                </div>
            </div>

            <!-- Accountants List -->
            <div class="card">
                <div class="card-header">
                    <h3>Existing Accountants</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table" id="accountantsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Unique ID</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Counter</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Accountants will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="section" style="display: none;">
            <div class="section-header">
                <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            </div>

            <!-- Date Filter -->
            <div class="card">
                <div class="card-header">
                    <h3>Filter Reports</h3>
                </div>
                <div class="card-body">
                    <form id="reportsFilterForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date"
                                    value="<?php echo date('Y-m-01'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reports Data -->
            <div id="reportsData">
                <!-- Reports will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Assign Fees Modal -->
    <div id="assignFeesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Fee Types to Counter</h3>
                <span class="close" onclick="closeModal('assignFeesModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="assignFeesForm">
                    <input type="hidden" id="modal_counter_id" name="counter_id">
                    <div class="form-group">
                        <label>Select Fee Types:</label>
                        <div class="fee-types-grid" id="feeTypesCheckboxes">
                            <!-- Fee types checkboxes will be loaded here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('assignFeesModal')">Cancel</button>
                <button type="submit" form="assignFeesForm" class="btn btn-primary">Assign Fees</button>
            </div>
        </div>
    </div>

    <script>
        let selectedCounterId = null;
        let queueInterval = null;

        // Show dashboard by default
        document.addEventListener('DOMContentLoaded', function () {
            showSection('dashboard');
            loadActiveCounters();
        });

        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName);
            if (targetSection) {
                targetSection.style.display = 'block';

                // Load data for the section
                switch (sectionName) {
                    case 'fee-management':
                        loadFeeTypes();
                        break;
                    case 'counter-management':
                        loadCounters();
                        break;
                    case 'accountant-management':
                        loadAccountants();
                        loadActiveCounters();
                        break;
                    case 'reports':
                        generateReports();
                        break;
                }
            }

            // Update active nav link
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Fee Management Functions
        document.getElementById('addFeeTypeForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', true);
            formData.append('action', 'add_fee_type');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Fee type added successfully!');
                        this.reset();
                        loadFeeTypes();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding fee type. Please try again.');
                });
        });

        function loadFeeTypes() {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'get_fee_types');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#feeTypesTable tbody');
                    tbody.innerHTML = '';

                    if (data.error) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading fee types</td></tr>';
                        return;
                    }

                    data.forEach(fee => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${fee.id}</td>
                        <td>${fee.fee_name}</td>
                        <td>Year ${fee.academic_year}</td>
                        <td>₹${parseFloat(fee.total_amount).toFixed(2)}</td>
                        <td><span class="badge ${fee.status === 'Active' ? 'badge-success' : 'badge-danger'}">${fee.status}</span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editFeeType(${fee.id})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteFeeType(${fee.id})">Delete</button>
                        </td>
                    `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading fee types:', error);
                    const tbody = document.querySelector('#feeTypesTable tbody');
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading fee types</td></tr>';
                });
        }

        function editFeeType(id) {
            alert('Edit functionality for fee type ' + id + ' will be implemented here.');
        }

        function deleteFeeType(id) {
            if (confirm('Are you sure you want to delete this fee type?')) {
                alert('Delete functionality for fee type ' + id + ' will be implemented here.');
            }
        }

        // Counter Management Functions
        document.getElementById('addCounterForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', true);
            formData.append('action', 'add_counter');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Counter added successfully!');
                        this.reset();
                        loadCounters();
                        loadActiveCounters();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding counter. Please try again.');
                });
        });

        function loadCounters() {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'get_counters');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#countersTable tbody');
                    tbody.innerHTML = '';

                    if (data.error) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading counters</td></tr>';
                        return;
                    }

                    data.forEach(counter => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${counter.id}</td>
                        <td>${counter.name}</td>
                        <td>${counter.location}</td>
                        <td>${counter.assigned_fees || 'None'}</td>
                        <td><span class="badge ${counter.status === 'Active' ? 'badge-success' : 'badge-danger'}">${counter.status}</span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="assignFees(${counter.id})">Assign Fees</button>
                        </td>
                    `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading counters:', error);
                    const tbody = document.querySelector('#countersTable tbody');
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading counters</td></tr>';
                });
        }

        function assignFees(counterId) {
            document.getElementById('modal_counter_id').value = counterId;
            loadFeeTypesForAssignment(counterId);
            document.getElementById('assignFeesModal').style.display = 'block';
        }

        function loadFeeTypesForAssignment(counterId) {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'get_fee_types');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('feeTypesCheckboxes');
                    container.innerHTML = '';

                    if (data.error) {
                        container.innerHTML = '<div style="text-align: center; color: red;">Error loading fee types</div>';
                        return;
                    }

                    data.forEach(fee => {
                        const div = document.createElement('div');
                        div.className = 'fee-type-checkbox';
                        div.innerHTML = `
                        <input type="checkbox" id="fee_${fee.id}" name="fee_types[]" value="${fee.id}">
                        <label for="fee_${fee.id}">${fee.fee_name} (Year ${fee.academic_year})</label>
                    `;
                        container.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading fee types for assignment:', error);
                    const container = document.getElementById('feeTypesCheckboxes');
                    container.innerHTML = '<div style="text-align: center; color: red;">Error loading fee types</div>';
                });
        }

        document.getElementById('assignFeesForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', true);
            formData.append('action', 'assign_fees_to_counter');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Fee types assigned successfully!');
                        closeModal('assignFeesModal');
                        loadCounters();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error assigning fees. Please try again.');
                });
        });

        // Accountant Management Functions
        function loadActiveCounters() {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'get_counters');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('acc_counter');
                    select.innerHTML = '<option value="">Select Counter</option>';

                    if (data.error) {
                        select.innerHTML = '<option value="">Error loading counters</option>';
                        return;
                    }

                    data.forEach(counter => {
                        if (counter.is_active || counter.status === 'Active') {
                            const option = document.createElement('option');
                            option.value = counter.id;
                            option.textContent = `${counter.name} - ${counter.location}`;
                            select.appendChild(option);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading active counters:', error);
                    const select = document.getElementById('acc_counter');
                    select.innerHTML = '<option value="">Error loading counters</option>';
                });
        }

        document.getElementById('registerAccountantForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const password = document.getElementById('acc_password').value;
            const confirmPassword = document.getElementById('acc_confirm_password').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            const formData = new FormData(this);
            formData.append('ajax', true);
            formData.append('action', 'register_accountant');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Accountant registered successfully!');
                        this.reset();
                        loadAccountants();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error registering accountant. Please try again.');
                });
        });

        function loadAccountants() {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'get_accountants');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#accountantsTable tbody');
                    tbody.innerHTML = '';

                    if (data.error) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Error loading accountants</td></tr>';
                        return;
                    }

                    data.forEach(accountant => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${accountant.id}</td>
                        <td>${accountant.name}</td>
                        <td>${accountant.unique_id}</td>
                        <td>${accountant.email}</td>
                        <td>${accountant.phone}</td>
                        <td>${accountant.counter_name || 'Not assigned'}</td>
                        <td><span class="badge ${accountant.is_active ? 'badge-success' : 'badge-danger'}">${accountant.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <button class="btn ${accountant.is_active ? 'btn-danger' : 'btn-success'} btn-sm" 
                                    onclick="toggleAccountantStatus(${accountant.id}, ${accountant.is_active})">
                                ${accountant.is_active ? 'Disable' : 'Enable'}
                            </button>
                        </td>
                    `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading accountants:', error);
                    const tbody = document.querySelector('#accountantsTable tbody');
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Error loading accountants</td></tr>';
                });
        }

        function toggleAccountantStatus(id, currentStatus) {
            const formData = new FormData();
            formData.append('ajax', true);
            formData.append('action', 'toggle_accountant_status');
            formData.append('id', id);
            formData.append('current_status', currentStatus);

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Accountant status updated!');
                        loadAccountants();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating accountant status. Please try again.');
                });
        }

        // Reports Functions
        document.getElementById('reportsFilterForm').addEventListener('submit', function (e) {
            e.preventDefault();
            generateReports();
        });

        function generateReports() {
            const formData = new FormData(document.getElementById('reportsFilterForm'));
            formData.append('ajax', true);
            formData.append('action', 'get_reports');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('reportsData');

                    if (data.error) {
                        container.innerHTML = '<div style="text-align: center; color: red;">Error generating reports</div>';
                        return;
                    }

                    let html = `
                    <div class="stat-card">
                        <h3>TOTAL COLLECTION</h3>
                        <div class="number">₹${parseFloat(data.total_collection || 0).toFixed(2)}</div>
                    </div>

                    <div class="section">
                        <h3>Collection by Counter</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Counter</th>
                                    <th>Amount Collected</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                    (data.by_counter || []).forEach(counter => {
                        html += `<tr>
                        <td>${counter.name}</td>
                        <td>₹${parseFloat(counter.total || 0).toFixed(2)}</td>
                    </tr>`;
                    });

                    html += `</tbody></table></div>

                    <div class="section">
                        <h3>Collection by Fee Type</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th>Amount Collected</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                    (data.by_fee_type || []).forEach(fee => {
                        html += `<tr>
                        <td>${fee.fee_name}</td>
                        <td>₹${parseFloat(fee.total || 0).toFixed(2)}</td>
                    </tr>`;
                    });

                    html += `</tbody></table></div>`;

                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error generating reports:', error);
                    const container = document.getElementById('reportsData');
                    container.innerHTML = '<div style="text-align: center; color: red;">Error generating reports</div>';
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }
    </script>
</body>

</html>