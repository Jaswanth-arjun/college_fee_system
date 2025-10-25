<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // This should come after functions.php

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
            // ... rest of login code
            if ($user) {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['roll_number'] = $user['roll_number'];
                header('Location: dashboard_student.php');
                exit;
            }
            break;

        case 'admin':
            $user = validate_admin_login($conn, $username, $password); // Pass $conn
            if ($user) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['user_type'] = 'admin';
                header('Location: dashboard_admin.php');
                exit;
            }
            break;

        case 'accountant':
            $user = validate_accountant_login($conn, $username, $password); // Pass $conn
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .login-container {
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

        input,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2 style="text-align: center;">College Fee Management System</h2>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>User Type:</label>
                <select name="user_type" required>
                    <option value="">Select User Type</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                    <option value="accountant">Accountant</option>
                </select>
            </div>

            <div class="form-group">
                <label>Username / Roll Number:</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="register-link">
            <p>New Student? <a href="register.php">Register Here</a></p>
        </div>
    </div>
</body>

</html>