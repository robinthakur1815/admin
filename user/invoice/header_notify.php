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
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Invoice.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$pubInvoice = new Invoice($db,$dbMongoDb);

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty

if(!empty($data->uniq_id)){
	#set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
		#set invoice property values 
		$pubInvoice->uniq_id = $data->uniq_id;
		$notify_details = $pubInvoice->getnotification();
		$bank_details = $pubInvoice->getBankDetails();
		$notifyarray = array();
		if(!empty($bank_details)){
			if(!empty($notify_details)){
				#set response code - 200 ok
				http_response_code(200);
				# JSON-encode the response
				$json_response = json_encode(array("message" => "Approval pending invoice found!","status_code"=>200));
				# Return the response
				echo $json_response;
			}else{
				#set response code - 204 No content
				http_response_code(204);
		  
				#tell the user
				echo json_encode(array("message" => "No Data Found!","status_code"=>204));
			}
		}else{
			#set response code - 201 ok
            http_response_code(201);
            # JSON-encode the response
            $json_response = json_encode(array("message" => "Bank details not updated!","status_code"=>201));

            # Return the response
            echo $json_response;
		}
	}else{
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
    echo json_encode(array("message" => "Unable to get invoice. Data is incomplete.","status_code"=>400));
}
?>