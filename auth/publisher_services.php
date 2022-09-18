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
include_once '../objects/Common.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$users = new User($db);
$common = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
#make sure data is not empty
if(
	!empty($data->service_id)&&
    !empty($data->uniq_id)
     
){
    #set user property values 
    $users->service_id = $data->service_id;
    $users->uniq_id = $data->uniq_id;
    $users->comment = $data->comment;
    $common->pub_uniq_id = $data->uniq_id;
    $res = $common->getPublisher();
    $stmt_result = $res->get_result();
    $rowArr = $stmt_result->fetch_array(MYSQLI_ASSOC);

    $users->name = $rowArr['name'];
    #create the services
    if($users->pubServices()){
  
        #set response code - 201 created
        http_response_code(201);
  
        #tell the user
        echo json_encode(array("message" => "Services was created.","status_code"=>201));
    
    }
    #if unable to get the user, tell the user
    else{
  
        #set response code - 503 service unavailable
        http_response_code(503);
  
        #tell the user
        echo json_encode(array("message" => "Unable to create services.","status_code"=>503));
    }
 }
  #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to create services . Data is incomplete.","status_code"=>400));
}   
?>