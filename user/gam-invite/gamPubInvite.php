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
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Invite.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$pubInvite = new Invite($db);

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->domainName)
    
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun1 = $header->verifyToken();
    $stmt_result1 = $result_fun1->get_result();
    $rows = $stmt_result1->num_rows;
    if($rows > 0){

        $result_fun = $header->getPublisher();
        $stmt_result = $result_fun->get_result();
        $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);

        #set gam invite property values 
        
       
        $pubInvite->domainName = $data->domainName;
        $pubInvite->uniq_id = $data->uniq_id;
        $pubInvite->pub_id = $rowPub['pub_id'];
        $pubInvite->pub_name = $rowPub['pub_name'];
        $pubInvite->email = $data->email;
        $pubInvite->gamAcc = $data->gamAcc;
        
        $result_inv = $pubInvite->getPubInvite();
     
        // echo "<pre>";
        // print_r($result_inv['msg']);die;
        if(!empty($result_inv)){
            
         if($result_inv['msg'] == "false"){
             #set response code - 422 validation error
                http_response_code(422);
    
               #tell the user
              echo json_encode($result_inv);
            }else{
            #set response code - 200 ok
            http_response_code(200);
            # JSON-encode the response
            $json_response = json_encode(array("invite"=>$result_inv,"status_code"=>200));

            # Return the response
            echo $json_response;
           }

         }
         else{
            
            #set response code - 204 No content
            http_response_code(204);
      
            #tell the user
            echo json_encode(array("message" => "Something went wrong","status_code"=>204));
         }

       }
     else{
        #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid token","status_code"=>422));
      }
}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to post domain. Data is incomplete.","status_code"=>400));
}
?>