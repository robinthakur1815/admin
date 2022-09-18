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
#Time Zone
//date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Sales.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
#get posted data
$data = json_decode(file_get_contents("php://input"));
if(!empty($data->strtdate) && !empty($data->enddate)) { 
	$sales = new Sales($db,$dbMongoDb, $data->strtdate, $data->enddate);

	$result_fun = $sales->getrevenue();
	if(isset($result_fun) && !empty($result_fun)){
			
           # JSON-encode the response
			$json_response = json_encode($result_fun);

			# Return the response
			echo $json_response;
	}

}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "No Data Found!"));
}

         
?>