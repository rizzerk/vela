<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

require __DIR__ . '/vendor/autoload.php';

function getMailer() {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth = true;
    
    // For password-based auth (less secure)
    $mail->Username = 'eventease0305@gmail.com';
    $mail->Password = 'ndbd remd mfno hyas';
    
    /* 
    // For OAuth2 (recommended but requires setup)
    $mail->AuthType = 'XOAUTH2';
    $provider = new Google([
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET'
    ]);
    $mail->setOAuth(
        new OAuth([
            'provider' => $provider,
            'clientId' => 'YOUR_CLIENT_ID',
            'clientSecret' => 'YOUR_CLIENT_SECRET',
            'refreshToken' => 'YOUR_REFRESH_TOKEN',
            'userName' => 'eventease0305@gmail.com'
        ])
    );
    */
    
    // Default from address
    $mail->setFrom('eventease0305@gmail.com', 'Property Management System');
    $mail->isHTML(true);
    
    return $mail;
}
?>