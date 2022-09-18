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
include_once '../../objects/Subuser.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$subUser = new Subuser($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->subuser_id) &&
    !empty($data->first_name) &&
    !empty($data->last_name) &&
    !empty($data->email) &&
    !empty($data->contact) &&
    !empty($data->role_id) &&
    //!empty($data->password) &&
    !empty($data->uniq_id)
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    
    $rows = $stmt_result->num_rows;
    if($rows > 0){

    #set sub-user property values
    $subUser->email = $data->email;
    //$subUser->password = base64_encode(trim($data->password));
    $subUser->first_name = $data->first_name;
    $subUser->last_name = $data->last_name;
    $subUser->role_id = $data->role_id;
    $subUser->contact = $data->contact;
    $subUser->subuser_id = $data->subuser_id;
    
    $result_fun = $subUser->updateSubuser();
    #create the user
    if($result_fun == 1){
  
        #set response code - 200 ok
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("message" => "Sub-User was updated.","status_code"=>200));
    }else if($result_fun == 2){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "E-Mail Address already exist, please check details.!","status_code"=>422));
    }
    else if($result_fun == 3){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid E-Mail Address.","status_code"=>422));
    }
    
    #if unable to create the user, tell the user
    else{
  
        #set response code - 503 service unavailable
        http_response_code(503);
  
        #tell the user
        echo json_encode(array("message" => "Unable to update sub-user.","status_code"=>503));
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
    echo json_encode(array("message" => "Unable to get sub-user. Data is incomplete.","status_code"=>400));
}
?>