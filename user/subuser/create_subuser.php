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
    !empty($data->parent_email) &&
    !empty($data->parent_id) &&
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
    #create sub-user uniq id, salt key and password
    $subUser->uniq_id = "UNIQ@".date("jnY")."_".date("his");
    $salt = substr(uniqid(), 0, 13);
    $pwdTouser = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    
    //$subUser->password = base64_encode(trim($data->password)).$salt;
    $subUser->password = base64_encode(trim($pwdTouser)).$salt;
    $subUser->salt_id = $salt;
    $subUser->first_name = $data->first_name;
    $subUser->last_name = $data->last_name;
    $subUser->role_id = $data->role_id;
    $subUser->contact = $data->contact;
    $subUser->parent_id = $data->parent_id;
    $subUser->parent_email = $data->parent_email;
    //$subUser->pwd = trim($data->password);
    $subUser->pwd = trim($pwdTouser);
    
    $result_fun = $subUser->createSubuser();
    #create the user
    if($result_fun == 1){
  
        #set response code - 201 created
        http_response_code(201);
  
        #tell the user
        echo json_encode(array("message" => "Sub-User was created","status_code"=>201));
    }else if($result_fun == 2){
       #set response code - 422 validation error
       http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "E-Mail Address already exists, please check details!","status_code"=>422));
    }
    else if($result_fun == 3){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid E-Mail Address","status_code"=>422));
    }
    
    #if unable to create the user, tell the user
    else{
  
        #set response code - 503 service unavailable
        http_response_code(503);
  
        #tell the user
        echo json_encode(array("message" => "Unable to create sub-user","status_code"=>503));
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
    echo json_encode(array("message" => "Unable to create subuser. Data is incomplete.","status_code"=>400));
}
?>