<?php
#Author BY ST
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
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
include_once '../../objects/Bankdetail.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$Bankdetail = new Bankdetail($db);

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];

#make sure data is not empty
if(!empty($data->uniq_id)){

	// $header->access_token = trim($token);
    // $header->pub_uniq_id = $data->uniq_id;
    // $result_fun = $header->verifyToken();
    // $stmt_results = $result_fun->get_result();
    // $rowPub = $stmt_results->fetch_array(MYSQLI_ASSOC);
    // $rowsPub = $stmt_results->num_rows;
	
	$header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rowsPub = $stmt_result->num_rows;
	
	
	if ($rowsPub > 0) {
	$Bankdetail->uniq_id = $data->uniq_id;
	// $stmt_result = $Bankdetail->getbankdetail(); 
	$stmt_result = $Bankdetail->getpub_bankdetail(); 
	$stmt_res = $stmt_result->get_result();
	$rows = $stmt_res->fetch_all(MYSQLI_ASSOC); 
	$row = $stmt_res->num_rows;
		if($row > 0){
			
			foreach($rows as $key =>$value){
				if(!empty($value['gst_certificate'])){
					$ext = pathinfo(
						parse_url($value['gst_certificate'], PHP_URL_PATH), 
						PATHINFO_EXTENSION
					);
					$rows[0]['gst_extn'] =$ext;
				}else{
					$rows[0]['gst_extn'] ='';
				}
				if(!empty($value['pan_card_file'])){
					$ext = pathinfo(
						parse_url($value['pan_card_file'], PHP_URL_PATH), 
						PATHINFO_EXTENSION
					);
					$rows[0]['pan_extn'] =$ext;
				}else{
					$rows[0]['pan_extn'] ='';
				}
				if(!empty($value['aadhaar_card_file'])){
					$ext = pathinfo(
						parse_url($value['aadhaar_card_file'], PHP_URL_PATH), 
						PATHINFO_EXTENSION
					);
					$rows[0]['aadhar_extn'] =$ext;
				}else{
					$rows[0]['aadhar_extn'] ='';
				}
				if(!empty($value['incorp_certificate_fille'])){
					$ext = pathinfo(
						parse_url($value['incorp_certificate_fille'], PHP_URL_PATH), 
						PATHINFO_EXTENSION
					);
					$rows[0]['incorp_extn'] =$ext;
				}else{
					$rows[0]['incorp_extn'] ='';
				}
				if(!empty($value['cancel_check_file'])){
					$ext = pathinfo(
						parse_url($value['cancel_check_file'], PHP_URL_PATH), 
						PATHINFO_EXTENSION
					);
					$rows[0]['cheque_extn'] =$ext;
				}else{
					$rows[0]['cheque_extn'] ='';
				}
			}
			// echo"<pre>";
			// print_r($rows);die;
			http_response_code(200);
			# JSON-encode the response
			$json_response = json_encode(array("resultdata"=>$rows,"message" => "success","status_code"=>200));
			# Return the response
			echo $json_response;
		}else{
         	#set response code - 404 Not found
	        http_response_code(404);
	  		#tell the user
	        echo json_encode(array("message" => "No Data Found!","status_code"=>404));
        }

	}
 	#tell the user data is incomplete
	else{
  	#set response code - 400 bad request
    http_response_code(422);
  	#tell the user
    echo json_encode(array("message" => "Invalid token ss","status_code"=>422));
	}

}
 #tell the user data is incomplete
else{
  	#set response code - 400 bad request
    http_response_code(400);
  	#tell the user
    echo json_encode(array("message" => "Unable to get data","status_code"=>400));
}

?>