<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];

// Get student details using the function
$student = get_student_details($conn, $student_id);

if (!$student) {
    die("Student not found!");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $phone = clean_input($_POST['phone']);
    $academic_year = clean_input($_POST['academic_year']);
    $branch = clean_input($_POST['branch']);

    // Check if section exists in the form before using it
    $section = isset($_POST['section']) ? clean_input($_POST['section']) : '';

    // Handle profile picture upload
    $profile_picture = $student['profile_picture']; // Keep current picture by default

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];

        // Check file type
        if (in_array($file_type, $allowed_types)) {
            // Check file size (max 2MB)
            if ($file_size <= 2 * 1024 * 1024) {
                $upload_dir = '../assets/uploads/';

                // Create upload directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate unique filename
                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $new_filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists and is not the default
                    if (
                        $profile_picture && file_exists('../' . $profile_picture) &&
                        strpos($profile_picture, 'student_' . $student_id) !== false
                    ) {
                        unlink('../' . $profile_picture);
                    }
                    $profile_picture = 'assets/uploads/' . $new_filename;
                } else {
                    $error = "Failed to upload profile picture. Please try again.";
                }
            } else {
                $error = "Profile picture size must be less than 2MB.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != UPLOAD_ERR_NO_FILE) {
        $error = "Error uploading file: " . $_FILES['profile_picture']['error'];
    }

    // Update student details if no error
    if (empty($error)) {
        try {
            // First, check if section column exists in the table
            $check_column_sql = "SHOW COLUMNS FROM students LIKE 'section'";
            $column_stmt = $conn->query($check_column_sql);
            $section_column_exists = $column_stmt->rowCount() > 0;

            if ($section_column_exists) {
                $sql = "UPDATE students SET full_name = ?, phone = ?, academic_year = ?, branch = ?, section = ?, profile_picture = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $success = $stmt->execute([$full_name, $phone, $academic_year, $branch, $section, $profile_picture, $student_id]);
            } else {
                // If section column doesn't exist, update without it
                $sql = "UPDATE students SET full_name = ?, phone = ?, academic_year = ?, branch = ?, profile_picture = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $success = $stmt->execute([$full_name, $phone, $academic_year, $branch, $profile_picture, $student_id]);
            }

            if ($success) {
                $message = "Profile updated successfully!";
                // Refresh student data
                $student = get_student_details($conn, $student_id);
            } else {
                $error = "Failed to update profile!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - College Fee System</title>
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
            background:
                radial-gradient(circle at 10% 20%, rgba(168, 255, 120, 0.3) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 154, 158, 0.3) 0%, transparent 20%),
                linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            overflow-x: hidden;
            position: relative;
            padding: 20px;
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

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            border-radius: 20px 20px 0 0;
        }

        h2 {
            color: white;
            margin-bottom: 30px;
            font-size: 2.2rem;
            text-align: center;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin-right: 30px;
            position: relative;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture .change-btn {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        .profile-picture .change-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        .profile-info h3 {
            color: white;
            margin-bottom: 10px;
            font-size: 1.6rem;
        }

        .profile-info p {
            color: rgba(255, 255, 255, 0.9);
            margin: 8px 0;
            font-size: 1rem;
        }

        .profile-info small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.95rem;
        }

        input,
        select {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
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

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: linear-gradient(to right, #a8ff78, #78ffd6);
            color: #333;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            backdrop-filter: blur(5px);
            border-left: 4px solid;
        }

        .success {
            background: rgba(212, 237, 218, 0.2);
            color: #a8ff78;
            border-left-color: #a8ff78;
        }

        .error {
            background: rgba(248, 215, 218, 0.2);
            color: #ff6b6b;
            border-left-color: #ff6b6b;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .file-info {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 8px;
            text-align: center;
        }

        .password-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .password-section h3 {
            color: white;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-section h3 i {
            color: #a8ff78;
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

            .container {
                padding: 25px 20px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .profile-picture {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .btn {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
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

    <div class="container">
        <h2><i class="fas fa-user-edit"></i> Update Profile</h2>

        <?php if (!empty($message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <div class="profile-header">
                <div class="profile-picture" id="profilePictureContainer">
                    <?php if (!empty($student['profile_picture']) && file_exists('../' . $student['profile_picture'])): ?>
                        <img src="../<?php echo $student['profile_picture']; ?>?t=<?php echo time(); ?>"
                            alt="Profile Picture" id="profileImage">
                    <?php else: ?>
                        <div id="profileInitial"><?php echo strtoupper(substr($student['full_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="change-btn" onclick="document.getElementById('profile_picture').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </div>
                </div>
                <div class="profile-info">
                    <h3><?php echo $student['full_name']; ?></h3>
                    <p><strong><i class="fas fa-id-card"></i> Roll Number:</strong>
                        <?php echo $student['roll_number']; ?></p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo $student['email']; ?>
                        <small>(Cannot be changed)</small>
                    </p>
                </div>
            </div>

            <input type="file" id="profile_picture" name="profile_picture"
                accept="image/jpeg,image/png,image/gif,image/jpg" style="display: none;" onchange="previewImage(this)">
            <div class="file-info"><i class="fas fa-info-circle"></i> Supported formats: JPG, PNG, GIF | Max size: 2MB
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Academic Year</label>
                    <select name="academic_year" required>
                        <option value="1st Year" <?php echo $student['academic_year'] == '1st Year' ? 'selected' : ''; ?>>
                            1st Year</option>
                        <option value="2nd Year" <?php echo $student['academic_year'] == '2nd Year' ? 'selected' : ''; ?>>
                            2nd Year</option>
                        <option value="3rd Year" <?php echo $student['academic_year'] == '3rd Year' ? 'selected' : ''; ?>>
                            3rd Year</option>
                        <option value="4th Year" <?php echo $student['academic_year'] == '4th Year' ? 'selected' : ''; ?>>
                            4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-code-branch"></i> Branch</label>
                    <select name="branch" required>
                        <option value="CSE" <?php echo $student['branch'] == 'CSE' ? 'selected' : ''; ?>>Computer Science
                        </option>
                        <option value="ECE" <?php echo $student['branch'] == 'ECE' ? 'selected' : ''; ?>>Electronics
                        </option>
                        <option value="ME" <?php echo $student['branch'] == 'ME' ? 'selected' : ''; ?>>Mechanical</option>
                        <option value="CE" <?php echo $student['branch'] == 'CE' ? 'selected' : ''; ?>>Civil</option>
                        <option value="EE" <?php echo $student['branch'] == 'EE' ? 'selected' : ''; ?>>Electrical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users"></i> Section</label>
                    <input type="text" name="section" value="<?php echo htmlspecialchars($student['section'] ?? ''); ?>"
                        placeholder="e.g., A, B, C">
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Profile
                </button>
                <a href="../dashboard_student.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>

        <div class="password-section">
            <h3><i class="fas fa-key"></i> Change Password</h3>
            <form method="POST" action="change_password.php" id="passwordForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    input.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const profilePictureContainer = document.getElementById('profilePictureContainer');
                    profilePictureContainer.innerHTML = `
                        <img src="${e.target.result}" alt="Profile Picture" id="profileImage">
                        <div class="change-btn" onclick="document.getElementById('profile_picture').click()">
                            <i class="fas fa-camera"></i> Change Photo
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            }
        }

        // Add form validation
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            const phone = document.querySelector('input[name="phone"]').value;
            const phoneRegex = /^[0-9]{10}$/;

            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number');
                return false;
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function (e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>

</html>