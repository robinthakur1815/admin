<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK");
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/User.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$users = new User($db);
#get posted data
$data = json_decode(file_get_contents("php://input"), true);

#make sure data is not empty
if(
    !empty($data['email']) &&
    !empty($data['password'])
){
	
    #set user property values 
    $users->email = $data['email'];
    $users->password = $data['password'];
    
    $result_fun = $users->login();

    if(is_array($result_fun)){
       $user_id = $result_fun[2];
       $role_id = $result_fun[3];
       
     
       #condtion for Admin Role
       if($result_fun[1] == 4){
          die;
            $token = openssl_random_pseudo_bytes(16);
            $token = bin2hex($token);
            $token_pub = $token;  
            $users->token = $token_pub;
            $users->tokenUpdate();

                $request_array=array(
                'pub_name'=>trim($result_fun[0]['pub_name']),
                'childNetworkCode'=>"",
                'ads_id'=>"",
                'anlytics_id'=>"",
                'uniq_id'=>"",
                'startdate'=>date('F d , Y'),
                'enddate'=>date('F d , Y', strtotime(' -6 day')),
                'acc_mgr_id'=>"",
                'email'=>$result_fun[0]['pub_email'],
                'user_id'=>$user_id,
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'success'=>True
                );
             # JSON-encode the response
            $json_response = json_encode($request_array);

            # Return the response
            echo $json_response;

       }
       #condtion for OBM Role
       if($result_fun[1] == 6){
          

            $acc_token = $result_fun[5];
            if($acc_token != ''){
                $token_pub = $acc_token;
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                    
            }
           
            if(empty($result_fun[5])){
                $token = openssl_random_pseudo_bytes(16);
                $token = bin2hex($token);
                $token_pub = $result_fun[4].$token;  
                $users->token = $token_pub;
                $users->tokenUpdate();
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                
            }
                $request_array=array(
                'pub_name'=>trim($result_fun[0]['pub_name']),
                'uniq_id'=>$result_fun[4],
                'email'=>$result_fun[0]['pub_email'],
                'user_id'=>$user_id,
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'success'=>True
                );
             # JSON-encode the response
            $json_response = json_encode($request_array);

            # Return the response
            echo $json_response;

       }
	   #condtion for Account Manager Role
       if($result_fun[1] == 7){
          
            $acc_token = $result_fun[5];
            if($acc_token != ''){
                $token_pub = $acc_token;
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                    
            }
           
            if(empty($result_fun[5])){
                $token = openssl_random_pseudo_bytes(16);
                $token = bin2hex($token);
                $token_pub = $result_fun[4].$token;  
                $users->token = $token_pub;
                $users->tokenUpdate();
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                
            }

                $request_array=array(
                'pub_name'=>trim($result_fun[0]['pub_name']),
                'uniq_id'=>$result_fun[4],
                'email'=>$result_fun[0]['pub_email'],
                'user_id'=>$result_fun[0]['manager_id'],//manager id
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'success'=>True
                );
             # JSON-encode the response
            $json_response = json_encode($request_array);

            # Return the response
            echo $json_response;

       }
	   #condtion for Sales Role
       if($result_fun[1] == 8){
          
             $acc_token = $result_fun[5];
            if($acc_token != ''){
                $token_pub = $acc_token;
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                    
            }
           
            if(empty($result_fun[5])){
                $token = openssl_random_pseudo_bytes(16);
                $token = bin2hex($token);
                $token_pub = $result_fun[4].$token;  
                $users->token = $token_pub;
                $users->tokenUpdate();
                $token_count = $result_fun[6];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                
            }
                $request_array=array(
                'pub_name'=>trim($result_fun[0]['pub_name']),
                'uniq_id'=>$result_fun[4],
                'email'=>$result_fun[0]['pub_email'],
                'user_id'=>$result_fun[0]['sales_id'],//sales id
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'success'=>True
                );
             # JSON-encode the response
            $json_response = json_encode($request_array);

            # Return the response
            echo $json_response;

       }
       #condtion for Publisher Role
        
       if($result_fun[1] == 3){
          
         $stmt_result_pub = $result_fun[0]->get_result();
         $rowPub = $stmt_result_pub->fetch_array(MYSQLI_ASSOC);
         $rowsPub = $stmt_result_pub->num_rows;
         if($rowsPub > 0){
            $acc_token = $result_fun[4];
            if($acc_token != ''){
                $token_pub = $acc_token;
                $token_count = $result_fun[5];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                    
            }
           
            if(empty($result_fun[4])){
                $token = openssl_random_pseudo_bytes(16);
                $token = bin2hex($token);
                $token_pub = $rowPub['pub_uniq_id'].$token;  
                $users->token = $token_pub;
                $users->tokenUpdate();
                $token_count = $result_fun[5];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                
            }
			

             $users->child_net_code = $rowPub['child_net_code'];
              #appStatus
             //$resultApp = $users->appStatus();
             #videoStatus
             //$resultVid = $users->videoStatus();
             #tourStatus
             $users->uniq_id = $rowPub['pub_uniq_id'];
             $resultTour = $users->tourStatus();
             #prostatus
             $resultPro = $users->proStatus();
               $pub_Name = trim($rowPub['name']) !='' ? trim($rowPub['name']) : NULL;
                $request_array=array(
                'pub_name'=> $pub_Name,
                'pub_acc_name'=>$rowPub['pub_acc_name'],
                'pub_acc_new_name'=>$rowPub['pub_acc_new_name'],
                'childNetworkCode'=>$rowPub['child_net_code'],
                'ads_id'=>'ca-'.$rowPub['pub_adsense_id'],
                'anlytics_id'=>$rowPub['pub_analytics_id'],
                'uniq_id'=>$rowPub['pub_uniq_id'],
                'startdate'=>date('F d , Y'),
                'enddate'=>date('F d , Y', strtotime(' -6 day')),
                'acc_mgr_id'=>$rowPub['manager_id'],
                'email'=>$rowPub['pub_email'],
                'user_id'=>$user_id,
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'mcm_status'=>$rowPub['mcm_status'],
                'display_status'=>$rowPub['adx_for_display'],
                'app_status'=>$rowPub['adx_for_app'],
                'video_status'=>$rowPub['adx_for_video'],
                'pro_status'=>$resultPro,
                'tour_status'=>$resultTour,
                'success'=>True
                );
         
			
            # JSON-encode the response
			$json_response = json_encode($request_array);

			# Return the response
			echo $json_response;

         }else{
         	#set response code - 422 No content
	        http_response_code(422);
	  
	        #tell the user
	        echo json_encode(array("message" => "No Data Found!","status_code"=>422));
         }
    }
       #condtion for Subuser Role
       if($result_fun[1] == 5){
                
         $stmt_result_pub = $result_fun[0]->get_result();
         $rowPub = $stmt_result_pub->fetch_array(MYSQLI_ASSOC);
         $rowsPub = $stmt_result_pub->num_rows;
         if($rowsPub > 0){
            
            $acc_token = $result_fun[6];
            if($acc_token != ''){
                $token_pub = $acc_token;
                $token_count = $result_fun[7];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                    
            }
           
            if(empty($result_fun[6])){
                $token = openssl_random_pseudo_bytes(16);
                $token = bin2hex($token);
                $token_pub = $rowPub['pub_uniq_id'].$token;  
                $users->token = $token_pub;
                $users->tokenUpdate();
                $token_count = $result_fun[7];
                $users->token_counts = $token_count + 1;
                $users->tokenCountUpdate();
                
            }

             $users->child_net_code = $rowPub['child_net_code'];
              #appStatus
             //$resultApp = $users->appStatus();
             #videoStatus
             //$resultVid = $users->videoStatus();
             #tourStatus
             $users->uniq_id = $result_fun[5];
             #prostatus
             $resultPro = $users->proStatus();
             $resultTour = $users->tourStatus();
                $request_array=array(
                'pub_name'=>trim($result_fun[4]),
                'pub_acc_name'=>$rowPub['pub_acc_name'],
                'pub_acc_new_name'=>$rowPub['pub_acc_new_name'],
                'childNetworkCode'=>$rowPub['child_net_code'],
                'ads_id'=>'ca-'.$rowPub['pub_adsense_id'],
                'anlytics_id'=>$rowPub['pub_analytics_id'],
                'uniq_id'=>$rowPub['pub_uniq_id'],
                'startdate'=>date('F d , Y'),
                'enddate'=>date('F d , Y', strtotime(' -6 day')),
                'acc_mgr_id'=>$rowPub['manager_id'],
                'email'=>$data['email'],
                'user_id'=>$user_id,
                'role_id'=>$role_id,
                'access_token'=>$token_pub,
                'mcm_status'=>$rowPub['mcm_status'],
                'display_status'=>$rowPub['adx_for_display'],
                'app_status'=>$rowPub['adx_for_app'],
                'video_status'=>$rowPub['adx_for_video'],
                'pro_status'=>$resultPro,
                'tour_status'=>$resultTour,
                'success'=>True
                );
         
            
            # JSON-encode the response
            $json_response = json_encode($request_array);

            # Return the response
            echo $json_response;

         }else{
            #set response code - 422 No content
            http_response_code(422);
      
            #tell the user
            echo json_encode(array("message" => "No Data Found!","status_code"=>422));
         }
    }
    

   }
    else{
     if($result_fun == 1){
    	#set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid credentials","status_code"=>422));
    }else if($result_fun == 2){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid credentials","status_code"=>422));
    }
    #if unable to get the user, tell the user
    else{
  
        #set response code - 503 service unavailable
        http_response_code(503);
  
        #tell the user
        echo json_encode(array("message" => "Unable to get user details.","status_code"=>503));
    }
  }
}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to login user. Data is incomplete.","status_code"=>400));
}
?>