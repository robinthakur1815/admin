<?php
#Author BY SS
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
include_once '../objects/Common.php';
include_once '../config/connection.php';
include_once '../objects/Salesapi.php';
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$dbMongoDb = $database->getConnectionMongoDb();
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];
if(
	!empty($data->strtdate) && 
	!empty($data->enddate) && 
	!empty($data->uniq_id) &&
	!empty($data->sales_id)
	) { 
	$header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
	if($rows > 0){
	$sales = new Salesapi($db,$dbMongoDb, $data->strtdate, $data->enddate);
	$sales->sales_id = $data->sales_id;
	$result_fun = $sales->allpubssalesuser();
	$datafound = json_decode($result_fun);
	if(!empty($datafound)){
		# JSON-encode the response
		$json_response = json_encode(array("data"=>$datafound, "message" => "success","status_code"=>200));
		# Return the response
		echo $json_response;
	}
	else{
		#set response code - 503 validation error
		http_response_code(503);
		#tell the user
		echo json_encode(array("message" => "No data found","status_code"=>503));
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
    echo json_encode(array("message" => "Unable to get Data.Incomplete Data.","status_code"=>400));
}
         
?>