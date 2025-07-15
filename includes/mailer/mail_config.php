<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (adjust path if needed)
require __DIR__ . '/../vendor/autoload.php';

/**
 * Send an email via Gmail SMTP using PHPMailer.
 * 
 * @param string $to      Recipient email address.
 * @param string $subject Email subject.
 * @param string $body    HTML/Plain text email body.
 * @param string $name    (Optional) Recipient name.
 * @return bool           True if sent successfully, false otherwise.
 */
function sendEmail($to, $subject, $body, $name = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings (Gmail SMTP)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com'; // Replace with your Gmail
        $mail->Password   = 'aycm atee woxl lmvj'; // Replace with App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port       = 465;

        // Sender (your Gmail/website email)
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');

        // Recipient
        if (!empty($name)) {
            $mail->addAddress($to, $name); // Name is optional
        } else {
            $mail->addAddress($to);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Uncomment for plain-text fallback
        // $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log errors (check your PHP error logs)
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>