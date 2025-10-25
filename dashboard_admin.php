<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

check_admin_auth();
$db = new Database();
$conn = $db->getConnection();

// Get statistics
$students_count = get_students_count($conn);
$accountants_count = get_accountants_count($conn);
$total_collected = get_total_collected($conn);
$pending_queue = get_pending_queue_count($conn);

// Helper functions for admin dashboard
function get_students_count($conn)
{
    $sql = "SELECT COUNT(*) as count FROM students WHERE email_verified = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch()['count'];
}

function get_accountants_count($conn)
{
    $sql = "SELECT COUNT(*) as count FROM accountants WHERE is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch()['count'];
}

function get_total_collected($conn)
{
    $sql = "SELECT SUM(amount_paid) as total FROM transactions";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ? $result['total'] : 0;
}

function get_pending_queue_count($conn)
{
    $sql = "SELECT COUNT(*) as count FROM queue WHERE status = 'waiting'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch()['count'];
}

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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Fee System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin: 10px 0;
        }

        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px;
            display: block;
            border-radius: 5px;
            cursor: pointer;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #34495e;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card.students {
            border-top: 4px solid #3498db;
        }

        .stat-card.accountants {
            border-top: 4px solid #e74c3c;
        }

        .stat-card.revenue {
            border-top: 4px solid #2ecc71;
        }

        .stat-card.queue {
            border-top: 4px solid #f39c12;
        }

        /* Sections */
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul class="nav-links">
            <li><a href="#" class="active" onclick="showSection('dashboard')">Dashboard</a></li>
            <li><a href="#" onclick="showSection('fee-management')">Fee Management</a></li>
            <li><a href="#" onclick="showSection('counter-management')">Counter Management</a></li>
            <li><a href="#" onclick="showSection('accountant-management')">Accountant Management</a></li>
            <li><a href="#" onclick="showSection('reports')">Reports & Analytics</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <p>Welcome to the College Fee Management System</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card students">
                <h3>TOTAL STUDENTS</h3>
                <div class="number"><?php echo $students_count; ?></div>
            </div>
            <div class="stat-card accountants">
                <h3>ACTIVE ACCOUNTANTS</h3>
                <div class="number"><?php echo $accountants_count; ?></div>
            </div>
            <div class="stat-card revenue">
                <h3>TOTAL COLLECTED</h3>
                <div class="number">₹<?php echo number_format($total_collected, 2); ?></div>
            </div>
            <div class="stat-card queue">
                <h3>IN QUEUE</h3>
                <div class="number"><?php echo $pending_queue; ?></div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <h2>System Overview</h2>
            <div class="recent-activities">
                <h3>Recent Transactions</h3>
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

        <!-- Fee Management Section -->
        <div id="fee-management" class="section" style="display: none;">
            <h2>Fee Type Management</h2>
            <p>Fee management functionality will be implemented here.</p>
            <p>You can add, edit, and manage fee types.</p>
        </div>

        <!-- Counter Management Section -->
        <div id="counter-management" class="section" style="display: none;">
            <h2>Counter Management</h2>
            <p>Counter management functionality will be implemented here.</p>
            <p>You can add counters and assign fee types to them.</p>
        </div>

        <!-- Accountant Management Section -->
        <div id="accountant-management" class="section" style="display: none;">
            <h2>Accountant Management</h2>
            <p>Accountant management functionality will be implemented here.</p>
            <p>You can register and manage accountants.</p>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="section" style="display: none;">
            <h2>Reports & Analytics</h2>
            <p>Reports and analytics functionality will be implemented here.</p>
            <p>View comprehensive reports on fee collections.</p>
        </div>
    </div>

    <script>
        // Show dashboard by default
        document.addEventListener('DOMContentLoaded', function () {
            showSection('dashboard');
        });

        function showSection(sectionName) {
            console.log('Showing section:', sectionName); // Debug log

            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName);
            if (targetSection) {
                targetSection.style.display = 'block';
            }

            // Update active nav link
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });

            // Find and activate the clicked link
            event.target.classList.add('active');
        }
    </script>
</body>

</html>