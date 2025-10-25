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
$accountant = get_accountant_details($conn, $accountant_id);
$counter = get_counter_details($conn, $counter_id);
$queue = get_current_queue($conn, $counter_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - College Fee System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h1 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header-info p {
            color: #666;
        }

        .counter-badge {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
        }

        /* Main Layout */
        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Queue Sidebar */
        .queue-sidebar {
            width: 350px;
            background: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .queue-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .queue-stats h3 {
            margin-bottom: 10px;
            color: #2c3e50;
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
        }

        .stat.waiting {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .stat.completed {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
        }

        .stat .number {
            font-size: 24px;
            font-weight: bold;
        }

        .stat .label {
            font-size: 12px;
            color: #666;
        }

        /* Queue List */
        .queue-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .queue-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .queue-item:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .queue-item.active {
            border-color: #28a745;
            background: #f8fff9;
        }

        .queue-item.processing {
            border-color: #ffc107;
            background: #fffdf6;
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
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        .student-details h4 {
            margin: 0;
            color: #2c3e50;
        }

        .student-details p {
            margin: 2px 0;
            color: #666;
            font-size: 12px;
        }

        .queue-position {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        /* Current Student Card */
        .current-student {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .current-student h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #f4f4f4;
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
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 32px;
            font-weight: bold;
        }

        .student-details-large h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .student-details-large p {
            margin: 2px 0;
            color: #666;
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
            border-bottom: 1px solid #e9ecef;
        }

        .fee-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }

        .amount-input {
            width: 120px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: right;
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
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* No Student Message */
        .no-student {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-student h3 {
            margin-bottom: 10px;
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
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }

        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }

        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        /* Receipt Styling */
        .receipt {
            font-family: 'Courier New', monospace;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            margin: 0;
            color: #2c3e50;
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
            border-bottom: 1px dashed #ddd;
        }

        .receipt-table th {
            text-align: left;
        }

        .receipt-total {
            border-top: 2px solid #000;
            margin-top: 20px;
            padding-top: 10px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="header-info">
            <h1>Accountant Dashboard</h1>
            <p>Welcome, <?php echo $accountant['full_name']; ?> | <?php echo $accountant['accountant_id']; ?></p>
        </div>
        <div class="counter-badge">
            <?php echo $counter['name']; ?> - <?php echo $counter['location']; ?>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Queue Sidebar -->
        <div class="queue-sidebar">
            <div class="queue-stats">
                <h3>Queue Statistics</h3>
                <div class="stats-grid">
                    <div class="stat waiting">
                        <div class="number" id="waiting-count">
                            <?php echo count(array_filter($queue, function ($q) {
                                return $q['status'] == 'waiting';
                            })); ?>
                        </div>
                        <div class="label">Waiting</div>
                    </div>
                    <div class="stat completed">
                        <div class="number" id="completed-count">0</div>
                        <div class="label">Completed Today</div>
                    </div>
                </div>
            </div>

            <h3>Current Queue</h3>
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
                                    <h4><?php echo $student['full_name']; ?></h4>
                                    <p><?php echo $student['roll_number']; ?></p>
                                    <span class="queue-position">Position: <?php echo $student['position']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="current-student" id="current-student-container">
                <div class="no-student" id="no-student-message">
                    <h3>No Student Selected</h3>
                    <p>Click on a student from the queue to start processing</p>
                </div>

                <div id="student-details" style="display: none;">
                    <h2>Process Payment</h2>

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
                        <h4>Selected Fees for Payment</h4>
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
                            Mark as Paid & Print Receipt
                        </button>
                        <button class="btn btn-warning" onclick="skipStudent()" id="skip-btn">
                            Skip (Move to End)
                        </button>
                        <button class="btn btn-danger" onclick="removeStudent()" id="remove-btn">
                            Remove from Queue
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="current-student">
                <h2>Recent Transactions</h2>
                <div id="recent-transactions">
                    <?php echo get_recent_accountant_transactions($conn, $accountant_id); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal" id="receipt-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Payment Receipt</h2>
                <button class="close" onclick="closeReceipt()">&times;</button>
            </div>
            <div class="receipt" id="receipt-content">
                <!-- Receipt content will be generated here -->
            </div>
            <div class="actions" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="printReceipt()">Print Receipt</button>
                <button class="btn" onclick="closeReceipt()" style="background: #6c757d; color: white;">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentQueueId = null;
        let currentStudentId = null;

        // Auto-refresh queue every 10 seconds
        setInterval(refreshQueue, 10000);

        function refreshQueue() {
            fetch('ajax/accountant_get_queue.php?counter_id=<?php echo $counter_id; ?>')
                .then(response => response.json())
                .then(data => {
                    updateQueueList(data.queue);
                    updateQueueStats(data.stats);
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
                <div class="queue-item" data-queue-id="${student.queue_id}" data-student-id="${student.student_id}" onclick="selectStudent(${student.queue_id}, ${student.student_id})">
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
        }

        function updateQueueStats(stats) {
            document.getElementById('waiting-count').textContent = stats.waiting;
            document.getElementById('completed-count').textContent = stats.completed_today;
        }

        function selectStudent(queueId, studentId) {
            currentQueueId = queueId;
            currentStudentId = studentId;

            // Update UI to show selected student
            document.querySelectorAll('.queue-item').forEach(item => {
                item.classList.remove('active', 'processing');
            });
            document.querySelector(`[data-queue-id="${queueId}"]`).classList.add('processing');

            // Show student details
            document.getElementById('no-student-message').style.display = 'none';
            document.getElementById('student-details').style.display = 'block';

            // Load student details
            fetch(`ajax/accountant_get_student_details.php?queue_id=${queueId}&student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudentDetails(data.student, data.fees);
                    }
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

            fees.forEach(fee => {
                totalAmount += parseFloat(fee.amount_to_pay);
                html += `
                <tr>
                    <td>${fee.fee_name}</td>
                    <td>₹${parseFloat(fee.total_amount).toFixed(2)}</td>
                    <td>₹${parseFloat(fee.paid_amount).toFixed(2)}</td>
                    <td>₹${parseFloat(fee.remaining_amount).toFixed(2)}</td>
                    <td>₹${parseFloat(fee.amount_to_pay).toFixed(2)}</td>
                </tr>`;
            });

            // Add total row
            html += `
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td colspan="4" style="text-align: right;">Total Amount:</td>
                <td>₹${totalAmount.toFixed(2)}</td>
            </tr>`;

            feeTableBody.innerHTML = html;
        }

        function processPayment() {
            if (!currentQueueId || !currentStudentId) {
                alert('Please select a student first!');
                return;
            }

            if (confirm('Mark this payment as completed and generate receipt?')) {
                fetch('ajax/accountant_process_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `queue_id=${currentQueueId}&student_id=${currentStudentId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showReceipt(data.receipt);
                            refreshQueue();
                            resetStudentDetails();
                        } else {
                            alert('Error: ' + data.message);
                        }
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
                            alert('Error: ' + data.message);
                        }
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
                            alert('Error: ' + data.message);
                        }
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
            receiptContent.innerHTML = `
                <div class="receipt-header">
                    <h1>COLLEGE FEE RECEIPT</h1>
                    <p>${receiptData.college_name}</p>
                    <p>${receiptData.college_address}</p>
                </div>
                
                <div class="receipt-details">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>Receipt No:</strong> ${receiptData.receipt_number}</td>
                            <td><strong>Date:</strong> ${receiptData.payment_date}</td>
                        </tr>
                        <tr>
                            <td><strong>Student Name:</strong> ${receiptData.student_name}</td>
                            <td><strong>Roll No:</strong> ${receiptData.roll_number}</td>
                        </tr>
                        <tr>
                            <td><strong>Year & Branch:</strong> ${receiptData.year_branch}</td>
                            <td><strong>Counter:</strong> ${receiptData.counter_name}</td>
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
                        ${receiptData.fees.map(fee => `
                            <tr>
                                <td>${fee.fee_name}</td>
                                <td>₹${parseFloat(fee.amount_paid).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="receipt-total">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>Total Amount Paid:</strong></td>
                            <td><strong>₹${parseFloat(receiptData.total_amount).toFixed(2)}</strong></td>
                        </tr>
                    </table>
                </div>

                <div class="receipt-footer">
                    <p>Thank you for your payment!</p>
                    <p>This is a computer generated receipt</p>
                </div>
            `;

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
                        body { font-family: 'Courier New', monospace; padding: 20px; }
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