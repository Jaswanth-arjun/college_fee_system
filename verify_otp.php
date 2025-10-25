<?php
session_start();

if (!isset($_SESSION['register_email'])) {
    header('Location: register.php');
    exit;
}

require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = clean_input($_POST['otp']);
    $email = $_SESSION['register_email'];

    // SIMPLIFIED OTP VERIFICATION - Remove expiration check for now
    $sql = "SELECT * FROM otp_verification 
            WHERE email = ? 
            AND otp_code = ? 
            AND is_used = 0
            ORDER BY created_at DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $otp]);
    $otp_data = $stmt->fetch();

    if ($otp_data) {
        // Mark OTP as used
        $update_sql = "UPDATE otp_verification SET is_used = 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$otp_data['id']]);

        // Mark student as verified
        $student_sql = "UPDATE students SET email_verified = 1 WHERE email = ?";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->execute([$email]);

        // Get student details
        $student_info_sql = "SELECT * FROM students WHERE email = ?";
        $student_info_stmt = $conn->prepare($student_info_sql);
        $student_info_stmt->execute([$email]);
        $student = $student_info_stmt->fetch();

        // Assign default fees to student
        if ($student) {
            assign_default_fees($conn, $student['id'], $student['academic_year']);
        }

        // Clear session
        unset($_SESSION['register_email']);
        if (isset($_SESSION['demo_otp'])) {
            unset($_SESSION['demo_otp']);
        }

        $_SESSION['success'] = "Registration successful! Please login with your roll number and password.";
        header('Location: login.php');
        exit;
    } else {
        $error = "Invalid OTP! Please check the OTP and try again.";

        // Debug: Show what OTPs exist for this email
        $debug_sql = "SELECT * FROM otp_verification WHERE email = ? ORDER BY created_at DESC";
        $debug_stmt = $conn->prepare($debug_sql);
        $debug_stmt->execute([$email]);
        $all_otps = $debug_stmt->fetchAll();

        if (empty($all_otps)) {
            $error .= " No OTP found for this email. Please register again.";
        }
    }
}

// For demo purposes - show the OTP
$demo_otp = isset($_SESSION['demo_otp']) ? $_SESSION['demo_otp'] : '';
$register_email = isset($_SESSION['register_email']) ? $_SESSION['register_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            letter-spacing: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .demo-otp {
            background: #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
        }

        .email-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 style="text-align: center;">Verify Email</h2>

        <div class="email-info">
            <strong>Verifying:</strong> <?php echo htmlspecialchars($register_email); ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($demo_otp)): ?>
            <div class="demo-otp">
                <strong>Use this OTP: <?php echo $demo_otp; ?></strong><br>
                <small>Enter the 6-digit code below</small>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Enter 6-digit OTP:</label>
                <input type="text" name="otp" placeholder="000000" required maxlength="6" pattern="[0-9]{6}"
                    title="Please enter exactly 6 digits" value="<?php echo $demo_otp; ?>">
            </div>

            <button type="submit">Verify OTP & Complete Registration</button>
        </form>

        <p style="text-align: center; margin-top: 15px;">
            <a href="register.php">Back to Registration</a>
        </p>
    </div>

    <script>
        // Auto-focus on OTP input
        document.querySelector('input[name="otp"]').focus();

        // Auto-submit when 6 digits are entered
        const otpInput = document.querySelector('input[name="otp"]');
        otpInput.addEventListener('input', function () {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>

</html>

<?php
function assign_default_fees($conn, $student_id, $academic_year)
{
    // Get all active fee types
    $fee_sql = "SELECT * FROM fee_types WHERE is_active = 1";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->execute();
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
?>