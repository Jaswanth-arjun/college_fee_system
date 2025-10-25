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
            width: 300px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #34495e;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .student-info {
            margin-bottom: 20px;
        }

        .student-info h3 {
            margin-bottom: 10px;
            color: #ecf0f1;
        }

        .student-info p {
            margin: 5px 0;
            color: #bdc3c7;
        }

        /* Navigation */
        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin: 10px 0;
        }

        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 5px;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #34495e;
        }

        /* Main Content */
        .main-content {
            margin-left: 300px;
            padding: 20px;
            width: calc(100% - 300px);
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #2c3e50;
        }

        /* Fee Summary */
        .fee-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .fee-summary h2 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fee-table th,
        .fee-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .fee-table th {
            background: #f8f9fa;
        }

        .amount-paid {
            color: #28a745;
        }

        .amount-remaining {
            color: #dc3545;
        }

        /* Counters Grid */
        .counters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .counters-section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .counters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .counter-card {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .counter-card:hover {
            border-color: #007bff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .counter-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .counter-card p {
            margin: 5px 0;
            color: #666;
        }

        /* Fee Form */
        #fee-form-container {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .fee-option {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .amount-input {
            margin-top: 5px;
            padding: 8px;
            width: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Queue Status */
        .queue-status {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }

        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- In the sidebar section of dashboard_student.php -->
        <div class="profile">
            <div class="profile-img">
                <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                    <img src="<?php echo $student['profile_picture']; ?>?t=<?php echo time(); ?>" alt="Profile Picture"
                        style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <h3><?php echo $student['full_name']; ?></h3>
            <p><?php echo $student['roll_number']; ?></p>
        </div>

        <div class="student-info">
            <h3>Student Details</h3>
            <p><strong>Year:</strong> <?php echo $student['academic_year']; ?></p>
            <p><strong>Branch:</strong> <?php echo $student['branch']; ?></p>
            <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
            <p><strong>Phone:</strong> <?php echo $student['phone']; ?></p>
        </div>

        <ul class="nav-links">
            <li><a href="#" class="active" onclick="showSection('fee-payment')">Fee Payment</a></li>
            <li><a href="#" onclick="showSection('queue-status')">Queue Status</a></li>
            <li><a href="student/profile.php">Update Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Student Dashboard</h1>
            <p>Welcome back, <?php echo $student['full_name']; ?>!</p>
        </div>

        <!-- Fee Summary -->
        <div class="fee-summary">
            <h2>Your Fee Summary</h2>
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

        <!-- Fee Payment Section -->
        <div id="fee-payment" class="section">
            <div class="counters-section">
                <h2>Select Counter for Payment</h2>
                <div class="counters-grid" id="counters-grid">
                    <!-- Counters will be loaded via JavaScript -->
                </div>

                <div id="fee-form-container">
                    <h3>Select Fees to Pay</h3>
                    <div id="fee-selection"></div>
                    <button onclick="joinQueue()"
                        style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;">Join
                        Queue</button>
                </div>
            </div>
        </div>

        <!-- Queue Status Section -->
        <div id="queue-status" class="section" style="display: none;">
            <div class="queue-status" id="queue-status-display">
                <h2>Your Queue Status</h2>
                <div class="loader"></div>
                <p id="queue-position">Loading your position...</p>
                <p id="queue-wait-time">Calculating wait time...</p>
                <button onclick="leaveQueue()"
                    style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;">Leave
                    Queue</button>
                <button onclick="refreshQueueStatus()"
                    style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px; margin-left: 10px;">Refresh</button>
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
                        countersGrid.innerHTML = '<p>No counters available at the moment.</p>';
                        return;
                    }

                    data.forEach(counter => {
                        const counterCard = document.createElement('div');
                        counterCard.className = 'counter-card';
                        counterCard.innerHTML = `
                        <h3>${counter.name}</h3>
                        <p><strong>Location:</strong> ${counter.location}</p>
                        <p><strong>Accepts:</strong> ${counter.fee_types || 'No fees assigned'}</p>
                        <p><strong>Status:</strong> <span style="color: #28a745;">Active</span></p>
                    `;
                        counterCard.onclick = () => selectCounter(counter.id);
                        countersGrid.appendChild(counterCard);
                    });
                })
                .catch(error => {
                    console.error('Error loading counters:', error);
                    document.getElementById('counters-grid').innerHTML = '<p class="error">Error loading counters. Please refresh the page.</p>';
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
                               onchange="toggleAmountInput(${fee.fee_type_id}, ${fee.remaining_amount})">
                        <label>${fee.fee_name} (Remaining: ₹${fee.remaining_amount})</label>
                        <input type="number" id="amount_${fee.fee_type_id}" name="amounts[${fee.fee_type_id}]" 
                               placeholder="Enter amount" min="1" max="${fee.remaining_amount}" 
                               class="amount-input" style="display:none;">
                    </div>`;
                    }
                });
            }

            feeSelection.innerHTML = html || '<p>No pending fees for this counter.</p>';
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
            const originalText = joinBtn.textContent;
            joinBtn.disabled = true;
            joinBtn.textContent = 'Joining Queue...';

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
                    joinBtn.textContent = originalText;
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

            // Update immediately and then every 5 seconds
            refreshQueueStatus();
            queueInterval = setInterval(refreshQueueStatus, 5000);
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
                        document.getElementById('queue-position').textContent = 'Not in queue';
                        document.getElementById('queue-wait-time').textContent = '';
                        // Stop updating if not in queue
                        if (queueInterval) {
                            clearInterval(queueInterval);
                            queueInterval = null;
                        }
                    } else {
                        document.getElementById('queue-position').textContent = `Your position: ${data.position} of ${data.total_in_queue}`;
                        document.getElementById('queue-wait-time').textContent = `Estimated wait time: ${data.wait_time} minutes`;
                    }
                })
                .catch(error => {
                    console.error('Error refreshing queue status:', error);
                    document.getElementById('queue-position').textContent = 'Error loading queue status';
                    document.getElementById('queue-wait-time').textContent = '';
                });
        }

        function leaveQueue() {
            if (confirm('Are you sure you want to leave the queue?')) {
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