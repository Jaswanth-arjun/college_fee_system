<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

check_accountant_auth();
$db = new Database();
$conn = $db->getConnection();

$accountant_id = $_SESSION['accountant_id'];
$counter_id = $_SESSION['counter_id'];

// Get accountant and counter details
function get_accountant_details($conn, $accountant_id)
{
    try {
        $sql = "SELECT * FROM accountants WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$accountant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

function get_counter_details($conn, $counter_id)
{
    try {
        $sql = "SELECT * FROM counters WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

function get_current_queue($conn, $counter_id)
{
    try {
        $sql = "SELECT q.id as queue_id, q.student_id, q.position, q.status, 
                       s.full_name, s.roll_number, s.academic_year, s.branch, s.email
                FROM queue q 
                JOIN students s ON q.student_id = s.id 
                WHERE q.counter_id = ? AND (q.status = 'waiting' OR q.status = 'processing')
                ORDER BY q.position ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$counter_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function get_recent_accountant_transactions($conn, $accountant_id)
{
    try {
        $sql = "SELECT t.receipt_number, s.full_name, t.amount_paid, t.created_at,
                       GROUP_CONCAT(ft.name) as fee_types
                FROM transactions t
                JOIN students s ON t.student_id = s.id
                JOIN fee_types ft ON t.fee_type_id = ft.id
                WHERE t.accountant_id = ?
                GROUP BY t.id
                ORDER BY t.created_at DESC
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$accountant_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($transactions)) {
            return '<p style="text-align: center; color: rgba(255, 255, 255, 0.8); padding: 20px;">No recent transactions</p>';
        }

        $html = '<table class="fee-table" style="width: 100%;">';
        $html .= '<thead><tr><th>Receipt No</th><th>Student</th><th>Amount</th><th>Date</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($transactions as $transaction) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($transaction['receipt_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['full_name']) . '</td>';
            $html .= '<td>₹' . number_format($transaction['amount_paid'], 2) . '</td>';
            $html .= '<td>' . date('M d, H:i', strtotime($transaction['created_at'])) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    } catch (PDOException $e) {
        return '<p style="text-align: center; color: rgba(255, 255, 255, 0.8); padding: 20px;">Error loading transactions</p>';
    }
}

$accountant = get_accountant_details($conn, $accountant_id);
$counter = get_counter_details($conn, $counter_id);
$queue = get_current_queue($conn, $counter_id);

if (!$accountant || !$counter) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - College Fee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 20%, rgba(168, 255, 120, 0.3) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 154, 158, 0.3) 0%, transparent 20%),
                linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            overflow-x: hidden;
            position: relative;
        }

        /* Black corner overlays */
        .corner-overlay {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            z-index: 0;
        }

        .corner-top-left {
            top: -200px;
            left: -200px;
            background: radial-gradient(circle, #000000 0%, transparent 70%);
        }

        .corner-bottom-right {
            bottom: -200px;
            right: -200px;
            background: radial-gradient(circle, #000000 0%, transparent 70%);
        }

        /* Floating elements for visual interest */
        .floating {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            animation: float 6s ease-in-out infinite;
            z-index: 0;
        }

        .floating:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
            background: rgba(168, 255, 120, 0.2);
        }

        .floating:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
            background: rgba(255, 154, 158, 0.2);
        }

        .floating:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 20%;
            left: 85%;
            animation-delay: 4s;
            background: rgba(120, 255, 214, 0.2);
        }

        .floating:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 15%;
            animation-delay: 1s;
            background: rgba(250, 208, 196, 0.2);
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(10deg);
            }

            100% {
                transform: translateY(0) rotate(0deg);
            }
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .header-info h1 {
            color: white;
            margin-bottom: 5px;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .header-info p {
            color: rgba(255, 255, 255, 0.9);
        }

        .counter-badge {
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            backdrop-filter: blur(5px);
        }

        /* Main Layout */
        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
            position: relative;
            z-index: 1;
        }

        /* Queue Sidebar */
        .queue-sidebar {
            width: 350px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .queue-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .queue-stats h3 {
            margin-bottom: 10px;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .stat {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
        }

        .stat.waiting {
            background: rgba(255, 243, 205, 0.2);
            border: 1px solid rgba(255, 234, 167, 0.3);
            color: #ffd93d;
        }

        .stat.completed {
            background: rgba(209, 236, 241, 0.2);
            border: 1px solid rgba(190, 229, 235, 0.3);
            color: #78ffd6;
        }

        .stat .number {
            font-size: 24px;
            font-weight: bold;
        }

        .stat .label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Queue List */
        .queue-list {
            max-height: 500px;
            overflow-y: auto;
            flex: 1;
            margin-bottom: 80px;
        }

        .queue-item {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .queue-item:hover {
            border-color: #a8ff78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .queue-item.active {
            border-color: #a8ff78;
            background: rgba(168, 255, 120, 0.2);
        }

        .queue-item.processing {
            border-color: #ffd93d;
            background: rgba(255, 217, 61, 0.2);
        }

        .student-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        .student-details h4 {
            margin: 0;
            color: white;
        }

        .student-details p {
            margin: 2px 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        .queue-position {
            background: rgba(108, 117, 125, 0.3);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            backdrop-filter: blur(5px);
        }

        /* Logout Button */
        .logout-container {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }

        .logout-btn {
            width: 100%;
            padding: 12px 20px;
            background: rgba(255, 107, 107, 0.2);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 10px;
            color: #ff6b6b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(97, 240, 100, 0.3);
            border-color: #4bfa4bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(63, 246, 109, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        /* Current Student Card */
        .current-student {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .current-student h2 {
            color: white;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }

        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .student-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 32px;
            font-weight: bold;
        }

        .student-details-large h3 {
            margin: 0 0 5px 0;
            color: white;
        }

        .student-details-large p {
            margin: 2px 0;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Fee Details */
        .fee-details {
            margin: 20px 0;
        }

        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fee-table th,
        .fee-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .fee-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: bold;
            color: white;
            backdrop-filter: blur(5px);
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
        }

        .btn-success {
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
        }

        .btn-warning {
            background: linear-gradient(to right, #ffd93d, #fd7e14);
            color: #333;
        }

        .btn-danger {
            background: linear-gradient(to right, #ff6b6b, #ff9a9e);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .btn:disabled {
            background: rgba(108, 117, 125, 0.3);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* No Student Message */
        .no-student {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.8);
        }

        .no-student h3 {
            margin-bottom: 10px;
            color: white;
        }

        /* Receipt Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
        }

        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.3s;
        }

        .close:hover {
            color: white;
        }

        /* Receipt Styling */
        .receipt {
            font-family: 'Courier New', monospace;
            color: white;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            margin: 0;
            color: white;
        }

        .receipt-details {
            margin: 20px 0;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .receipt-table th,
        .receipt-table td {
            padding: 8px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
            color: white;
        }

        .receipt-table th {
            text-align: left;
        }

        .receipt-total {
            border-top: 2px solid rgba(255, 255, 255, 0.5);
            margin-top: 20px;
            padding-top: 10px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .corner-overlay {
                width: 300px;
                height: 300px;
            }

            .corner-top-left {
                top: -150px;
                left: -150px;
            }

            .corner-bottom-right {
                bottom: -150px;
                right: -150px;
            }

            .main-container {
                flex-direction: column;
            }

            .queue-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .logout-container {
                position: relative;
                bottom: auto;
                left: auto;
                right: auto;
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Black corner overlays -->
    <div class="corner-overlay corner-top-left"></div>
    <div class="corner-overlay corner-bottom-right"></div>

    <!-- Floating background elements -->
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-info">
            <h1>Accountant Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($accountant['full_name']); ?> |
                <?php echo htmlspecialchars($accountant['accountant_id']); ?>
            </p>
        </div>
        <div class="counter-badge">
            <?php echo htmlspecialchars($counter['name']); ?> - <?php echo htmlspecialchars($counter['location']); ?>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Queue Sidebar -->
        <div class="queue-sidebar">
            <div class="queue-stats">
                <h3><i class="fas fa-chart-bar"></i> Queue Statistics</h3>
                <div class="stats-grid">
                    <div class="stat waiting">
                        <div class="number" id="waiting-count">
                            <?php
                            $waiting_count = 0;
                            foreach ($queue as $q) {
                                if (isset($q['status']) && $q['status'] == 'waiting') {
                                    $waiting_count++;
                                }
                            }
                            echo $waiting_count;
                            ?>
                        </div>
                        <div class="label">Waiting</div>
                    </div>
                    <div class="stat completed">
                        <div class="number" id="completed-count">0</div>
                        <div class="label">Completed Today</div>
                    </div>
                </div>
            </div>

            <h3 style="color: white; margin-bottom: 15px;"><i class="fas fa-list"></i> Current Queue</h3>
            <div class="queue-list" id="queue-list">
                <?php if (empty($queue)): ?>
                    <div class="no-student">
                        <p>No students in queue</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($queue as $student): ?>
                        <div class="queue-item" data-queue-id="<?php echo $student['queue_id']; ?>"
                            data-student-id="<?php echo $student['student_id']; ?>">
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($student['roll_number']); ?></p>
                                    <span class="queue-position">Position: <?php echo $student['position']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Logout Button -->
            <div class="logout-container">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="current-student" id="current-student-container">
                <div class="no-student" id="no-student-message">
                    <h3><i class="fas fa-user-clock"></i> No Student Selected</h3>
                    <p>Click on a student from the queue to start processing</p>
                </div>

                <div id="student-details" style="display: none;">
                    <h2><i class="fas fa-money-bill-wave"></i> Process Payment</h2>

                    <!-- Student Header -->
                    <div class="student-header">
                        <div class="student-avatar-large" id="student-avatar-large">
                            <!-- Avatar will be filled by JavaScript -->
                        </div>
                        <div class="student-details-large">
                            <h3 id="student-full-name"></h3>
                            <p><strong>Roll Number:</strong> <span id="student-roll-number"></span></p>
                            <p><strong>Year & Branch:</strong> <span id="student-year-branch"></span></p>
                            <p><strong>Email:</strong> <span id="student-email"></span></p>
                        </div>
                    </div>

                    <!-- Fee Details -->
                    <div class="fee-details">
                        <h4 style="color: white; margin-bottom: 15px;"><i class="fas fa-receipt"></i> Selected Fees for
                            Payment</h4>
                        <table class="fee-table" id="fee-details-table">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th>Total Amount</th>
                                    <th>Already Paid</th>
                                    <th>Remaining</th>
                                    <th>Amount to Pay</th>
                                </tr>
                            </thead>
                            <tbody id="fee-details-body">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <button class="btn btn-success" onclick="processPayment()" id="process-btn">
                            <i class="fas fa-check-circle"></i> Mark as Paid & Print Receipt
                        </button>
                        <button class="btn btn-warning" onclick="skipStudent()" id="skip-btn">
                            <i class="fas fa-forward"></i> Skip (Move to End)
                        </button>
                        <button class="btn btn-danger" onclick="removeStudent()" id="remove-btn">
                            <i class="fas fa-times-circle"></i> Remove from Queue
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="current-student">
                <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                <div id="recent-transactions" style="color: white;">
                    <?php echo get_recent_accountant_transactions($conn, $accountant_id); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal" id="receipt-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Payment Receipt</h2>
                <button class="close" onclick="closeReceipt()">&times;</button>
            </div>
            <div class="receipt" id="receipt-content">
                <!-- Receipt content will be generated here -->
            </div>
            <div class="actions" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button class="btn" onclick="closeReceipt()"
                    style="background: rgba(108, 117, 125, 0.3); color: white;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentQueueId = null;
        let currentStudentId = null;

        // Add click event listeners to queue items
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.queue-item').forEach(item => {
                item.addEventListener('click', function () {
                    const queueId = this.getAttribute('data-queue-id');
                    const studentId = this.getAttribute('data-student-id');
                    selectStudent(queueId, studentId);
                });
            });
        });

        // Auto-refresh queue every 10 seconds
        setInterval(refreshQueue, 10000);

        function refreshQueue() {
            fetch('ajax/accountant_get_queue.php?counter_id=<?php echo $counter_id; ?>')
                .then(response => response.json())
                .then(data => {
                    updateQueueList(data.queue);
                    updateQueueStats(data.stats);
                })
                .catch(error => {
                    console.error('Error refreshing queue:', error);
                });
        }

        function updateQueueList(queue) {
            const queueList = document.getElementById('queue-list');

            if (queue.length === 0) {
                queueList.innerHTML = '<div class="no-student"><p>No students in queue</p></div>';
                return;
            }

            let html = '';
            queue.forEach(student => {
                html += `
                <div class="queue-item" data-queue-id="${student.queue_id}" data-student-id="${student.student_id}">
                    <div class="student-info">
                        <div class="student-avatar">
                            ${student.full_name.charAt(0).toUpperCase()}
                        </div>
                        <div class="student-details">
                            <h4>${student.full_name}</h4>
                            <p>${student.roll_number}</p>
                            <span class="queue-position">Position: ${student.position}</span>
                        </div>
                    </div>
                </div>`;
            });

            queueList.innerHTML = html;

            // Re-add click event listeners
            document.querySelectorAll('.queue-item').forEach(item => {
                item.addEventListener('click', function () {
                    const queueId = this.getAttribute('data-queue-id');
                    const studentId = this.getAttribute('data-student-id');
                    selectStudent(queueId, studentId);
                });
            });
        }

        function updateQueueStats(stats) {
            if (stats.waiting !== undefined) {
                document.getElementById('waiting-count').textContent = stats.waiting;
            }
            if (stats.completed_today !== undefined) {
                document.getElementById('completed-count').textContent = stats.completed_today;
            }
        }

        function selectStudent(queueId, studentId) {
            currentQueueId = queueId;
            currentStudentId = studentId;

            // Update UI to show selected student
            document.querySelectorAll('.queue-item').forEach(item => {
                item.classList.remove('active', 'processing');
            });
            const selectedItem = document.querySelector(`[data-queue-id="${queueId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('processing');
            }

            // Show student details
            document.getElementById('no-student-message').style.display = 'none';
            document.getElementById('student-details').style.display = 'block';

            // Load student details
            fetch(`ajax/accountant_get_student_details.php?queue_id=${queueId}&student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudentDetails(data.student, data.fees);
                    } else {
                        alert('Error loading student details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading student details:', error);
                    alert('Error loading student details. Please try again.');
                });
        }

        function displayStudentDetails(student, fees) {
            // Update student header
            document.getElementById('student-avatar-large').textContent = student.full_name.charAt(0).toUpperCase();
            document.getElementById('student-full-name').textContent = student.full_name;
            document.getElementById('student-roll-number').textContent = student.roll_number;
            document.getElementById('student-year-branch').textContent = `${student.academic_year} - ${student.branch}`;
            document.getElementById('student-email').textContent = student.email;

            // Update fee details
            const feeTableBody = document.getElementById('fee-details-body');
            let html = '';
            let totalAmount = 0;

            if (fees && fees.length > 0) {
                fees.forEach(fee => {
                    const amountToPay = parseFloat(fee.amount_to_pay) || 0;
                    totalAmount += amountToPay;
                    html += `
                    <tr>
                        <td>${fee.fee_name || 'N/A'}</td>
                        <td>₹${parseFloat(fee.total_amount || 0).toFixed(2)}</td>
                        <td>₹${parseFloat(fee.paid_amount || 0).toFixed(2)}</td>
                        <td>₹${parseFloat(fee.remaining_amount || 0).toFixed(2)}</td>
                        <td>₹${amountToPay.toFixed(2)}</td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="5" style="text-align: center;">No fees selected for payment</td></tr>';
            }

            // Add total row
            if (fees && fees.length > 0) {
                html += `
                <tr style="background: rgba(255, 255, 255, 0.1); font-weight: bold;">
                    <td colspan="4" style="text-align: right;">Total Amount:</td>
                    <td>₹${totalAmount.toFixed(2)}</td>
                </tr>`;
            }

            feeTableBody.innerHTML = html;
        }

        function processPayment() {
            if (!currentQueueId || !currentStudentId) {
                alert('Please select a student first!');
                return;
            }

            if (confirm('Mark this payment as completed and generate receipt?')) {
                const processBtn = document.getElementById('process-btn');
                const originalText = processBtn.innerHTML;
                processBtn.disabled = true;
                processBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                fetch('ajax/accountant_process_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `queue_id=${currentQueueId}&student_id=${currentStudentId}&accountant_id=<?php echo $accountant_id; ?>`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showReceipt(data.receipt);
                            refreshQueue();
                            resetStudentDetails();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error processing payment:', error);
                        alert('Error processing payment. Please try again.');
                    })
                    .finally(() => {
                        processBtn.disabled = false;
                        processBtn.innerHTML = originalText;
                    });
            }
        }

        function skipStudent() {
            if (!currentQueueId) {
                alert('Please select a student first!');
                return;
            }

            if (confirm('Move this student to the end of the queue?')) {
                fetch('ajax/accountant_skip_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `queue_id=${currentQueueId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshQueue();
                            resetStudentDetails();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error skipping student:', error);
                        alert('Error skipping student. Please try again.');
                    });
            }
        }

        function removeStudent() {
            if (!currentQueueId) {
                alert('Please select a student first!');
                return;
            }

            if (confirm('Remove this student from the queue?')) {
                fetch('ajax/accountant_remove_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `queue_id=${currentQueueId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshQueue();
                            resetStudentDetails();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error removing student:', error);
                        alert('Error removing student. Please try again.');
                    });
            }
        }

        function resetStudentDetails() {
            currentQueueId = null;
            currentStudentId = null;
            document.getElementById('no-student-message').style.display = 'block';
            document.getElementById('student-details').style.display = 'none';
        }

        function showReceipt(receiptData) {
            const receiptContent = document.getElementById('receipt-content');

            if (!receiptData) {
                receiptContent.innerHTML = '<p style="color: white; text-align: center;">Error generating receipt</p>';
            } else {
                receiptContent.innerHTML = `
                    <div class="receipt-header">
                        <h1>COLLEGE FEE RECEIPT</h1>
                        <p>${receiptData.college_name || 'College Name'}</p>
                        <p>${receiptData.college_address || 'College Address'}</p>
                    </div>
                    
                    <div class="receipt-details">
                        <table style="width: 100%;">
                            <tr>
                                <td><strong>Receipt No:</strong> ${receiptData.receipt_number || 'N/A'}</td>
                                <td><strong>Date:</strong> ${receiptData.payment_date || new Date().toLocaleDateString()}</td>
                            </tr>
                            <tr>
                                <td><strong>Student Name:</strong> ${receiptData.student_name || 'N/A'}</td>
                                <td><strong>Roll No:</strong> ${receiptData.roll_number || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Year & Branch:</strong> ${receiptData.year_branch || 'N/A'}</td>
                                <td><strong>Counter:</strong> ${receiptData.counter_name || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>

                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(receiptData.fees || []).map(fee => `
                                <tr>
                                    <td>${fee.fee_name || 'N/A'}</td>
                                    <td>₹${parseFloat(fee.amount_paid || 0).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>

                    <div class="receipt-total">
                        <table style="width: 100%;">
                            <tr>
                                <td><strong>Total Amount Paid:</strong></td>
                                <td><strong>₹${parseFloat(receiptData.total_amount || 0).toFixed(2)}</strong></td>
                            </tr>
                        </table>
                    </div>

                    <div class="receipt-footer">
                        <p>Thank you for your payment!</p>
                        <p>This is a computer generated receipt</p>
                    </div>
                `;
            }

            document.getElementById('receipt-modal').style.display = 'block';
        }

        function closeReceipt() {
            document.getElementById('receipt-modal').style.display = 'none';
        }

        function printReceipt() {
            const receiptContent = document.getElementById('receipt-content').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Receipt</title>
                    <style>
                        body { font-family: 'Courier New', monospace; padding: 20px; background: white; color: black; }
                        .receipt-header { text-align: center; margin-bottom: 20px; }
                        .receipt-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        .receipt-table th, .receipt-table td { padding: 8px; border-bottom: 1px dashed #000; }
                        .receipt-total { border-top: 2px solid #000; margin-top: 20px; padding-top: 10px; font-weight: bold; }
                        .receipt-footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>${receiptContent}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>

</html>