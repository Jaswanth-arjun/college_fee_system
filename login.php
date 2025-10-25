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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
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

        .login-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .login-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
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
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #007bff, #0056b3);
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
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
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

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #0056b3;
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
            border: 2px solid #e1e5ee;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .user-type-option:hover {
            border-color: #007bff;
            background: white;
        }

        .user-type-option.active {
            border-color: #007bff;
            background: #007bff;
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