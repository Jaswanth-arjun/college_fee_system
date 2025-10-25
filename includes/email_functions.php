<?php
// Manually include PHPMailer classes
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include email config
require_once 'email_config.php';

function sendOTPEmail($email, $otp, $student_name = '')
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Debug settings - only if SMTP_DEBUG is defined and greater than 0
        if (defined('SMTP_DEBUG') && SMTP_DEBUG > 0) {
            $mail->SMTPDebug = SMTP_DEBUG;
        } else {
            $mail->SMTPDebug = 0;
        }

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'College Fee System - Email Verification OTP';

        $email_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .otp-code { background: #f8f9fa; border: 2px dashed #007bff; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #007bff; margin: 20px 0; border-radius: 10px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
                .note { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>College Fee System</h2>
                    <p>Email Verification</p>
                </div>
                
                <h3>Hello " . htmlspecialchars($student_name) . ",</h3>
                <p>Thank you for registering with our College Fee Management System. Please use the following OTP to verify your email address:</p>
                
                <div class='otp-code'>" . $otp . "</div>
                
                <div class='note'>
                    <strong>Note:</strong> This OTP is valid for 1 hour. Please do not share this code with anyone.
                </div>
                
                <p>If you didn't request this verification, please ignore this email.</p>
                
                <div class='footer'>
                    <p>Best regards,<br>College Fee System Team</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $email_body;

        // Plain text version for non-HTML email clients
        $mail->AltBody = "College Fee System - Email Verification\n\nHello $student_name,\n\nYour OTP for email verification is: $otp\n\nThis OTP is valid for 1 hour.\n\nBest regards,\nCollege Fee System Team";

        $mail->send();
        return ['success' => true, 'message' => 'OTP email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email sending failed: " . $mail->ErrorInfo];
    }
}

function sendWelcomeEmail($email, $student_name, $roll_number)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Debug settings
        if (defined('SMTP_DEBUG') && SMTP_DEBUG > 0) {
            $mail->SMTPDebug = SMTP_DEBUG;
        } else {
            $mail->SMTPDebug = 0;
        }

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to College Fee System - Registration Successful';

        $email_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to College Fee System</h2>
                    <p>Registration Successful!</p>
                </div>
                
                <h3>Hello " . htmlspecialchars($student_name) . ",</h3>
                <p>Your registration with the College Fee Management System has been successfully completed and verified!</p>
                
                <div class='info-box'>
                    <p><strong>Roll Number:</strong> " . htmlspecialchars($roll_number) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Status:</strong> Verified ✅</p>
                </div>
                
                <p>You can now login to your account using your roll number and password to:</p>
                <ul>
                    <li>View your fee details</li>
                    <li>Make online payments</li>
                    <li>Download receipts</li>
                    <li>Track payment history</li>
                </ul>
                
                <p><strong>Login URL:</strong> http://localhost/college_fee_system/login.php</p>
                
                <div class='footer'>
                    <p>Best regards,<br>College Fee System Team</p>
                    <p>Need help? Contact our support team.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $email_body;
        $mail->AltBody = "Welcome to College Fee System\n\nHello $student_name,\n\nYour registration has been completed successfully!\n\nRoll Number: $roll_number\nEmail: $email\nStatus: Verified\n\nYou can now login to your account to view and pay fees.\n\nBest regards,\nCollege Fee System Team";

        $mail->send();
        return ['success' => true, 'message' => 'Welcome email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Welcome email failed: " . $mail->ErrorInfo];
    }
}
?>