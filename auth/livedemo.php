<?php
#Author BY Sandeep Yadav
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
    !empty($data->name) &&
    !empty($data->email) &&
    !empty($data->message)
){
	#set user property values
    $users->email = $data->email;
	$users->name = $data->name;
	$users->message = $data->message;
	$livedemo = $users->livedemo();
	if(is_array($livedemo)){
		#set response code - 201 created
        http_response_code(201);
  
        #tell the user
        echo json_encode(array("message" => "live demo requested created","status_code"=>201));
	}else{
		if($livedemo == 3){
		   #set response code - 422 validation error
			http_response_code(422);
			#tell the user
			echo json_encode(array("message" => "Invalid E-Mail Address","status_code"=>422));
		}else{
			#set response code - 503 service unavailable
			http_response_code(503);
			#tell the user
			echo json_encode(array("message" => "Unable to request for live demo","status_code"=>503));
		}
	}
}
#tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to request for live demo. Data is incomplete.","status_code"=>400));
}