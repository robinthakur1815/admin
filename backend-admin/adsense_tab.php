<?php
#Author BY SS
#error reporting
// error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
include_once '../objects/BackendAdmin.php';
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];
if(!empty($data->uniq_id)) { 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
	  if($rows > 0){
	  $topAdx = new BackendAdmin($db,$dbMongoDb, $data->strtdate, $data->enddate);
	  $result_fun = $topAdx->AdsenseTab();
    $datafound = json_decode($result_fun);
	  if(!empty($datafound)){
    # JSON-encode the response
		$json_response = json_encode(array("data"=>$datafound, "message" => "success"));
		# Return the response
		echo $json_response;
	}
  else{
		#set response code - 503 validation error
		http_response_code(503);
		#tell the user
		echo json_encode(array("message" => "No data found"));
	}
}
else{
	#set response code - 422 validation error
	http_response_code(422);
	#tell the user
	echo json_encode(array("message" => "Invalid token"));
  }
}
#tell the user data is incomplete
else{
   #set response code - 400 bad request
   http_response_code(400);
   #tell the user
   echo json_encode(array("message" => "Unable to get Data.Data is incomplete."));
}       
?>