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
        /* Keep your existing CSS styles from the previous version */
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

        .register-container {
            width: 100%;
            max-width: 800px;
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

        .register-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px 20px;
            text-align: center;
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
            color: #333;
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
            color: #666;
            z-index: 2;
        }

        input,
        select {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5ee;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #28a745;
            background: white;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745, #20c997);
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
            margin-top: 10px;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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
            color: #666;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 5px;
            background: #e9ecef;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 5px;
        }

        .strength-weak {
            background: #dc3545;
            width: 33%;
        }

        .strength-medium {
            background: #ffc107;
            width: 66%;
        }

        .strength-strong {
            background: #28a745;
            width: 100%;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 2px;
        }

        .requirement.met {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
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
                        <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
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
        // Password validation script (keep your existing JavaScript)
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
                passwordMatch.style.color = '#28a745';
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                passwordMatch.style.color = '#dc3545';
            }
        });

        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                passwordMatch.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fix password mismatch before submitting';
                passwordMatch.style.color = '#dc3545';
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