<html>
<head>
<title>PHPMailer - SMTP advanced test with authentication</title>
</head>
<body>
<?php
require_once('../class.phpmailer.php');
//include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded

$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch

$mail->IsSMTP(); // telling the class to use SMTP

try {
  $mail->Host       = "smtpin.falconide.com"; // SMTP server
  $mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
  $mail->SMTPAuth   = true;                  // enable SMTP authentication
  $mail->Host       = "smtpin.falconide.com"; // sets the SMTP server
  $mail->Port       = 587;                    // set the SMTP port for the GMAIL server
  $mail->Username   = "taxindiaonline"; // SMTP account username
  $mail->Password   = "T@x0nline";        // SMTP account password
 // $mail->AddCC
//  $mail->AddReplyTo('name@yourdomain.com', 'First Last');
  $mail->AddAddress('nirmal@cyberspace.in', 'John Doe');
  $mail->SetFrom('info@taxindiaonline.com', 'First Last');
//  $mail->AddReplyTo('name@yourdomain.com', 'First Last');
  $mail->Subject = 'PHPMailer Test Subject via mail(), advanced';
  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
  $mail->MsgHTML('My Name is nirmal');
//  $mail->AddAttachment('images/phpmailer.gif');      // attachment
//  $mail->AddAttachment('images/phpmailer_mini.gif'); // attachment
  $mail->Send();
  echo "Message Sent OK</p>\n";
} catch (phpmailerException $e) {
  echo $e->errorMessage(); //Pretty error messages from PHPMailer
} catch (Exception $e) {
  echo $e->getMessage(); //Boring error messages from anything else!
}
?>

</body>
</html>
