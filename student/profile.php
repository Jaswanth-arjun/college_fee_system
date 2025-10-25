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
    $section = clean_input($_POST['section']);

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
        $sql = "UPDATE students SET full_name = ?, phone = ?, academic_year = ?, branch = ?, section = ?, profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$full_name, $phone, $academic_year, $branch, $section, $profile_picture, $student_id]);

        if ($success) {
            $message = "Profile updated successfully!";
            // Refresh student data
            $student = get_student_details($conn, $student_id);
        } else {
            $error = "Failed to update profile!";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin-right: 30px;
            position: relative;
            overflow: hidden;
            border: 3px solid #007bff;
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
            padding: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .profile-picture .change-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        .form-group {
            margin-bottom: 20px;
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

        .btn {
            padding: 12px 25px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success {
            background: #28a745;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Update Profile</h2>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
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
                        Change Photo
                    </div>
                </div>
                <div>
                    <h3><?php echo $student['full_name']; ?></h3>
                    <p><strong>Roll Number:</strong> <?php echo $student['roll_number']; ?></p>
                    <p><strong>Email:</strong> <?php echo $student['email']; ?> <small>(Cannot be changed)</small></p>
                </div>
            </div>

            <input type="file" id="profile_picture" name="profile_picture"
                accept="image/jpeg,image/png,image/gif,image/jpg" style="display: none;" onchange="previewImage(this)">
            <div class="file-info">Supported formats: JPG, PNG, GIF | Max size: 2MB</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Academic Year</label>
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
                    <label>Branch</label>
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
                    <label>Section</label>
                    <input type="text" name="section" value="<?php echo htmlspecialchars($student['section'] ?? ''); ?>"
                        placeholder="e.g., A, B, C">
                </div>
            </div>

            <button type="submit" class="btn btn-success">Update Profile</button>
            <a href="../dashboard_student.php" class="btn" style="background: #6c757d; color: white;">Back to
                Dashboard</a>
        </form>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>Change Password</h3>
            <form method="POST" action="change_password.php" id="passwordForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn">Change Password</button>
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
                            Change Photo
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