<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Path to PHPMailer autoload
require_once 'connection.php'; // Include your database connection

// Get email from POST (called from forgot-password.php)
$email = $_POST['email'];

// First check if the email exists in the database
$stmt = $conn->prepare("SELECT reset_token FROM USERS WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $token = $user['reset_token']; // Get the token we stored earlier
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com'; // Your Gmail address
        $mail->Password   = 'aycm atee woxl lmvj'; // Your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        
        $reset_link = "http://localhost/vela/reset-password.php?token=$token&email=" . urlencode($email);
        
        $mail->Body    = "
            <h2>Password Reset Request</h2>
            <p>We received a request to reset your password. Click the link below to reset it:</p>
            <p><a href='$reset_link' style='background: #368ce7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
            <p>If you didn't request this, you can safely ignore this email.</p>
            <p>This link will expire in 1 hour.</p>
        ";
        
        $mail->AltBody = "Password Reset Request\n\nWe received a request to reset your password. Visit this link to reset it:\n$reset_link\n\nIf you didn't request this, you can safely ignore this email.\nThis link will expire in 1 hour.";

        $mail->send();
    } catch (Exception $e) {
        // Log error or handle it appropriately
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
} else {
    // Email doesn't exist in database (this should theoretically never happen since we checked earlier)
    error_log("Attempt to send reset email to non-existent address: $email");
}
?>