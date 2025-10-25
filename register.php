<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = clean_input($_POST['full_name']);
    $roll_number = clean_input($_POST['roll_number']);
    $email = clean_input($_POST['email']);
    $academic_year = clean_input($_POST['academic_year']);
    $branch = clean_input($_POST['branch']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $phone = clean_input($_POST['phone']);

    // Validate passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if roll number or email already exists
        $check_sql = "SELECT id FROM students WHERE roll_number = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$roll_number, $email]);

        if ($check_stmt->rowCount() > 0) {
            $error = "Roll number or email already exists!";
        } else {
            // Hash password
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

                // Insert new OTP WITHOUT expiration for now
                $otp_sql = "INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
                $otp_stmt = $conn->prepare($otp_sql);
                $otp_stmt->execute([$email, $otp]);

                $conn->commit();

                // Store in session for verification
                $_SESSION['register_email'] = $email;
                $_SESSION['demo_otp'] = $otp; // For demo purposes

                // Redirect to OTP verification
                header('Location: verify_otp.php');
                exit;

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
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
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
            font-weight: bold;
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
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #218838;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 style="text-align: center;">Student Registration</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="registrationForm">
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name"
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Roll Number:</label>
                <input type="text" name="roll_number"
                    value="<?php echo isset($_POST['roll_number']) ? htmlspecialchars($_POST['roll_number']) : ''; ?>"
                    required>
            </div>

            <div class="form-group">
                <label>College Email:</label>
                <input type="email" name="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Academic Year:</label>
                <select name="academic_year" required>
                    <option value="">Select Year</option>
                    <option value="1st Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2nd Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3rd Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4th Year" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                </select>
            </div>

            <div class="form-group">
                <label>Branch:</label>
                <select name="branch" required>
                    <option value="">Select Branch</option>
                    <option value="CSE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'CSE') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="ECE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'ECE') ? 'selected' : ''; ?>>Electronics</option>
                    <option value="ME" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'ME') ? 'selected' : ''; ?>>Mechanical</option>
                    <option value="CE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'CE') ? 'selected' : ''; ?>>Civil</option>
                    <option value="EE" <?php echo (isset($_POST['branch']) && $_POST['branch'] == 'EE') ? 'selected' : ''; ?>>Electrical</option>
                </select>
            </div>

            <div class="form-group">
                <label>Phone Number:</label>
                <input type="tel" name="phone"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" id="submitBtn">Register</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login Here</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const submitBtn = document.getElementById('submitBtn');

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';
            return true;
        });
    </script>
</body>

</html>