<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/email_functions.php'; // Include email functions

$db = new Database();
$conn = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $roll_number = clean_input($_POST['roll_number']);
    $email = clean_input($_POST['email']);
    $academic_year = clean_input($_POST['academic_year']);
    $branch = clean_input($_POST['branch']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $phone = clean_input($_POST['phone']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check_sql = "SELECT id FROM students WHERE roll_number = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$roll_number, $email]);

        if ($check_stmt->rowCount() > 0) {
            $error = "Roll number or email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $conn->beginTransaction();

                // Insert student
                $sql = "INSERT INTO students (roll_number, full_name, email, academic_year, branch, password, phone) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$roll_number, $full_name, $email, $academic_year, $branch, $hashed_password, $phone]);

                $student_id = $conn->lastInsertId();

                // Generate OTP
                $otp = generateOTP();

                // Delete any existing OTP for this email
                $delete_sql = "DELETE FROM otp_verification WHERE email = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->execute([$email]);

                // Insert new OTP with expiration
                $otp_sql = "INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
                $otp_stmt = $conn->prepare($otp_sql);
                $otp_stmt->execute([$email, $otp]);

                $conn->commit();

                // Send OTP email
                $email_sent = sendOTPEmail($email, $otp, $full_name);

                if ($email_sent) {
                    // Store in session for verification
                    $_SESSION['register_email'] = $email;
                    $_SESSION['student_name'] = $full_name;
                    $_SESSION['email_sent'] = true;

                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $error = "Registration completed but failed to send OTP email. Please contact support.";
                    // You might want to store the OTP in session for manual verification
                    $_SESSION['demo_otp'] = $otp;
                    $_SESSION['register_email'] = $email;
                    header('Location: verify_otp.php');
                    exit;
                }

            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - College Fee System</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            background:
                radial-gradient(circle at 10% 20%, rgba(168, 255, 120, 0.3) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 154, 158, 0.3) 0%, transparent 20%),
                linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            padding: 20px;
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

        .register-container {
            width: 100%;
            max-width: 800px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
            position: relative;
            z-index: 1;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
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

        .register-header {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .register-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .register-body {
            padding: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: white;
            font-size: 14px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            z-index: 2;
        }

        input,
        select {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        select option {
            background: #6a11cb;
            color: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #a8ff78;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(168, 255, 120, 0.2);
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
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
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(168, 255, 120, 0.4);
        }

        .error-message {
            background: rgba(248, 215, 218, 0.2);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(245, 198, 203, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
            backdrop-filter: blur(5px);
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

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.8);
        }

        .login-link a {
            color: #a8ff78;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #78ffd6;
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 5px;
        }

        .strength-weak {
            background: #ff6b6b;
            width: 33%;
        }

        .strength-medium {
            background: #ffd93d;
            width: 66%;
        }

        .strength-strong {
            background: #a8ff78;
            width: 100%;
        }

        .password-requirements {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 2px;
        }

        .requirement.met {
            color: #a8ff78;
        }

        .requirement i {
            font-size: 10px;
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

            .form-row {
                grid-template-columns: 1fr;
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

    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> Student Registration</h2>
            <p>Create your college fee account</p>
        </div>

        <div class="register-body">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registrationForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="full_name" name="full_name"
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="roll_number">Roll Number</label>
                        <div class="input-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="roll_number" name="roll_number"
                                value="<?php echo isset($_POST['roll_number']) ? htmlspecialchars($_POST['roll_number']) : ''; ?>"
                                placeholder="Enter roll number" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">College Email</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                placeholder="Enter college email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                placeholder="Enter phone number" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <div class="input-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <select id="academic_year" name="academic_year" required>
                                <option value="">Select Year</option>
                                <option value="1st Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <div class="input-icon">
                            <i class="fas fa-code-branch"></i>
                            <select id="branch" name="branch" required>
                                <option value="">Select Branch</option>
                                <option value="CSE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'CSE') ? 'selected' : ''; ?>>Computer Science</option>
                                <option value="ECE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'ECE') ? 'selected' : ''; ?>>Electronics</option>
                                <option value="ME" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'ME') ? 'selected' : ''; ?>>Mechanical</option>
                                <option value="CE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'CE') ? 'selected' : ''; ?>>Civil</option>
                                <option value="EE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'EE') ? 'selected' : ''; ?>>Electrical</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create password" required
                                minlength="6">
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement" id="reqLength"><i class="fas fa-circle"></i> At least 6 characters
                            </div>
                            <div class="requirement" id="reqUpper"><i class="fas fa-circle"></i> Contains uppercase
                            </div>
                            <div class="requirement" id="reqLower"><i class="fas fa-circle"></i> Contains lowercase
                            </div>
                            <div class="requirement" id="reqNumber"><i class="fas fa-circle"></i> Contains number</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Confirm password" required minlength="6">
                        </div>
                        <div id="passwordMatch"
                            style="font-size: 12px; margin-top: 5px; color: rgba(255, 255, 255, 0.8);"></div>
                    </div>
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login Here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password validation script
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const passwordMatch = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');

        passwordInput.addEventListener('input', function () {
            const password = this.value;
            let strength = 0;

            const hasMinLength = password.length >= 6;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);

            document.getElementById('reqLength').className = hasMinLength ? 'requirement met' : 'requirement';
            document.getElementById('reqUpper').className = hasUpperCase ? 'requirement met' : 'requirement';
            document.getElementById('reqLower').className = hasLowerCase ? 'requirement met' : 'requirement';
            document.getElementById('reqNumber').className = hasNumber ? 'requirement met' : 'requirement';

            if (hasMinLength) strength++;
            if (hasUpperCase) strength++;
            if (hasLowerCase) strength++;
            if (hasNumber) strength++;

            strengthBar.className = 'strength-bar';
            if (strength > 0) {
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength === 3) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            }
        });

        confirmPasswordInput.addEventListener('input', function () {
            const password = passwordInput.value;
            const confirmPassword = this.value;

            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                passwordMatch.style.color = '';
            } else if (password === confirmPassword) {
                passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                passwordMatch.style.color = '#a8ff78';
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                passwordMatch.style.color = '#ff6b6b';
            }
        });

        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                passwordMatch.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fix password mismatch before submitting';
                passwordMatch.style.color = '#ff6b6b';
                confirmPasswordInput.focus();
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            return true;
        });
    </script>
</body>

</html>