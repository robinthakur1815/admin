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
include_once '../objects/Common.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$common = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));

#make sure data is not empty
if(
    !empty($data->name) &&
    !empty($data->email)
){
	#set user property values
    $common->email = $data->email;
	$common->name = $data->name;
	
	
	// print_r($data);die;
	$newsletter = $common->newsletter();
	
	if(is_array($newsletter)){
		#set response code - 200 created
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("message" => "Newsletter requested created","status_code"=>200));
	}else{
		if($newsletter == 3){
		   #set response code - 422 validation error
			http_response_code(422);
			#tell the user
			echo json_encode(array("message" => "Invalid E-Mail Address","status_code"=>422));
		}else{
			#set response code - 503 service unavailable
			http_response_code(503);
			#tell the user
			echo json_encode(array("message" => "Unable to request for newsletter","status_code"=>503));
		}
	}
	
}
#tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to request for newsletter. Data is incomplete.","status_code"=>400));
}