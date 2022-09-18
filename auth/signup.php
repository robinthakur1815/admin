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
#include database and object files
include_once '../config/connection.php';
include_once '../objects/User.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$users = new User($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
 
#make sure data is not empty
if(
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->f_name) &&
    //!empty($data->l_name) &&
    // !empty($data->domain) &&
    !empty($data->business_type) &&
    !empty($data->country_code) &&
    !empty($data->contact)
){
  
    #set user property values
    $users->email = $data->email;
    #create publisher uniq id, salt key and password
    $users->uniq_id = "UNIQ@".date("jnY")."_".date("his");
    $salt = substr(uniqid(), 0, 13);
    $users->password = base64_encode(trim($data->password)).$salt;
    $users->salt_id = $salt;
    $users->f_name = $data->f_name;
    $users->l_name = $data->l_name;
    $users->domain = $data->domain;
    $users->country_code = $data->country_code;
    $users->contact = $data->contact;
    $users->business_type = $data->business_type;
    
    $result_fun = $users->create();
    
    #create the user
    if($result_fun == 1){

       #set response code - 201 created
        http_response_code(201);
  
        #tell the user
        echo json_encode(array("uniq_id"=>$users->uniq_id,"message" => "User was created","status_code"=>201));

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
    else if($result_fun == 4){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid Domain","status_code"=>422));
    }
    else if($result_fun == 5){
       #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Domain already exists, please check details!","status_code"=>422));
    }
      #if unable to create the user, tell the user
    else{
  
        #set response code - 503 service unavailable
        http_response_code(503);
  
        #tell the user
        echo json_encode(array("message" => "Unable to create user","status_code"=>503));
    }
  
  

}
  #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to create user. Data is incomplete.","status_code"=>400));
}
?>
