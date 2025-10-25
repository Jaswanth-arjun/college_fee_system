<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['register_email'])) {
    header('Location: register.php');
    exit;
}

require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/email_functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$email = $_SESSION['register_email'];
$student_name = $_SESSION['student_name'] ?? 'Student';

// Resend OTP functionality
if (isset($_POST['resend_otp'])) {
    $new_otp = generateOTP();

    // Delete old OTP
    $delete_sql = "DELETE FROM otp_verification WHERE email = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->execute([$email]);

    // Insert new OTP
    $otp_sql = "INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
    $otp_stmt = $conn->prepare($otp_sql);
    $otp_stmt->execute([$email, $new_otp]);

    // Send new OTP email
    $email_sent = sendOTPEmail($email, $new_otp, $student_name);

    if ($email_sent['success']) {
        $success = "New OTP has been sent to your email!";
    } else {
        $error = "Failed to send OTP email. Please try again.";
        // Store for demo
        $_SESSION['demo_otp'] = $new_otp;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['resend_otp'])) {
    $otp = clean_input($_POST['otp']);

    // Verify OTP with expiration check
    $sql = "SELECT * FROM otp_verification 
            WHERE email = ? 
            AND otp_code = ? 
            AND is_used = 0
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $otp]);
    $otp_data = $stmt->fetch();

    if ($otp_data) {
        try {
            $conn->beginTransaction();

            // Mark OTP as used
            $update_sql = "UPDATE otp_verification SET is_used = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$otp_data['id']]);

            // Mark student as verified (REMOVED verified_at since column doesn't exist)
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

                // Send welcome email
                sendWelcomeEmail($email, $student['full_name'], $student['roll_number']);
            }

            $conn->commit();

            // Get the complete student data after verification
            $student_sql = "SELECT * FROM students WHERE email = ?";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->execute([$email]);
            $student = $student_stmt->fetch();

            if ($student) {
                // Set student session for automatic login
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_roll_number'] = $student['roll_number'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_logged_in'] = true;

                // Clear registration session variables
                unset($_SESSION['register_email']);
                if (isset($_SESSION['demo_otp'])) {
                    unset($_SESSION['demo_otp']);
                }

                $_SESSION['success'] = "Registration successful! Welcome to your dashboard!";
                header('Location: dashboard_student.php');
                exit;
            } else {
                throw new Exception("Student record not found after verification");
            }

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Verification failed: " . $e->getMessage();
        }
    } else {
        $error = "Invalid or expired OTP! Please check the OTP and try again.";
    }
}

// For demo purposes - show the OTP if email failed
$demo_otp = isset($_SESSION['demo_otp']) ? $_SESSION['demo_otp'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - College Fee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .otp-container {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .otp-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .otp-body {
            padding: 30px;
        }

        .email-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #007bff;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
            text-align: center;
        }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e1e5ee;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .otp-input:focus {
            outline: none;
            border-color: #ff6b6b;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            transform: scale(1.05);
        }

        .otp-input.filled {
            border-color: #28a745;
            background: #f8fff9;
        }

        .btn-verify {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-resend {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-verify:hover {
            background: linear-gradient(135deg, #ee5a52, #e53e3e);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-resend:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .demo-otp {
            background: linear-gradient(135deg, #ffeaa7, #fab1a0);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #e17055;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #333;
            text-decoration: underline;
        }

        .timer {
            color: #ff6b6b;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .otp-body {
                padding: 20px;
            }

            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="otp-container">
        <div class="otp-header">
            <h2><i class="fas fa-shield-alt"></i> Verify Email</h2>
            <p>Enter the OTP sent to your email</p>
        </div>

        <div class="otp-body">
            <div class="email-info">
                <i class="fas fa-envelope"></i>
                <strong>Verifying:</strong> <?php echo htmlspecialchars($email); ?>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($demo_otp)): ?>
                <div class="demo-otp">
                    <strong><i class="fas fa-key"></i> DEMO OTP: <?php echo $demo_otp; ?></strong>
                    <small>Use this 6-digit code for verification (Email sending failed)</small>
                </div>
            <?php endif; ?>

            <form method="POST" id="otpForm">
                <div class="form-group">
                    <label>Enter the 6-digit verification code</label>
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" data-index="1" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="2" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="3" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="4" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="5" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="6" autocomplete="off">
                    </div>
                    <input type="hidden" name="otp" id="otp" value="<?php echo $demo_otp; ?>">
                </div>

                <button type="submit" class="btn-verify" id="verifyBtn">
                    <i class="fas fa-check-circle"></i>
                    Verify & Complete Registration
                </button>
            </form>

            <form method="POST">
                <button type="submit" name="resend_otp" class="btn-resend" id="resendBtn">
                    <i class="fas fa-redo"></i>
                    Resend OTP <span id="timer" class="timer"></span>
                </button>
            </form>

            <div class="back-link">
                <a href="register.php"><i class="fas fa-arrow-left"></i> Back to Registration</a>
            </div>
        </div>
    </div>

    <script>
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHiddenInput = document.getElementById('otp');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');
        const timerElement = document.getElementById('timer');

        let canResend = false;
        let countdown = 60;

        // Auto-focus first input
        otpInputs[0].focus();

        // OTP input handling
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;

                if (!/^\d*$/.test(value)) {
                    e.target.value = '';
                    return;
                }

                if (value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }

                    e.target.classList.add('filled');
                    updateHiddenOTP();

                    if (index === otpInputs.length - 1 && allInputsFilled()) {
                        document.getElementById('otpForm').submit();
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (input.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                    input.classList.remove('filled');
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6);
                if (/^\d{6}$/.test(pasteData)) {
                    pasteData.split('').forEach((char, charIndex) => {
                        if (charIndex < otpInputs.length) {
                            otpInputs[charIndex].value = char;
                            otpInputs[charIndex].classList.add('filled');
                        }
                    });
                    updateHiddenOTP();
                    otpInputs[5].focus();
                }
            });
        });

        function updateHiddenOTP() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpHiddenInput.value = otp;
        }

        function allInputsFilled() {
            return Array.from(otpInputs).every(input => input.value.length === 1);
        }

        // Auto-fill demo OTP if available
        const demoOTP = "<?php echo $demo_otp; ?>";
        if (demoOTP && demoOTP.length === 6) {
            setTimeout(() => {
                demoOTP.split('').forEach((char, index) => {
                    otpInputs[index].value = char;
                    otpInputs[index].classList.add('filled');
                });
                updateHiddenOTP();
            }, 500);
        }

        // Resend OTP timer
        function startTimer() {
            canResend = false;
            resendBtn.disabled = true;
            resendBtn.style.opacity = '0.6';

            const timerInterval = setInterval(() => {
                countdown--;
                timerElement.textContent = `(${countdown}s)`;

                if (countdown <= 0) {
                    clearInterval(timerInterval);
                    canResend = true;
                    resendBtn.disabled = false;
                    resendBtn.style.opacity = '1';
                    timerElement.textContent = '';
                    countdown = 60;
                }
            }, 1000);
        }

        // Start timer on page load
        startTimer();

        // Form submission
        document.getElementById('otpForm').addEventListener('submit', function (e) {
            if (!allInputsFilled()) {
                e.preventDefault();
                alert('Please enter the complete 6-digit OTP');
                return false;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            return true;
        });
    </script>
</body>

</html>

<?php
function assign_default_fees($conn, $student_id, $academic_year)
{
    $fee_sql = "SELECT * FROM fee_types WHERE is_active = 1";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->execute();
    $fee_types = $fee_stmt->fetchAll();

    foreach ($fee_types as $fee) {
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