<?php
// Check student authentication
require_once '../includes/auth.php';
check_student_auth();

$db = new Database();
$conn = $db->getConnection();

// Get student details and fee summary
$student_id = $_SESSION['student_id'];
$student = get_student_details($conn, $student_id);
$fee_summary = get_fee_summary($conn, $student_id);

// Get available counters
$counters = get_available_counters($conn);
?>

<div class="fee-payment-section">
    <h3>Fee Payment</h3>

    <!-- Fee Summary -->
    <div class="fee-summary">
        <h4>Your Fee Summary</h4>
        <table>
            <tr>
                <th>Fee Type</th>
                <th>Total Amount</th>
                <th>Paid Amount</th>
                <th>Remaining</th>
            </tr>
            <?php foreach ($fee_summary as $fee): ?>
                <tr>
                    <td><?php echo $fee['fee_type']; ?></td>
                    <td>₹<?php echo $fee['total_amount']; ?></td>
                    <td>₹<?php echo $fee['paid_amount']; ?></td>
                    <td>₹<?php echo $fee['remaining']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Counter Selection -->
    <div class="counter-selection">
        <h4>Select Counter</h4>
        <div class="counters-grid">
            <?php foreach ($counters as $counter): ?>
                <div class="counter-card" onclick="selectCounter(<?php echo $counter['id']; ?>)">
                    <h5><?php echo $counter['name']; ?></h5>
                    <p>Accepts: <?php echo $counter['fee_types']; ?></p>
                    <p>Location: <?php echo $counter['location']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Dynamic Fee Form (will be populated via JavaScript) -->
    <div id="fee-form-container" style="display:none;">
        <form id="payment-form">
            <div id="fee-selection"></div>
            <button type="button" onclick="joinQueue()">Join Queue</button>
        </form>
    </div>
</div>

<script>
    function selectCounter(counterId) {
        // AJAX call to get counter details and fee types
        fetch('get_counter_fees.php?counter_id=' + counterId)
            .then(response => response.json())
            .then(data => {
                displayFeeForm(data);
            });
    }

    function displayFeeForm(counterData) {
        // Show the form container
        document.getElementById('fee-form-container').style.display = 'block';

        // Build dynamic fee selection form
        let html = '<h4>Select Fees to Pay</h4>';

        counterData.fee_types.forEach(fee => {
            html += `
        <div class="fee-option">
            <input type="checkbox" name="fee_types[]" value="${fee.id}" 
                   onchange="toggleAmountInput(${fee.id})">
            <label>${fee.name} (Remaining: ₹${fee.remaining})</label>
            <input type="number" id="amount_${fee.id}" name="amounts[${fee.id}]" 
                   placeholder="Enter amount" min="1" max="${fee.remaining}" 
                   style="display:none;">
        </div>`;
        });

        document.getElementById('fee-selection').innerHTML = html;
    }

    function joinQueue() {
        // Submit form and join queue
        let formData = new FormData(document.getElementById('payment-form'));
        formData.append('counter_id', selectedCounterId);

        fetch('join_queue.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'queue_status.php';
                }
            });
    }
</script>