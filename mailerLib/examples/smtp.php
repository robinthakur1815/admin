<?php

require_once "class.phpmailer.php";


	$mail = new PHPMailer(); //$mail->IsMail();

	$mail->IsSMTP();
	$mail->From ='info@taxindiaonline.com';
	
	$mail->AddAddress('nirmal@cyberspace.in');
	$mail->Subject ="Enquiry - taxindia";
	$mail->Body = $strMailBody;
	$mail->WordWrap = 1000;
	$mail->IsHTML(true);
	if(!$mail->Send())
	{
	   echo  $mail->ErrorInfo;
	   exit;
	}

?>
