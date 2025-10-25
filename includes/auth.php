<?php
session_start();

function check_student_auth()
{
    if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] != 'student') {
        header('Location: ../login.php');
        exit;
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

// REMOVED THE VALIDATION FUNCTIONS - They are now in includes/functions.php
?>