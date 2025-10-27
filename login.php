<?php
// Remove session_start() from here since it's already in auth.php
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // This contains session_start()

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $user_type = clean_input($_POST['user_type']);

    // Now these functions are available from includes/functions.php
    switch ($user_type) {
        case 'student':
            $user = validate_student_login($conn, $username, $password);
            if ($user) {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['roll_number'] = $user['roll_number'];
                header('Location: dashboard_student.php');
                exit;
            }
            break;

        case 'admin':
            $user = validate_admin_login($conn, $username, $password);
            if ($user) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['user_type'] = 'admin';
                header('Location: dashboard_admin.php');
                exit;
            }
            break;

        case 'accountant':
            $user = validate_accountant_login($conn, $username, $password);
            if ($user) {
                $_SESSION['accountant_id'] = $user['id'];
                $_SESSION['user_type'] = 'accountant';
                $_SESSION['counter_id'] = $user['counter_id'];
                header('Location: dashboard_accountant.php');
                exit;
            }
            break;
    }

    $error = "Invalid login credentials!";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Fee System</title>
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

        .login-container {
            width: 100%;
            max-width: 450px;
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

        .login-container::before {
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

        .login-header {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .login-body {
            padding: 30px;
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

        input:focus,
        select:focus {
            outline: none;
            border-color: #a8ff78;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(168, 255, 120, 0.2);
        }

        .btn-login {
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
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(168, 255, 120, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
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

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.8);
        }

        .register-link a {
            color: #a8ff78;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #78ffd6;
            text-decoration: underline;
        }

        .user-type-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .user-type-option {
            text-align: center;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .user-type-option:hover {
            border-color: #a8ff78;
            background: rgba(168, 255, 120, 0.1);
        }

        .user-type-option.active {
            border-color: #a8ff78;
            background: rgba(168, 255, 120, 0.2);
            color: white;
        }

        .user-type-option i {
            font-size: 20px;
            margin-bottom: 5px;
            display: block;
        }

        .user-type-option span {
            font-size: 12px;
            font-weight: 500;
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
        }

        @media (max-width: 480px) {
            .login-body {
                padding: 20px;
            }

            .user-type-options {
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

    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-graduation-cap"></i> College Fee System</h2>
            <p>Sign in to your account</p>
        </div>

        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>Select User Type</label>
                    <div class="user-type-options">
                        <div class="user-type-option" data-value="student">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student</span>
                        </div>
                        <div class="user-type-option" data-value="admin">
                            <i class="fas fa-user-shield"></i>
                            <span>Admin</span>
                        </div>
                        <div class="user-type-option" data-value="accountant">
                            <i class="fas fa-calculator"></i>
                            <span>Accountant</span>
                        </div>
                    </div>
                    <input type="hidden" name="user_type" id="user_type" required>
                </div>

                <div class="form-group">
                    <label for="username">Username / Roll Number</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Account
                </button>
            </form>

            <div class="register-link">
                <p>New Student? <a href="register.php">Create an Account</a></p>
            </div>
        </div>
    </div>

    <script>
        // User type selection
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        const userTypeInput = document.getElementById('user_type');

        userTypeOptions.forEach(option => {
            option.addEventListener('click', () => {
                userTypeOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                userTypeInput.value = option.getAttribute('data-value');
            });
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            if (!userTypeInput.value) {
                e.preventDefault();
                alert('Please select a user type');
                return false;
            }
        });
    </script>
</body>

</html>