<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];
$student = get_student_details($conn, $student_id);
$fee_summary = get_fee_summary($conn, $student_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - College Fee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            height: 100vh;
            padding: 25px;
            position: fixed;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-img img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .student-info {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .student-info h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-info h3 i {
            color: #667eea;
        }

        .student-info p {
            margin: 8px 0;
            color: #5a6c7d;
            display: flex;
            justify-content: space-between;
        }

        .student-info p strong {
            color: #2c3e50;
        }

        /* Navigation */
        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin: 8px 0;
        }

        .nav-links a {
            color: #5a6c7d;
            text-decoration: none;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-links a i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 320px;
            padding: 30px;
            width: calc(100% - 320px);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: #5a6c7d;
            font-size: 1.1rem;
        }

        /* Fee Summary */
        .fee-summary {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .fee-summary h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fee-summary h2 i {
            color: #667eea;
        }

        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .fee-table th,
        .fee-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }

        .fee-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .fee-table tr:hover {
            background: #f8f9fa;
        }

        .amount-paid {
            color: #28a745;
            font-weight: 600;
        }

        .amount-remaining {
            color: #dc3545;
            font-weight: 600;
        }

        /* Counters Grid */
        .counters-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .counters-section h2 {
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .counters-section h2 i {
            color: #667eea;
        }

        .counters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .counter-card {
            background: white;
            border: 2px solid #eaeaea;
            padding: 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .counter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .counter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .counter-card h3 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 1.3rem;
        }

        .counter-card p {
            margin: 8px 0;
            color: #5a6c7d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .counter-card p i {
            color: #667eea;
            width: 16px;
        }

        /* Fee Form */
        #fee-form-container {
            display: none;
            margin-top: 25px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #667eea;
        }

        .fee-option {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .fee-option:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .amount-input {
            margin-top: 10px;
            padding: 10px;
            width: 200px;
            border: 2px solid #eaeaea;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .amount-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Queue Status */
        .queue-status {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-top: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .queue-animation {
            margin: 30px 0;
            padding: 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 3px solid #eaeaea;
            position: relative;
            overflow: hidden;
        }

        .queue-line {
            position: relative;
            height: 120px;
            background: repeating-linear-gradient(90deg,
                    transparent,
                    transparent 40px,
                    #667eea 40px,
                    #667eea 42px);
            margin: 20px 0;
            border-bottom: 3px solid #667eea;
        }

        .walking-person {
            position: absolute;
            bottom: 10px;
            font-size: 3rem;
            animation: walk 2s linear infinite;
            transform-origin: bottom;
        }

        .person-male {
            color: #3498db;
        }

        .person-female {
            color: #e74c3c;
        }

        .current-user {
            color: #2ecc71;
            font-size: 3.5rem;
            z-index: 10;
            animation: bounce 1s ease-in-out infinite;
        }

        @keyframes walk {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            25% {
                transform: translateY(-5px) rotate(-2deg);
            }

            50% {
                transform: translateY(0px) rotate(0deg);
            }

            75% {
                transform: translateY(-3px) rotate(2deg);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .queue-info {
            margin: 25px 0;
            padding: 20px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 12px;
            color: white;
        }

        .queue-info h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .position-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .wait-time {
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .queue-actions {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 280px;
            }

            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .counters-grid {
                grid-template-columns: 1fr;
            }

            .queue-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile-img">
                <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                    <img src="<?php echo $student['profile_picture']; ?>?t=<?php echo time(); ?>" alt="Profile Picture">
                <?php else: ?>
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <h3><?php echo $student['full_name']; ?></h3>
            <p><?php echo $student['roll_number']; ?></p>
        </div>

        <div class="student-info">
            <h3><i class="fas fa-user-graduate"></i> Student Details</h3>
            <p><strong>Year:</strong> <span><?php echo $student['academic_year']; ?></span></p>
            <p><strong>Branch:</strong> <span><?php echo $student['branch']; ?></span></p>
            <p><strong>Email:</strong> <span><?php echo $student['email']; ?></span></p>
            <p><strong>Phone:</strong> <span><?php echo $student['phone']; ?></span></p>
        </div>

        <ul class="nav-links">
            <li><a href="#" class="active" onclick="showSection('fee-payment')"><i class="fas fa-credit-card"></i> Fee
                    Payment</a></li>
            <li><a href="#" onclick="showSection('queue-status')"><i class="fas fa-clock"></i> Queue Status</a></li>
            <li><a href="student/profile.php"><i class="fas fa-user-edit"></i> Update Profile</a></li>
            <li><a href="student/change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Student Dashboard</h1>
            <p>Welcome back, <?php echo $student['full_name']; ?>! Ready to manage your fees?</p>
        </div>

        <!-- Fee Summary -->
        <div class="fee-summary">
            <h2><i class="fas fa-chart-bar"></i> Your Fee Summary</h2>
            <div class="table-container">
                <table class="fee-table">
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fee_summary as $fee): ?>
                            <tr>
                                <td><?php echo $fee['name']; ?></td>
                                <td>₹<?php echo number_format($fee['total_amount'], 2); ?></td>
                                <td class="amount-paid">₹<?php echo number_format($fee['paid_amount'], 2); ?></td>
                                <td class="amount-remaining">₹<?php echo number_format($fee['remaining_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fee Payment Section -->
        <div id="fee-payment" class="section">
            <div class="counters-section">
                <h2><i class="fas fa-desktop"></i> Select Counter for Payment</h2>
                <div class="counters-grid" id="counters-grid">
                    <!-- Counters will be loaded via JavaScript -->
                </div>

                <div id="fee-form-container">
                    <h3 style="color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-money-check"></i> Select Fees to Pay
                    </h3>
                    <div id="fee-selection"></div>
                    <button onclick="joinQueue()" class="btn btn-success" style="margin-top: 20px;">
                        <i class="fas fa-sign-in-alt"></i> Join Queue
                    </button>
                </div>
            </div>
        </div>

        <!-- Queue Status Section -->
        <div id="queue-status" class="section" style="display: none;">
            <div class="queue-status" id="queue-status-display">
                <h2
                    style="color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-clock"></i> Your Queue Status
                </h2>
                <p style="color: #5a6c7d; margin-bottom: 20px;">Real-time updates on your position</p>

                <!-- Queue Animation -->
                <div class="queue-animation">
                    <div class="queue-line" id="queue-line">
                        <!-- Walking people will be dynamically added here -->
                    </div>
                </div>

                <!-- Queue Information -->
                <div class="queue-info">
                    <h3><i class="fas fa-user-clock"></i> Current Position</h3>
                    <div class="position-number" id="queue-position">--</div>
                    <div class="wait-time" id="queue-wait-time">Calculating wait time...</div>
                    <div style="margin-top: 15px; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> Average processing time: 2-3 minutes per student
                    </div>
                </div>

                <!-- Queue Actions -->
                <div class="queue-actions">
                    <button onclick="refreshQueueStatus()" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Refresh Status
                    </button>
                    <button onclick="leaveQueue()" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Leave Queue
                    </button>
                </div>

                <!-- Last Updated -->
                <div style="margin-top: 20px; color: #5a6c7d; font-size: 0.9rem;">
                    <i class="fas fa-clock"></i> Last updated: <span id="last-updated">Just now</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedCounterId = null;
        let queueInterval = null;

        // Load counters on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadCounters();
            checkCurrentQueue();
        });

        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName);
            if (targetSection) {
                targetSection.style.display = 'block';
            }

            // Update active nav link
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function loadCounters() {
            fetch('ajax/get_counters.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const countersGrid = document.getElementById('counters-grid');
                    countersGrid.innerHTML = '';

                    if (data.length === 0) {
                        countersGrid.innerHTML = '<p style="text-align: center; color: #5a6c7d; padding: 40px;">No counters available at the moment. Please check back later.</p>';
                        return;
                    }

                    data.forEach(counter => {
                        const counterCard = document.createElement('div');
                        counterCard.className = 'counter-card';
                        counterCard.innerHTML = `
                            <h3><i class="fas fa-desktop"></i> ${counter.name}</h3>
                            <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${counter.location}</p>
                            <p><i class="fas fa-money-bill-wave"></i> <strong>Accepts:</strong> ${counter.fee_types || 'No fees assigned'}</p>
                            <p><i class="fas fa-circle"></i> <strong>Status:</strong> <span style="color: #28a745;">Active</span></p>
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <small style="color: #5a6c7d;">Click to select this counter</small>
                            </div>
                        `;
                        counterCard.onclick = () => selectCounter(counter.id);
                        countersGrid.appendChild(counterCard);
                    });
                })
                .catch(error => {
                    console.error('Error loading counters:', error);
                    document.getElementById('counters-grid').innerHTML = '<p style="text-align: center; color: #dc3545; padding: 20px;"><i class="fas fa-exclamation-triangle"></i> Error loading counters. Please refresh the page.</p>';
                });
        }

        function selectCounter(counterId) {
            selectedCounterId = counterId;

            fetch(`ajax/get_counter_fees.php?counter_id=${counterId}&student_id=<?php echo $student_id; ?>`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    displayFeeForm(data);
                })
                .catch(error => {
                    console.error('Error loading counter fees:', error);
                    alert('Error loading fee information: ' + error.message);
                });
        }

        function displayFeeForm(counterData) {
            const container = document.getElementById('fee-form-container');
            const feeSelection = document.getElementById('fee-selection');

            container.style.display = 'block';

            let html = '';
            if (counterData.feeTypes && counterData.feeTypes.length > 0) {
                counterData.feeTypes.forEach(fee => {
                    if (fee.remaining_amount > 0) {
                        html += `
                            <div class="fee-option">
                                <input type="checkbox" name="fee_types[]" value="${fee.fee_type_id}" 
                                       onchange="toggleAmountInput(${fee.fee_type_id}, ${fee.remaining_amount})"
                                       style="margin-right: 10px;">
                                <label style="font-weight: 600; color: #2c3e50;">${fee.fee_name}</label>
                                <div style="color: #5a6c7d; margin: 5px 0 0 25px;">
                                    Remaining Balance: <strong style="color: #dc3545;">₹${fee.remaining_amount}</strong>
                                </div>
                                <input type="number" id="amount_${fee.fee_type_id}" name="amounts[${fee.fee_type_id}]" 
                                       placeholder="Enter amount to pay" min="1" max="${fee.remaining_amount}" 
                                       class="amount-input" style="display:none;">
                            </div>`;
                    }
                });
            }

            feeSelection.innerHTML = html || '<p style="text-align: center; color: #5a6c7d; padding: 20px;">No pending fees available for this counter.</p>';
        }

        function toggleAmountInput(feeTypeId, maxAmount) {
            const amountInput = document.getElementById('amount_' + feeTypeId);
            const checkbox = document.querySelector(`input[value="${feeTypeId}"]`);

            if (checkbox.checked) {
                amountInput.style.display = 'block';
                amountInput.value = maxAmount;
            } else {
                amountInput.style.display = 'none';
                amountInput.value = '';
            }
        }

        function joinQueue() {
            const selectedFees = [];
            const checkboxes = document.querySelectorAll('input[name="fee_types[]"]:checked');

            if (checkboxes.length === 0) {
                alert('Please select at least one fee type!');
                return;
            }

            checkboxes.forEach(checkbox => {
                const feeTypeId = checkbox.value;
                const amountInput = document.getElementById('amount_' + feeTypeId);
                const amount = amountInput ? amountInput.value : 0;

                if (amount > 0) {
                    selectedFees.push({
                        fee_type_id: feeTypeId,
                        amount: parseFloat(amount)
                    });
                }
            });

            if (selectedFees.length === 0) {
                alert('Please enter amount for selected fees!');
                return;
            }

            if (!selectedCounterId) {
                alert('Please select a counter first!');
                return;
            }

            const formData = new FormData();
            formData.append('counter_id', selectedCounterId);
            formData.append('fee_types', JSON.stringify(selectedFees));

            // Show loading state
            const joinBtn = document.querySelector('button[onclick="joinQueue()"]');
            const originalText = joinBtn.innerHTML;
            joinBtn.disabled = true;
            joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining Queue...';

            fetch('ajax/join_queue.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showSection('queue-status');
                        startQueueUpdates();
                    } else {
                        alert(data.message || 'Failed to join queue. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error joining queue:', error);
                    alert('Error joining queue. Please check console for details and try again.');
                })
                .finally(() => {
                    joinBtn.disabled = false;
                    joinBtn.innerHTML = originalText;
                });
        }

        function checkCurrentQueue() {
            fetch('ajax/check_queue.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.in_queue) {
                        showSection('queue-status');
                        startQueueUpdates();
                    }
                })
                .catch(error => {
                    console.error('Error checking queue:', error);
                });
        }

        function startQueueUpdates() {
            // Clear any existing interval
            if (queueInterval) {
                clearInterval(queueInterval);
            }

            // Update immediately and then every 3 seconds
            refreshQueueStatus();
            queueInterval = setInterval(refreshQueueStatus, 3000);
        }

        function refreshQueueStatus() {
            fetch('ajax/get_queue_status.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        document.getElementById('queue-position').textContent = '--';
                        document.getElementById('queue-wait-time').textContent = 'Not in queue';
                        // Stop updating if not in queue
                        if (queueInterval) {
                            clearInterval(queueInterval);
                            queueInterval = null;
                        }
                    } else {
                        document.getElementById('queue-position').textContent = data.position;
                        document.getElementById('queue-wait-time').textContent = `Estimated wait: ${data.wait_time} minutes`;
                        updateQueueAnimation(data.position, data.total_in_queue);
                        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                    }
                })
                .catch(error => {
                    console.error('Error refreshing queue status:', error);
                    document.getElementById('queue-position').textContent = '--';
                    document.getElementById('queue-wait-time').textContent = 'Error loading status';
                });
        }

        function updateQueueAnimation(position, totalInQueue) {
            const queueLine = document.getElementById('queue-line');
            queueLine.innerHTML = '';

            // Create walking people animation
            const totalPeople = Math.min(totalInQueue, 10); // Show max 10 people
            const currentUserIndex = position - 1;

            for (let i = 0; i < totalPeople; i++) {
                const person = document.createElement('div');
                const isCurrentUser = i === currentUserIndex;
                const isMale = Math.random() > 0.5;

                person.className = `walking-person ${isCurrentUser ? 'current-user' : isMale ? 'person-male' : 'person-female'}`;
                person.style.left = `${(i / (totalPeople - 1)) * 90}%`;
                person.style.animationDelay = `${i * 0.2}s`;

                if (isCurrentUser) {
                    person.innerHTML = '<i class="fas fa-user" title="You"></i>';
                } else {
                    person.innerHTML = isMale ? '<i class="fas fa-male"></i>' : '<i class="fas fa-female"></i>';
                }

                queueLine.appendChild(person);
            }

            // Add counter at the end
            const counter = document.createElement('div');
            counter.style.position = 'absolute';
            counter.style.right = '20px';
            counter.style.bottom = '10px';
            counter.style.fontSize = '2rem';
            counter.style.color = '#28a745';
            counter.innerHTML = '<i class="fas fa-desktop" title="Counter"></i>';
            queueLine.appendChild(counter);
        }

        function leaveQueue() {
            if (confirm('Are you sure you want to leave the queue? Your position will be lost.')) {
                fetch('ajax/leave_queue.php', { method: 'POST' })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Stop queue updates
                            if (queueInterval) {
                                clearInterval(queueInterval);
                                queueInterval = null;
                            }
                            showSection('fee-payment');
                            loadCounters();
                            // Hide fee form
                            document.getElementById('fee-form-container').style.display = 'none';
                            alert('You have left the queue successfully.');
                        } else {
                            alert('Failed to leave queue. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error leaving queue:', error);
                        alert('Error leaving queue. Please try again.');
                    });
            }
        }
    </script>
</body>

</html>