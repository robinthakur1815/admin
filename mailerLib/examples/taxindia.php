<html>
<head>
<title>PHPMailer - SMTP advanced test with authentication</title>
</head>
<body>
<?php
require_once('../class.phpmailer.php');
//include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded


$mail = new PHPMailer(); //$mail->IsMail();

	$mail->IsSMTP();
	$mail->From ='info@taxindiaonline.com';
	$mail->FromName='Nirmal';
	$mail->AddAddress('nirmal@cyberspace.in');
	$mail->Subject ="Enquiry - taxindia";
	$mail->Body = 'Hello World';
	$mail->WordWrap = 1000;
	$mail->IsHTML(true);
	if(!$mail->Send())
	{
	   echo  $mail->ErrorInfo;
	   exit;
	}
?>

</body>
</html>
