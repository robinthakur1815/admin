<?php
#Author BY AD
class Subuser{

 #database connection and table name
    private $conn;
    private $table_user = "users";

    #object properties
    public $uniq_id;
    public $parent_id;
    public $first_name;
    public $last_name;
    public $email;
    public $role_id;
    public $contact;
    public $salt_id;
    public $password;
    public $subuser_id;
    public $pwd;
    public $parent_email;

    
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
   #create get services
    public function getSubuser(){
    //$queryFetch= 'SELECT id,role_id,CONCAT(f_name," ",l_name) as name,email,contact FROM ' . $this->table_user . ' where parent_id ='.$this->parent_id.' and user_status="Y"';
    $queryFetch= 'SELECT id,role_id,f_name,l_name,email,contact,password,salt_key FROM ' . $this->table_user . ' where parent_id ='.$this->parent_id.' and user_status="Y"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    return $row;
    
    }
    #get salt key
    public function getSubSaltKey($subuser = NULL){
       $queryFetch= 'SELECT salt_key FROM ' . $this->table_user . ' WHERE id='.$subuser.'';
         
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
    }
    #verify Email
    public function verifyEmail($subuser = NULL){
        if($subuser !=''){

        $queryFetch= 'SELECT email FROM ' . $this->table_user . ' WHERE email = "'.trim(strip_tags($this->email)).'" and id!='.$subuser.'';
         }else{
            $queryFetch= 'SELECT email FROM ' . $this->table_user . ' WHERE email = "'.trim(strip_tags($this->email)).'"';
         }
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
    }
    #create sub-user
    public function createSubuser(){
    #Email validation check
    $resultEmail = $this->verifyEmail();
    $resultEmail->store_result();
    $rows = $resultEmail->num_rows;       
    if($rows > 0){
         return 2;
    }else{
    #sanitize
    $this->uniq_id=strip_tags($this->uniq_id);
    $this->first_name=htmlspecialchars(trim(strip_tags($this->first_name)));
    $this->last_name=htmlspecialchars(trim(strip_tags($this->last_name)));
    $this->email=htmlspecialchars(trim(strip_tags($this->email)));
    $this->role_id=trim(strip_tags($this->role_id));
    $this->password=trim(strip_tags($this->password));
    
    if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
    
      return 3;
      exit();      
    }
     
        
      #query to insert record in users table
      $queryUser = 'INSERT INTO ' . $this->table_user . '(uniq_id,role_id,f_name, l_name, email, password,contact, salt_key, user_status,parent_id) VALUES ("'.$this->uniq_id.'",'.$this->role_id.',"'.$this->first_name.'", "'.$this->last_name.'", "'.$this->email.'","'.$this->password.'","'.$this->contact.'","'.$this->salt_id.'","Y",'.$this->parent_id.')';
      #prepare query
      $stmt_user = $this->conn->prepare($queryUser);
      // #execute query
      if($stmt_user->execute()){ 
         #Call mailer 
            
            $html = '<html><head>
                <meta charset="utf-8" />
                <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex,nofollow" />
                <title>auxo ads </title> 
              </head>

              <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
            <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
              <tbody>
              <tr style="background: #D6C4FB !important;">
                <td style="padding: 10px!important; color: #000; text-align: center;"> 
                <!-- <a href="#" target="_blank">
                  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo1.png" style="border: none ; width: 150px; margin-top: 1px;" /> 
                </a>  -->
                  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
                </td>

                <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
                <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
                </td>-->
              </tr>
              </tbody>
            </table>

                    
                
         <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
          <tbody>
            <tr>
              <td>
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->first_name).' '.ucwords($this->last_name).',</p>

               
                 <p style="font-size: 18px; line-height: 33px;">You have been invited by '.$this->parent_email.' to access earnings reports via Auxo Ads.</p>
                  <p style="font-size: 18px; line-height: 33px;"> Welcome to the Auxo Ads community! Start exploring by logging in using the below credentials. </p>
                  <p style="font-size: 18px; line-height: 33px;"> Username : '.$this->email.'</p>
                  <p style="font-size: 18px; line-height: 33px;"> Password : '.$this->pwd.'</p>
                  <p style="font-size: 18px; line-height: 33px;"> We recommend you change your password as per your preferences. </p>
                  <p style="font-size: 18px; line-height: 33px;"> In case of any concerns you may reach out to us <a href="mailto: support@cybermedia.co.in" style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 18px;">here.</a> </p>

            <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Thanks!</span><br>
                  <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                </td>
              </tr>
            </tbody>
          </table> 
          <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
          <span style="position: relative; top: -5px;">
            Visit us at
            <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
            <br/>
             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
             </span>
          </span>
        </center>
      </center>
  </body>
</html>';

                   $subject = "Invitation to Auxo Ads!";
                   $mailer = $this->mailPub($html,$subject,$this->email);

                   return $mailer; 
            
         }
      }
    
    return false;
    }  
    #update sub-user
    public function updateSubuser(){
    #Email validation check
    $resultEmail = $this->verifyEmail($this->subuser_id);
    $row_results = $resultEmail->get_result();

    $rows = $row_results->num_rows;       
    if($rows > 0){
         return 2;
    }else{

    $resultKey = $this->getSubSaltKey($this->subuser_id);
    $row_key = $resultKey->get_result();
    $row = $row_key->fetch_array(MYSQLI_ASSOC);
   
    #sanitize
    $this->first_name=htmlspecialchars(trim(strip_tags($this->first_name)));
    $this->last_name=htmlspecialchars(trim(strip_tags($this->last_name)));
    $this->email=htmlspecialchars(trim(strip_tags($this->email)));
    $this->role_id=trim(strip_tags($this->role_id));
    //$this->password=trim(strip_tags($this->password)).$row['salt_key'];
    
    if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
    
      return 3;
      exit();      
    }
     
        
      #query to update record in users table
      $queryUser = 'UPDATE users SET role_id ='.$this->role_id.',`f_name`="'.$this->first_name.'",`l_name`="'.$this->last_name.'",`email`="'.$this->email.'",`contact`="'.$this->contact.'" WHERE `id`='.$this->subuser_id.'';
      #prepare query
      $stmt_user = $this->conn->prepare($queryUser);
      #execute query
      if($stmt_user->execute()){ 
                   #Call mailer 
            
            $html = '
                 <html><head>
                <meta charset="utf-8" />
                <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex,nofollow" />
                <title>auxo ads </title> 
              </head>

              <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
				<center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
				<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
					<tbody>
					<tr style="background: #D6C4FB !important;">
						<td style="padding: 10px!important; color: #000; text-align: center;"> 
						<!-- <a href="#" target="_blank">
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo1.png" style="border: none ; width: 150px; margin-top: 1px;" /> 
						</a>  -->
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
						</td>

						<!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
						<p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
						</td>-->
					</tr>
					</tbody>
				</table>
                
        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
          <tbody>
            <tr>
              <td>
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->first_name).' '.ucwords($this->last_name).',</p>

               
                 <p style="font-size: 18px; line-height: 33px;">Your profile details have been updated. This may change your level of access to Auxo Ads. You may connect with your Admin in case of any concerns.</p>
                  

                <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Thanks!</span><br>
                  <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                </td>
              </tr>
            </tbody>
          </table> 
          <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
          <span style="position: relative; top: -5px;">
            Visit us at
            <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
            <br/>
             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
             </span>
          </span>
        </center>
      </center>
  </body>
</html>';

                   $subject = "Auxo Ads Change in User Details";
                   $mailer = $this->mailPub($html,$subject,$this->email);

                   return $mailer; 
         }
      }
    
    return false;
    } 
#query to get subuser record from users table
    public function editSubuser(){
    $queryEdit= "SELECT f_name, l_name, email, password, contact,role_id FROM " . $this->table_user . " where id =".$this->subuser_id."";
      #prepare query
      $edit_row = $this->conn->prepare($queryEdit);
      #execute query 
      $edit_row->execute();
      return $edit_row;
      
      }
#delete subuser from users
public function deleteSubuser(){
    #query to update status in users table
    $queryUserdelete = 'UPDATE users SET `user_status`="N" WHERE `id`='.$this->subuser_id.'';
    #prepare query
    $stmt_user_delete = $this->conn->prepare($queryUserdelete);
    #execute query
    if($stmt_user_delete->execute()){ 
        #Call mailer 
            
            $html = '
                    <html><head>
                <meta charset="utf-8" />
                <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex,nofollow" />
                <title>auxo ads </title> 
              </head>

              <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
				<center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
				<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
					<tbody>
					<tr style="background: #D6C4FB !important;">
						<td style="padding: 10px!important; color: #000; text-align: center;"> 
						<!-- <a href="#" target="_blank">
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo1.png" style="border: none ; width: 150px; margin-top: 1px;" /> 
						</a>  -->
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
						</td>

						<!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
						<p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
						</td>-->
					</tr>
					</tbody>
				</table>
                    
                
        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
          <tbody>
            <tr>
              <td>
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->first_name.' '.$this->last_name).',</p>

               
                 <p style="font-size: 18px; line-height: 33px;">Your access to Auxo Ads has been deactivated. You may connect with your Admin in case of any concerns.</p>
                  
         <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Thanks!</span><br>
                  <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                </td>
              </tr>
            </tbody>
          </table> 
      <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
          <span style="position: relative; top: -5px;">
            Visit us at
            <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
            <br/>
             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
             </span>
          </span>
        </center>
      </center>
  </body>
</html>';

                   $subject = "Auxo Ads Account Deactivated";
                   $mailer = $this->mailPub($html,$subject,$this->email);

                   return $mailer; 
       }
   
  
    return false;
  }
 #send mail
  public function mailPub($html,$subject,$email){
    include_once('../../mailerLib/class.phpmailer.php');
    $body_bank = $html;
            $mail  = new PHPMailer();
            $mail->IsSMTP();  
            $mail->Host       = "103.76.212.101";
            $mail->SMTPDebug  = 1; 
            $mail->SMTPAuth   = true;   
            $mail->Username   = 'noreply@cybermedia.co.in';
            $mail->Password   = 'K6Cx*5G%W8j';
            $mail->Port = "587";
            $mail->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
            $mail->Subject = $subject;           
            //$mail->addAddress('ankurdu@cybermedia.co.in');
            $mail->addAddress('shivamj@cmrsl.net');
            $mail->isHTML(true);
            $mail->Body = $body_bank;
            if($mail->Send()){
                return true;
             }else{
                return false;
             }
  }

}
?>