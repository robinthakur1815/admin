<?php
#Author BY ST
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK"); 
#Time Zone
//date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Common.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$country = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));

$result = $country->getCountry();
$stmt_result = $result->get_result();
$row = $stmt_result->fetch_all(MYSQLI_ASSOC);
$rows = $stmt_result->num_rows;
	if($rows > 0){
           # JSON-encode the response
			$json_response = json_encode(array("country"=>$row,"status_code"=>200));

			# Return the response
			echo $json_response;
		}else{
         	#set response code - 404 Not found
	        http_response_code(404);
	  
	        #tell the user
	        echo json_encode(array("message" => "No Data Found!","status_code"=>404));
         }
         
?>