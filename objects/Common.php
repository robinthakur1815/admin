<?php
#Author BY AD
class Common{
  
    #database connection and table name
    private $conn;
    private $table_name = "services";
    private $table_country = "country_code";
    private $table_user = "users";
    private $table_dashhead = "header_revenue";
    private $table_manager = "account_manager";
    private $table_master = "publisher_master";

    private $thirdpartyapi = "thirdparty_api_doc";
	private $table_newsletter = "newsletter_enquiry";
    private $table_category = "category";
	
    #object properties
    public $country_name;
    public $country_code;
    public $access_token;
    public $child_net_code;
    public $manager_id;
    public $pub_uniq_id;
    public $type;
    public $adsense_id;
  	public $name;
  	public $email;
    public $old_acc_name;
    public $new_acc_name;
    public $ads_id;
    public $top_box_type;
    public $top_box_table;
    public $category1_name;
    public $category2_name;
    // public $password;
    
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
   #create get services
    public function services(){
    //$queryFetch= 'SELECT * FROM ' . $this->table_name . '';
    //$queryFetch= 'SELECT c1.serv_id, c1.service_name, c2.serv_id as sub_id,c2.service_name as sub_name FROM ' . $this->table_name . ' c1 LEFT JOIN ' . $this->table_name . ' c2 ON c2.parent_id = c1.serv_id WHERE c1.parent_id = 0 and c1.flag=0';
    $queryFetch= 'SELECT serv_id, service_name,parent_id FROM ' . $this->table_name . ' WHERE flag=0';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    return $row;
    
    }
    #Get country
    public function getCountry(){
    $queryFetch= 'SELECT id,nicename,phonecode FROM ' . $this->table_country . '';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    return $row;
    
    }
    #verify token
    public function verifyToken(){
    //$queryFetch= 'SELECT access_token,salt_key,id FROM ' . $this->table_user . ' WHERE access_token = "'.trim($this->access_token).'" and uniq_id = "'.$this->pub_uniq_id.'"';
    $queryFetch= 'SELECT access_token,salt_key,id,token_count,email FROM ' . $this->table_user . ' WHERE access_token = "'.trim($this->access_token).'" and token_count >0';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    
    return $row;
    
    }
    #get header revenue
    public function headerRevenue(){
    //$queryFetch= 'SELECT * FROM ' . $this->table_dashhead . ' WHERE child_net_code = "'.$this->child_net_code.'" and type="'.$this->type.'"';
    // $queryFetch= 'SELECT SUM(this_month) as this_month,SUM(previous_month) as previous_month FROM ' . $this->table_dashhead . ' WHERE (child_net_code = "'.$this->child_net_code.'" OR adx_p_name = "'.$this->old_acc_name.'" OR adx_p_name = "'.$this->new_acc_name.'" OR adx_p_name = "'.$this->ads_id.'") And (child_net_code IS NOT NULL OR adx_p_name IS NOT NULL)';
   $queryFetch= 'SELECT SUM(this_month) as this_month,SUM(last_month) as previous_month FROM ' . $this->table_dashhead . ' WHERE pub_uniq_id="'.$this->pub_uniq_id.'"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    
    return $row;
    
    }
    #get adsense header revenue
    public function headerAdsRevenue(){
    $queryFetch= 'SELECT * FROM ' . $this->table_dashhead . ' WHERE adx_p_name = "'.$this->adsense_id.'" and type="'.$this->type.'"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    
    return $row;
    
    }
    #Get Account manager details
    public function getAccmanager(){
    $queryFetch= 'SELECT manager_name, manager_email FROM ' . $this->table_manager . ' WHERE manager_id = "'.$this->manager_id.'"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    
    return $row;
    
    }
    public function checkApiDoc(){
      $queryFetch= 'SELECT * FROM ' . $this->publisher_master . ' WHERE unid_id = "'.$this->sites.'"';
      #prepare query
      $row = $this->conn->prepare($queryFetch);
      #execute query 
      $row->execute();
      
      return $row;
      
      }
      public function getapi(){
        $queryFetch= 'SELECT * FROM ' . $this->thirdpartyapi . ' WHERE unid_id = "'.$this->sites.'"';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        
        return $row;
        
        }
    //   public function checkApiDoc1(){
    //     $checkToken = mysqli_query($conn,"select * from thirdparty_api_doc where unid_id='$sites'");
    //     #prepare query
    //     if($checkToken->num_rows >0 ){
    //         echo json_encode(array("message" => "token and uniq_id allready exist in databse","status_code"=>202));
           
    //       }
    //         else{
    //         $sites = $data->uniq_id;
    //         $token = openssl_random_pseudo_bytes(16);
    //         $token = bin2hex($token);
    //         $newtoken = $sites.$token;
    //           $query ='INSERT INTO thirdparty_api_doc(unid_id,access_token)VALUES ("'.$sites.'", "'.$newtoken.'")';
    //           if(mysqli_query($conn,$query))
    //             {
    //            echo json_encode(array("message" => "successfully generated token","status_code"=>200));
    //            exit();  
    //         }
        
    //     }
    // }
    #Get Account manager details
    public function getPublisher(){
    $queryFetch= 'SELECT CONCAT(IFNULL(`pub_fname`, ""), " ", IFNULL(`pub_lname`, "")) as name, pub_email, pub_display_share, pub_app_share, pub_video_share,pub_adsense_share,pub_id FROM ' . $this->table_master . ' WHERE pub_uniq_id = "'.$this->pub_uniq_id.'"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    
    return $row;
    
    }
	#Get Account manager details
    public function newsletter(){
		include_once('../mailerLib/class.phpmailer.php');
		$this->name=htmlspecialchars(trim(strip_tags($this->name)));
		$this->email=htmlspecialchars(trim(strip_tags($this->email)));
		if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
			return 3;
			exit();      
		}
		$queryNewsletter = 'INSERT INTO ' . $this->table_newsletter . '(name, email) VALUES ("'.$this->name.'", "'.$this->email.'")';
		#prepare query
		$stmt_pub = $this->conn->prepare($queryNewsletter);
		if($stmt_pub->execute()){
			$body='<html><head>
                <meta charset="utf-8" />
                <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="robots" content="noindex,nofollow" />
                <title>Auxo Ads</title> 
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
				<p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->name).'</p>

				<p style="font-size: 18px; line-height: 33px;"> Thank you for subscribing to our weekly newsletter!</p>
				<p style="font-size: 18px; line-height: 33px;"> We will be sharing with you regular updates on Auxo Ads platform, and also latest news from across the ad tech industry.</p>

                 <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Happy Reading!</span><br>
	          
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
			$body_bank = $body;
			$mail  = new PHPMailer();
			$mail->IsSMTP();  
			$mail->Host       = "103.76.212.101";
			$mail->SMTPDebug  = 1; 
			$mail->SMTPAuth   = true;   
			$mail->Username   = 'noreply@cybermedia.co.in';
			$mail->Password   = 'K6Cx*5G%W8j';
			$mail->Port = "587";
			$mail->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
			$mail->Subject = "Subscribe to Auxo Ads Newsletter";           
			$mail->addAddress('sandeepy@cmrsl.net');
      $mail->isHTML(true);
			$mail->Body = $body_bank;
			$mail->Send();
			$mail->ClearAddresses();
			
			$pubID = $this->conn->insert_id;
			$resArr = array('newsletter_id'=>$pubID);
			return $resArr;
		}else{
			return false;
		}
    }
	
	public function getcategory(){
		$queryFetch= 'SELECT id,Tier_1 FROM '.$this->table_category.' WHERE Tier_1!="" GROUP BY Tier_1 ORDER BY Tier_1 ASC';
		$row = $this->conn->prepare($queryFetch);
		$row->execute();
		return $row;    
    }
	public function getcategory2(){
		$queryFetch= 'SELECT id,Tier_2 FROM '.$this->table_category.' WHERE Tier_2!="" AND Tier_1 ="'.$this->category1_name.'" GROUP BY Tier_2 ORDER BY Tier_2 ASC';
		$row = $this->conn->prepare($queryFetch);
		$row->execute();
		return $row;    
    }
	public function getcategory3(){
		$queryFetch= 'SELECT id,Tier_3 FROM '.$this->table_category.' WHERE Tier_3!="" AND Tier_2 ="'.$this->category2_name.'" GROUP BY Tier_3 ORDER BY Tier_3 ASC';
		$row = $this->conn->prepare($queryFetch);
		$row->execute();
		return $row;    
    }
    #get verticals
    public function getVerticalsData(){
		$queryFetch= 'SELECT * from verticals order by vname ASC';
		$row = $this->conn->prepare($queryFetch);
		$row->execute();
		$stmt_result = $row->get_result();
    $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
		return $resp;    
    }
}
?>