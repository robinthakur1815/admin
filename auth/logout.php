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
include_once '../objects/Common.php';
include_once '../objects/User.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$verify = new Common($db);
$users = new User($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id)&&
    // !empty($data->child_net_code)&&
    !empty($token)
){
    #set token property values 
    $verify->access_token = trim($token);
    $verify->pub_uniq_id = $data->uniq_id;
    $result_fun = $verify->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
        #set reset property values 
        $users->user_id = $row['id'];
        $users->token_counts = $row['token_count'];
        $users->email = $row['email'];
        $users->child_net_code = $data->child_net_code;
        
        if($users->logout()){

            #set response code - 200 ok
            http_response_code(200);
          
            #tell the user
            echo json_encode(array("message" => "User successfully logout.","status_code"=>200));
        }
        #if unable to logout the user, tell the user
        else{
      
            #set response code - 503 service unavailable
            http_response_code(503);
      
            #tell the user
            echo json_encode(array("message" => "Unable to logout","status_code"=>503));
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
    echo json_encode(array("message" => "Unable to logout. Data is incomplete.","status_code"=>400));
}
?>