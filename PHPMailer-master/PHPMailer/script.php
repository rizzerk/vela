<?php
 // put PHPMailer namespaces at the top of the page.

 use PHPMailer\PHPMailer\Exception;

 use PHPMailer\PHPMailer\PHPMailer;

 use PHPMailer\PHPMailer\SMTP;

 //require config.php file to use our Gmail account login details

 require 'config.php';

 function sendMail($email, $subject, $message){

    //create new PHPMailer object
    $mail = new PHPMailer(true);

    //use the SMTP protocol to send email
    $mail->isSMTP();

    //set SMTPAuth to true to use Gmail login details to send the mail
    $mail->SMTPAuth = true;

    //set host property to the MAILHOST value defined in the config file
    $mail->Host = MAILHOST;

    //set username property to USEERNAME value
    $mail->Username = USERNAME;

    //set password property to PASSWORD value
    $mail->Password = PASSWORD;

    //put STARTTLS encryption between your PHP application and SMTP server to add security
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    //TCP port to connect with the Gmail SMTP Server
    $mail->Port = 587;

    //who is sending the email. use constants from config file
    $mail->setFrom(SEND_FROM, SEND_FROM_NAME);

    //where the email goes
    $mail->addAddress($email);  

    //where the recipient can reply to. constant from config file.
    $mail->addReplyTo(REPLY_TO,REPLY_TO_NAME);

    //inform PHPMailer that the email message we're constructing will include HTML markup. to include hyperlinks, images, formatting.
    $mail->isHTML(true);

    //assign incoming subject to the subject property
    $mail->Subject = $subject;

    //assign incoming message to body property
    $mail->Body = $message;

    //provide a plain text alternative to the HTML version of our email.
    $mail->AltBody = $message;

    if(!$mail->send()){
        return "Email not sent. Please try again";
    }else{
        return "success";
    }
 }

?>