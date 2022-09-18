<?php
#Author BY AD
class User{
  
    #database connection and table name
    private $conn;
    private $table_name = "publisher_master";
    private $table_name2 = "users";
    private $table_name3 = "publishers_website";
    private $table_name4 = "publisher_services";
	private $table_name5 = "livedemo";
  
    #object properties
    public $uniq_id;
    public $f_name;
    public $l_name;
    public $email;
    public $country_code;
    public $domain;
    public $contact;
    public $salt_id;
    public $password;
    public $token;
    public $service_id;
    public $comment;
    public $user_id;
    public $newpassword;
    public $verificationText;
    public $name;
    public $old_service;
    public $servFalseArr;
	public $message;
    public $newsletter;
    public $child_net_code;
    public $business_type;
    public $token_counts;
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    #verify Email
    public function verifyEmail(){
        $queryFetch= 'SELECT pub_email,pub_uniq_id,CONCAT(pub_fname," ",IFNULL(pub_lname,"")) as name FROM ' . $this->table_name . ' WHERE pub_email = "'.strip_tags(strtolower($this->email)).'"';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
    }
	public function verifyDomain(){
        $queryFetch= 'SELECT id FROM ' . $this->table_name3 . ' WHERE web_name like "%'.strip_tags($this->domain).'%"';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
    }
    public function checkApiDoc(){
      $queryFetch= 'SELECT adx_for_display,adx_for_video,adx_for_app,child_net_code FROM ' .$this->table_master. ' WHERE pub_uniq_id = "'.$this->uniq_id.'"';
      #prepare query
      $row = $this->conn->prepare($queryFetch);
      #execute query 
      $row->execute();
      return $row;
   }
    
    #Get getapidoc
      public function getapidoc()
      {
       $queryFetch= 'SELECT * FROM ' . $this->thirdpartyapi . ' WHERE unid_id = "'.$this->uniq_id.'"';
          #prepare query
       $row = $this->conn->prepare($queryFetch);
       #execute query 
       $row->execute();
        return $row;
      }
	
	
    #create user
    public function create(){
    #Email validation check
    $resultEmail = $this->verifyEmail();
    $resultEmail->store_result();
    $rows = $resultEmail->num_rows;       
    if($rows > 0){
         return 2;
    }else{
    #sanitize
    $this->uniq_id=strip_tags($this->uniq_id);
    $this->f_name=htmlspecialchars(trim(strip_tags($this->f_name)));
    $this->l_name=htmlspecialchars(trim(strip_tags($this->l_name)));
    $this->email=htmlspecialchars(trim(strip_tags(strtolower($this->email))));
    $this->country_code=htmlspecialchars(trim(strip_tags($this->country_code)));
    $this->domain=trim(strip_tags($this->domain));
    $this->password=trim(strip_tags($this->password));
    if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
    
      return 3;
      exit();      
    }
    if($this->business_type == 'Website'){
    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$this->domain)) {
      return 4;
      exit();
    }
    #Domain check
    $resultDomain = $this->verifyDomain();
    $resultDomain->store_result();
    $rowsDomain = $resultDomain->num_rows;       
    if($rowsDomain > 0){
         return 5;
         exit();
     }
    }
    #query to insert record in publisher master table
    $queryPub = 'INSERT INTO ' . $this->table_name . '(pub_uniq_id, pub_fname, pub_lname, pub_email,country_code_id) VALUES ("'.$this->uniq_id.'", "'.$this->f_name.'", "'.$this->l_name.'", "'.$this->email.'",'.$this->country_code.')';
    #prepare query
    $stmt_pub = $this->conn->prepare($queryPub);
    #execute query
    if($stmt_pub->execute()){
      $pubID = $this->conn->insert_id;
        
      #query to insert record in users table
      $queryUser = 'INSERT INTO ' . $this->table_name2 . '(uniq_id,role_id,f_name, l_name, email, password,contact, salt_key, user_status) VALUES ("'.$this->uniq_id.'","3","'.$this->f_name.'", "'.$this->l_name.'", "'.$this->email.'","'.$this->password.'","'.$this->contact.'","'.$this->salt_id.'","Y")';
      #prepare query
      $stmt_user = $this->conn->prepare($queryUser);

      #execute query
      if($stmt_user->execute()){
        
        if($this->business_type == 'Website'){  
      #query to insert record in website table
      $input = trim($this->domain, '/');
          if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
          }
      $urlParts = parse_url($input);
      $domainName = preg_replace('/^www\./', '', $urlParts['host']);  
      $querypubWeb = 'INSERT INTO ' . $this->table_name3 . '(pub_id,pub_uniq_id, web_name) VALUES ('.$pubID.',"'.$this->uniq_id.'", "'.$domainName.'")';
      #prepare query
      $stmt_pubWeb = $this->conn->prepare($querypubWeb);
      
      #execute query
      if($stmt_pubWeb->execute()){ 
          
          return true;
         }
      }else{
        return true;
      }
     }
    }
    return false;
    }  
      
  }
  #create login 
  public function login(){

    $this->email=htmlspecialchars(strip_tags($this->email));
    $this->password=trim(strip_tags($this->password));
    $queryFetch= "SELECT salt_key,email,CONCAT(f_name, ' ', IFNULL(l_name,'')) as name,role_id,uniq_id,password,id,parent_id,uniq_id,access_token,token_count FROM " . $this->table_name2 . " WHERE email ='".$this->email."' and user_status !='N'";
    #prepare query
    $stmt = $this->conn->prepare($queryFetch);
    #execute query 
    $stmt->execute();
    $stmt_result = $stmt->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
   
    $rows = $stmt_result->num_rows;
    if($rows > 0){

        $getPassword = base64_encode(trim($this->password)).$row['salt_key'];
        #password check
         if($getPassword == $row['password']){
            #condition for superadmin/Admin
            if($row['role_id'] == 1){
                $stmtPub = array('pub_name'=>trim($row['name']),
                    'pub_email'=>trim($row['email'])
                );
               return array($stmtPub,4,$row['id'],$row['role_id'],$row['access_token'],$row['token_count']);
            }
            else if($row['role_id'] == 9){
                $stmtPub = array('pub_name'=>trim($row['name']),
                    'pub_email'=>trim($row['email'])
                );
               return array($stmtPub,6,$row['id'],$row['role_id'],$row['uniq_id'],$row['access_token'],$row['token_count']);
            }
			else if($row['role_id'] == 2){

                 $queryFetchP = "SELECT manager_id,manager_name,manager_email FROM account_manager WHERE manager_email ='".$row['email']."' and manager_status !='N'";
                  #prepare query
                  $stmtP = $this->conn->prepare($queryFetchP);
                  #execute query 
                  $stmtP->execute();
                  $stmt_resultP = $stmtP->get_result();
                  $rowP = $stmt_resultP->fetch_array(MYSQLI_ASSOC);
                 $rowsP = $stmt_resultP->num_rows;
                if($rowsP > 0){
                 $stmtPub = array('manager_id'=>$rowP['manager_id'],'pub_name'=>trim($rowP['manager_name']),
                    'pub_email'=>trim($rowP['manager_email'])

                );

                   return array($stmtPub,7,$row['id'],$row['role_id'],$row['uniq_id'],$row['access_token'],$row['token_count']);
               }else{
                  return 1 ;
                }
            }
            else if($row['role_id'] == 12 || $row['role_id'] == 13 || $row['role_id'] == 14)
            {
              $queryFetchP = "SELECT email FROM " . $this->table_name2 . " WHERE id =".$row['parent_id']." and user_status !='N'";
              #prepare query
              $stmtP = $this->conn->prepare($queryFetchP);
              #execute query 
              $stmtP->execute();
              $stmt_resultP = $stmtP->get_result();
              $rowP = $stmt_resultP->fetch_array(MYSQLI_ASSOC);
             
              $rowsP = $stmt_resultP->num_rows;
                if($rowsP > 0){

                   #query
                    $queryPub="SELECT am.manager_id, am.manager_name, am.manager_email, pm.pub_adsense_id, CONCAT(pm.pub_fname, ' ', IFNULL(pm.pub_lname,'')) as name,pm.pub_uniq_id, pm.pub_analytics_id, pm.child_net_code,pm.pub_email,pm.pub_acc_name,pm.pub_acc_new_name,pm.mcm_status,pm.adx_for_display,pm.adx_for_app,pm.adx_for_video FROM publisher_master as pm 
                          LEFT JOIN account_manager as am ON pm.manager_id = am.manager_id
                          WHERE pm.pub_email = '".$rowP['email']."'"; 
                    #prepare query
                    $stmtPub = $this->conn->prepare($queryPub);      
                    #execute query 
                   $stmtPub->execute();
                    return array($stmtPub,5,$row['id'],$row['role_id'],$row['name'],$row['uniq_id'],$row['access_token'],$row['token_count']); 
                 
                }else{
                  return 1 ;
                }

            }else if($row['role_id'] == 7 || $row['role_id'] == 8){

                 $queryFetchP = "SELECT sal_id,sal_name,sal_email FROM sales_team WHERE sal_email ='".$row['email']."' and sal_status !='N'";
                  #prepare query
                  $stmtP = $this->conn->prepare($queryFetchP);
                  #execute query 
                  $stmtP->execute();
                  $stmt_resultP = $stmtP->get_result();
                  $rowP = $stmt_resultP->fetch_array(MYSQLI_ASSOC);
                 $rowsP = $stmt_resultP->num_rows;
                if($rowsP > 0){
                 $stmtPub = array('sales_id'=>$rowP['sal_id'],'pub_name'=>trim($rowP['sal_name']),
                    'pub_email'=>trim($rowP['sal_email'])

                );
                return array($stmtPub,8,$row['id'],$row['role_id'],$row['uniq_id'],$row['access_token'],$row['token_count']);
               }else{
                  return 1 ;
                }
            }
            else
            {
                #query
              $queryPub="SELECT am.manager_id, am.manager_name, am.manager_email, pm.pub_adsense_id, CONCAT(pm.pub_fname, ' ', IFNULL(pm.pub_lname,'')) as name,pm.pub_uniq_id, pm.pub_analytics_id, pm.child_net_code,pm.pub_email,pm.pub_acc_name,pm.pub_acc_new_name,pm.mcm_status,pm.adx_for_display,pm.adx_for_app,pm.adx_for_video FROM publisher_master as pm 
                    LEFT JOIN account_manager as am ON pm.manager_id = am.manager_id
                    WHERE pm.pub_email = '".$this->email."'"; 
              #prepare query
              $stmtPub = $this->conn->prepare($queryPub);      
              #execute query 
             $stmtPub->execute();
              return array($stmtPub,3,$row['id'],$row['role_id'],$row['access_token'],$row['token_count']); 
            }
                 
         }else{
            return 2;
         }
    }else{
      return 1;
    }
    return false;
  }
  #Token Update
  public function tokenUpdate(){

    #update query
    $query = "UPDATE
                " . $this->table_name2 . "
            SET
                access_token = '".$this->token."'
                
            WHERE
                email = '".$this->email."'";
  
    #prepare query statement
    $stmt_token = $this->conn->prepare($query);
     if($stmt_token->execute()){
        return true;
    }
  }
  #Token count Update
  public function tokenCountUpdate(){
  
    #update query
    $query = "UPDATE
                " . $this->table_name2 . "
            SET
                token_count = ".$this->token_counts."
                
            WHERE
                email = '".$this->email."'";
  
    #prepare query statement
    $stmt_token_count = $this->conn->prepare($query);
     if($stmt_token_count->execute()){
        return true;
    }
  }
  #create publishers service
  public function pubServices(){

     $serArr = array_unique($this->service_id);
     if (!in_array("1", $serArr))
      {
       $serArr = \array_diff($serArr, ["2", "3", "4", "5"]);
      }
    
     $i = 0;
     #query to insert record in publisher services table
     foreach ($serArr as $value) {
        if($value == 9){
            $comment = $this->comment;
        }else{
            $comment = "";
        }
        $queryPub = 'INSERT INTO ' . $this->table_name4 . '(service_id, uniq_id, service_comment) VALUES ('.$value.', "'.$this->uniq_id.'", "'.$comment.'")';
        #prepare query
        $stmt_pub = $this->conn->prepare($queryPub);
        #execute query
        $stmt_pub->execute();
        $i++;
     }
     
     if(count($serArr) == $i){
         $html = '
   <html><head>
          <meta charset="utf-8" />
          <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
          <meta http-equiv="X-UA-Compatible" content="IE=edge" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
          <meta name="robots" content="noindex,nofollow" />
          <title>auxo ads</title> 
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
          <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->name).',</p>

          <p style="font-size: 18px; line-height: 33px;">Welcome onboard Auxo Ads!</p>
          <p style="font-size: 18px; line-height: 33px;"> Our team has started a comprehensive analysis of your website. </p>

          <p style="font-size: 18px; line-height: 33px;"> Auxo Ads team aims to help publishers manage the increasing complex world of programmatic monetization, data analysis and traffic management. </p>
          

          <p style="font-size: 18px; line-height: 33px;"> Auxo Ads custom setup leverages data that enables you to follow a comprehensive strategy on website improvements and maximizing earnings via AdX, AdSense and programmatic demand.</p>
          <p style="font-size: 18px; line-height: 33px;">
          <a href="http://safedev.cybermediaservices.in/" style="display: inline-block;
                padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a> to setup your Key Insights section, Bank details and Add more domains.
          </p>


         <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Happy Earnings!</span><br>
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
        $subject = "Welcome Onboard to Google Ad Manager";
        $mailer = $this->mailPub($html,$subject,$this->email);

        return true;
     }
     return false;

   
        

  }

	public function livedemo(){
		
		$this->name=htmlspecialchars(trim(strip_tags($this->name)));
		$this->message=htmlspecialchars(trim(strip_tags($this->message)));
		$this->email=htmlspecialchars(trim(strip_tags($this->email)));
		if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
			return 3;
			exit();      
		}
		$queryLivedemo = 'INSERT INTO ' . $this->table_name5 . '(name, email, message) VALUES ("'.$this->name.'", "'.$this->email.'", "'.$this->message.'")';
		#prepare query
		$stmt_pub = $this->conn->prepare($queryLivedemo);
		if($stmt_pub->execute()){
			
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
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>Sales,</p>

                <p style="font-size: 18px; line-height: 33px;">A live demo request created by '.ucwords($this->name).' </p>

                <p style="font-size: 18px; line-height: 33px;"> Email id :'.$this->email.'</p>
				         <p style="font-size: 18px; line-height: 33px;"> Message :'.$this->message.'</p>
                

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
			#Mail to sales Manager
			$subject = "Enquiry For Live Demo";
            $mailer = $this->mailPub($html,$subject,"priankushc@cybermedia.co.in","dhavalg@cybermedia.co.in");
			
            #Thank you Mail to publisher
			if(!empty($this->name)){
				$body123='<html><head>
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
													<p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->name).',</p>

													<p style="font-size: 18px; line-height: 33px;">Thank you for your interest in Auxo Ads platform demo!</p>
                          <p style="font-size: 18px; line-height: 33px;">Your request is under process. Our team  will get in touch with you soon to schedule a session.</p>
										
								 <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Thanks You,</span><br>
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
				
                $subjectPub = "Auxo Ads Live Demo Request";
                $mailerPub = $this->mailPub($body123,$subjectPub,$this->email);
			}
			
			$pubID = $this->conn->insert_id;
			$resArr = array('pub_id'=>$pubID);
			return $resArr;
			
		}else{
			return false;
		}
	}
	
	
  #Reset Password Mail to Publisher
  public function resetPwdMail(){
     
      
     $request_date = date('d F Y');
     $request_time = date('H:i');       
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
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->name.',</p>

                <p style="font-size: 18px; line-height: 33px;">An account recovery request for your Auxo Ads ID ('.$this->email.') was made on '.$request_date.' at '.$request_time.'. </p>

                <p style="font-size: 18px; line-height: 33px;"> Use this link to reset your password. This link is valid for 24 hours.</p>
                <p> <a href="https://safedev.cybermediaservices.in/authentication/reset/'.$this->uniq_id.'/'.$this->verificationText.'" target="_blank" title="click here Reset my password" style="display: inline-block;
                padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Reset my password</a> </p>

                
                <p style="font-size: 18px; line-height: 33px;"> If you did not make this request or do not recognise the information presented above, contact <a href="mailto: query@cybermedia.co.in" style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 18px;">here</a> immediately to keep your account secure. </p>
               
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
        $subject = "Reset Auxo Ads Password";
        $mailer = $this->mailPub($html,$subject,$this->email);
        if($mailer == 1){
            #update query
        $query = "UPDATE
                    " . $this->table_name2 . "
                SET
                    reset_pwd_flag=1
                    
                WHERE
                    uniq_id = '".$this->uniq_id."'";
  
            #prepare query statement
            $stmt_pwd = $this->conn->prepare($query);
            $stmt_pwd->execute();
            return true;
         }else{
            return false;
         }
        
  }
  public function check_reset_flag(){
        $query='SELECT  id,salt_key FROM ' . $this->table_name2 . ' WHERE uniq_id="'.$this->uniq_id.'" AND reset_pwd_flag=1 AND user_status="Y"';
        #prepare query statement
        $stmt_pwd = $this->conn->prepare($query);
        $stmt_pwd->execute();
            
        return $stmt_pwd;
   }
  #Tour Status
   public function tourStatus(){
        $query='SELECT  tour_status,tour_date FROM ' . $this->table_name2 . ' WHERE uniq_id="'.$this->uniq_id.'" and user_status="Y"';
        #prepare query statement
        $stmt_tr = $this->conn->prepare($query);
        $stmt_tr->execute();
            
        $stmt_result_tour = $stmt_tr->get_result();
        $row = $stmt_result_tour->fetch_array(MYSQLI_ASSOC);
        
        $rowsTour = $stmt_result_tour->num_rows;
         if($rowsTour > 0){
             $statusT = $row['tour_status'];
             if($statusT == 1){
                 $curDate = date("Y-m-d");
                 $tourDate = $row['tour_date']; 
                 $datediff = strtotime($curDate) - strtotime($tourDate);
                 $tourDays = abs(round($datediff / 86400));
                 if($tourDays > 30){
                     #update query
                     $query = "UPDATE
                                  " . $this->table_name2 . "
                              SET
                                  tour_status = 0,tour_date=NULL
                                  
                              WHERE
                                  uniq_id = '".$this->uniq_id."'";
                    
                      #prepare query statement
                      $stmt_tr = $this->conn->prepare($query);
                      $stmt_tr->execute();

                      $status = 0;            
                 }else{
                   $status = $statusT;
                 }

             }else{
                $status = $statusT;
             }
                  
         }
        
         return $status;
   } 
    public function tourStatusUp(){
      $tour_date = date("Y-m-d");
     #update query
       $query = "UPDATE
                    " . $this->table_name2 . "
                SET
                    tour_status = 1,tour_date='".$tour_date."'
                    
                WHERE
                    uniq_id = '".$this->uniq_id."'";
      
        #prepare query statement
        $stmt_tr = $this->conn->prepare($query);
        $stmt_tr->execute();
            return true;
   }
  #Reset Password
  public function resetPassword(){
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
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->name).',</p>

                <p style="font-size: 18px; line-height: 33px;">Thank You! </p>

                <p style="font-size: 18px; line-height: 33px;"> Your password has been succesfully updated. </p>
                
                 <p style="font-size: 18px; line-height: 33px;">For further analysis, tools, and features, please login to your </p>
               <p> 
                <a href="http://safedev.cybermediaservices.in/" style="display: inline-block;
                      padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Auxo Ads Dashboard</a>
                </p>               
               <p style="font-size: 18px; line-height: 33px;">In case of any query, please contact
                <a href="mailto: query@auxoads.com" style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 18px;">here</a>.
                </p>

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
        $subject = "Auxo Ads Reset Password Confirmation";
        $mailer = $this->mailPub($html,$subject,$this->email);
        if($mailer == 1){

        #update query
       $query = "UPDATE
                    " . $this->table_name2 . "
                SET
                    password = '".$this->newpassword."',reset_pwd_flag=0
                    
                WHERE
                    id = ".$this->user_id."";
      
        #prepare query statement
        $stmt_pwd = $this->conn->prepare($query);
        $stmt_pwd->execute();
            return true;
        }else{

          return false;
        }
  }
  #User logout
  public function logout(){
   
    if($this->token_counts <= 1){
     $this->token_counts = 0; 
     $this->tokenCountUpdate();
     $logRes = $this->tokenBlank();
     if($logRes == 1){
      
      if($this->child_net_code != null || $this->child_net_code != 'null')
      {
         $memtest = new Memcached();
         $memtest->addServer("localhost", 11211);
         $allKeys = $memtest->getAllKeys();

        $key_prefix = "KEY_".$this->child_net_code.'_';
         foreach ($allKeys as $index => $key) {
              // If strpos returns 0 (not false) then we have a match.
              if (strpos($key, $key_prefix) === 0) {
                $memtest->delete($key);
              }
            }
        }
        
            return true;
        }else{
            return false;
        }

    } // counts if
    else{

        $this->token_counts = $this->token_counts-1;
        $counRes = $this->tokenCountUpdate();
        if($counRes == 1){
         return true;
       }else{
        return false;
       }
    }
    
  }
  #Access token
  public function tokenBlank(){

    #update query
    $query = "UPDATE
                " . $this->table_name2 . "
            SET
                access_token = ''
                
            WHERE
                id = ".$this->user_id."";
  
    #prepare query statement
    $stmt_pwd = $this->conn->prepare($query);
     if($stmt_pwd->execute()){
        return true;
    }
    return false;
  }
  #inventory settings
  public function invenService(){
         $queryFetch= 'SELECT ps.service_id,s.service_name FROM `publisher_services` ps INNER join services s ON s.serv_id = ps.service_id WHERE ps.uniq_id="'.$this->uniq_id.'" and ps.status=1';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
  }
  #check news letter service
  public function getnews(){
    $queryFetch= 'SELECT email FROM `newsletter_enquiry` WHERE email="'.$this->email.'" and status=1';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
  }
    #create publishers Inventory service
  public function postInvenService(){
     $old_service  = $this->old_service;
     $serArr = array_unique($this->service_id);
       if(empty($old_service)){
          foreach ($serArr as $value) {
            
              $queryPub = 'INSERT INTO ' . $this->table_name4 . '(service_id, uniq_id) VALUES ('.$value.', "'.$this->uniq_id.'")';
              #prepare query
              $stmt_pub = $this->conn->prepare($queryPub);
              #execute query
              $stmt_pub->execute();
              
           }
       }else{
           $result = array_values(array_diff($serArr,$this->old_service));
           //print_r($result);die;
           if(!empty($result)){
           foreach ($result as $value) {
            
              $queryPub = 'INSERT INTO ' . $this->table_name4 . '(service_id, uniq_id) VALUES ('.$value.', "'.$this->uniq_id.'")';
              #prepare query
              $stmt_pub = $this->conn->prepare($queryPub);
              #execute query
              $stmt_pub->execute();
              
           }
          }
       }   

     $serFalArr = array_unique($this->servFalseArr);
     $i = 0;
    if(!empty($serFalArr)){


     foreach ($serFalArr as $val) {
      
        $queryPubUp = 'update ' . $this->table_name4 . ' set status = 0 where uniq_id="'.$this->uniq_id.'" and service_id='.$val.'';
        #prepare query
        $stmt_pubU = $this->conn->prepare($queryPubUp);
        #execute query
        $stmt_pubU->execute();
        $i++;
       }
       if(count($serFalArr) == $i){return true;}
        return false;
        exit;
     }
     
     return true;
    } 
  #create publishers Inventory service
  public function pubNewsServices(){
    $newsLetter = $this->newsletter;
    if($newsLetter == true){

       $queryFetch= 'SELECT email FROM newsletter_enquiry WHERE email = "'.strip_tags($this->email).'"';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
      $stmt_result = $row->get_result();
      $rows = $stmt_result->num_rows;
      if($rows > 0){
        $queryPubUp = 'update newsletter_enquiry set status = 1 where email="'.$this->email.'"';
        #prepare query
        $stmt_pubU = $this->conn->prepare($queryPubUp);
        #execute query
        $stmt_pubU->execute();
      }
      else{
         $queryPub = 'INSERT INTO newsletter_enquiry (name, email) VALUES ("'.$this->name.'", "'.$this->email.'")';
              #prepare query
              $stmt_pub = $this->conn->prepare($queryPub);
              #execute query
              $stmt_pub->execute();
      }
    }else{
        $queryPubUp = 'update newsletter_enquiry set status = 0 where email="'.$this->email.'"';
        #prepare query
        $stmt_pubU = $this->conn->prepare($queryPubUp);
        #execute query
        $stmt_pubU->execute();
    }
  }
  #check pro status
  public function proStatus(){
        $queryPro = 'select pub_ser_id from publisher_services where status=1 and uniq_id="'.$this->uniq_id.'" and service_id IN (4,5)';
        #prepare query
        $stmt_pro = $this->conn->prepare($queryPro);
        #execute query
        $stmt_pro->execute();
        $stmt_result_pro = $stmt_pro->get_result();
        $rowsPro = $stmt_result_pro->num_rows;
         if($rowsPro > 0){
             $status = 1;
         }else{
             $status = 0;
         }
        return $status;    
  }
  #check app status
  public function appStatus(){
     if($this->child_net_code == "demo_9999999"){
      $queryApp = 'select id from mcm_ad_exch_app_report_demo where child_net_code ="'.trim($this->child_net_code).'"  and  DATE(ad_exch_date) >= DATE(NOW() - INTERVAL 6 month) limit 1 ';
       }else{
        $queryApp = 'select id from mcm_ad_exch_app_report where child_net_code ="'.trim($this->child_net_code).'"  and  DATE(ad_exch_date) >= DATE(NOW() - INTERVAL 6 month) limit 1 ';
       }
        #prepare query
        $stmt_app = $this->conn->prepare($queryApp);
        #execute query
        $stmt_app->execute();
        $stmt_result_app = $stmt_app->get_result();
        $rowsApp = $stmt_result_app->num_rows;
         if($rowsApp > 0){
             $status = "true";
         }else{
           $status = "false";
         }
         return $status;
  }
   #check video status
  public function videoStatus(){
    if($this->child_net_code == "demo_9999999"){
      $queryVideo = 'select id from mcm_ad_exch_video_report_demo where child_net_code ="'.trim($this->child_net_code).'"  and  DATE(ad_exch_date) >= DATE(NOW() - INTERVAL 6 month) limit 1 ';
        }else{
           $queryVideo = 'select id from mcm_ad_exch_video_report where child_net_code ="'.trim($this->child_net_code).'"  and  DATE(ad_exch_date) >= DATE(NOW() - INTERVAL 6 month) limit 1 '; 
        }
        #prepare query
        $stmt_video = $this->conn->prepare($queryVideo);
        #execute query
        $stmt_video->execute();
        $stmt_result_video = $stmt_video->get_result();
        $rowsVid = $stmt_result_video->num_rows;
         if($rowsVid > 0){
             $status = "true";
         }else{
           $status = "false";
         }
         return $status;
  }
  #send mail
  public function mailPub($html,$subject,$email,$emailcc=NULL){
    
    include_once('../mailerLib/class.phpmailer.php');
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
            //$mail->addAddress('sandeepy@cmrsl.net');
            $mail->addAddress('shivamj@cmrsl.net');
            if($emailcc != NULL){
                $mail->addCC('ankurdu@cmrsl.net');
            }
            $mail->isHTML(true);
            $mail->Body = $body_bank;
            
            if($mail->Send()){
              
                $mail->ClearAddresses();
                return true;
             }else{
                return false;
             }
  }

}
?>