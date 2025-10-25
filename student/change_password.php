<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_student_auth();
$db = new Database();
$conn = $db->getConnection();

$student_id = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get student details using the function
    $student = get_student_details($conn, $student_id);

    if (!password_verify($current_password, $student['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE students SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt->execute([$hashed_password, $student_id])) {
            $message = "Password changed successfully!";
        } else {
            $error = "Failed to change password!";
        }
    }
}

// Redirect back to profile page with message
if (isset($message)) {
    header('Location: profile.php?message=' . urlencode($message));
} else {
    header('Location: profile.php?error=' . urlencode($error));
}
exit;
?>