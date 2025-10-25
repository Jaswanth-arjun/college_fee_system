<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function check_student_auth()
{
    // Check if student is logged in (with or without user_type)
    if (!isset($_SESSION['student_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'student')) {
        header('Location: ../login.php');
        exit;
    }

    // If user_type is not set, set it to student for backward compatibility
    if (!isset($_SESSION['user_type'])) {
        $_SESSION['user_type'] = 'student';
    }
}

function check_admin_auth()
{
    if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

function check_accountant_auth()
{
    if (!isset($_SESSION['accountant_id']) || $_SESSION['user_type'] != 'accountant') {
        header('Location: ../login.php');
        exit;
    }
}
?>