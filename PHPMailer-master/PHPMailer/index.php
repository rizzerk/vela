<?php require("script.php")?>

<?php

    if(isset($_POST['submit'])){
        if(empty($_POST['email']) || empty($_POST['subject']) || empty($_POST['message'])){
            $response = "All fields are required";
        }else{
            $response = sendMail($_POST['email'], $_POST['subject'], $_POST['message']);
        }
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="styles.css">
   <title>Send Email in PHP using PHPMailer and Gmail</title>
</head>
<body>
 
<form action="" method="post" enctype="multipart/form-data">
   <div class="info">
      Send an email to your self
   </div>
 
   <label>Enter your email</label>
   <input type="email" name="email" value="">
   
   <label>Enter a subject</label>
   <input type="text" name="subject" value="">
 
   <label>Enter your message</label>
   <textarea name="message"></textarea>
 
   <button type="submit" name="submit">Submit</button>

   <?php

    if(@$response == "success"){
        ?>
            <p class="success">Email and successfully</p>
        <?php
    }else{
        ?>
            <p class="error"><?php echo @$response; ?></p>
        
            <?php
    }

   ?>
</form>
 
</body>
</html>	