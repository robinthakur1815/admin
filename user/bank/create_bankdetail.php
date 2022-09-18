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
$data = $_POST;
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];
// print_r($_POST);die;
#make sure data is not empty

$countryId = $data['org_country'];

if(
	!empty($data['uniq_id']) &&
	!empty($data['org_type']) &&
	!empty($data['org_country']) &&
	!empty($data['org_address']) &&
	!empty($data['org_city']) &&
	!empty($data['org_state']) &&
	!empty($data['org_postelcode']) &&
	!empty($data['acc_holder_name']) &&
	!empty($data['bank_name']) &&
	!empty($data['account_number']) &&
	!empty($data['bank_address']) &&
	!empty($data['bank_city']) &&
	!empty($data['bank_state']) &&
	!empty($data['bank_postalcode']) &&
	!empty($data['bank_acctype']) &&
	!empty($data['GST_status'])
){
	
	$gstStatus = $data['GST_status'];
	$countryId = $data['org_country'];
	if($gstStatus=='yes'){
		if(empty($data['GST_num']) || empty($data['GST_address']) || empty($data['GST_city']) || empty($data['GST_state']) || empty($data['GST_postalcode']) || empty($data['GST_file'])){
			http_response_code(401);
			echo json_encode(array("message" => "Unable to create bank details. Data is incomplete.","status_code"=>401));
			exit();
		}
	}
	if($countryId==99){
		if(empty($data['bank_ifsc']) || empty($data['pan_file']) || empty($data['aadhaar_file']) || empty($data['cancelcheck_file']) || empty($data['incorp_file'])){
			http_response_code(402);
			echo json_encode(array("message" => "Unable to create bank details. Data is incomplete.","status_code"=>402));
			exit();
		}
	}else{
		if(empty($data['bank_swift_code']) || empty($data['incorp_file'])){
			http_response_code(403);
			echo json_encode(array("message" => "Unable to create bank details. Data is incomplete.","status_code"=>403));
			exit();
		}
	}
	
	
	#set token property values 
	$header->access_token = trim($token);
	$header->pub_uniq_id = $data['uniq_id'];
	$result_fun = $header->verifyToken();
	$stmt_result = $result_fun->get_result();
	$rows = $stmt_result->num_rows;
	if($rows > 0){
		#set user property values
		$Bankdetail->uniq_id = $data['uniq_id'];
		$Bankdetail->acc_holder_name = $data['acc_holder_name'];
		$Bankdetail->org_address = $data['org_address'];
		$Bankdetail->bank_name = $data['bank_name'];
		$Bankdetail->account_number = $data['account_number'];
		$Bankdetail->bank_acctype = $data['bank_acctype'];
		if($countryId==99){
			$Bankdetail->PAN_num = $data['PAN_num'];
			$Bankdetail->bank_ifsc = $data['bank_ifsc'];
			$Bankdetail->bank_swift_code = null;
			$Bankdetail->aadhaar_card = $Bankdetail->generateAadhaar($data['aadhaar_file']);
			$Bankdetail->pan_card = $Bankdetail->generateImage($data['pan_file']);
			// $Bankdetail->pan_card = $Bankdetail->generateImage('pan_file');
			$Bankdetail->check_copy = $Bankdetail->generateCheck($data['cancelcheck_file']);
		}else{
			$Bankdetail->bank_ifsc = null;
			$Bankdetail->bank_swift_code = $data['bank_swift_code'];
			$Bankdetail->PAN_num = null;
			$Bankdetail->aadhaar_card = null;
			$Bankdetail->pan_card = null;
			$Bankdetail->check_copy = null;
		}
		if($gstStatus=='yes'){
			$Bankdetail->GST_num = $data['GST_num'];
			$Bankdetail->GST_address = $data['GST_address'];
			$Bankdetail->GST_city = $data['GST_city'];
			$Bankdetail->GST_state = $data['GST_state'];
			$Bankdetail->GST_postalcode = $data['GST_postalcode'];
			$Bankdetail->gst_certify = $Bankdetail->generateGst($data['GST_file']);
		}else{
			$Bankdetail->GST_num = null;
			$Bankdetail->GST_address = null;
			$Bankdetail->GST_city = null;
			$Bankdetail->GST_state = null;
			$Bankdetail->GST_postalcode = null;
			$Bankdetail->gst_certify = null;
		}
		
		
		$Bankdetail->bank_address = $data['bank_address'];
		$Bankdetail->bank_city = $data['bank_city'];
		$Bankdetail->bank_state = $data['bank_state'];
		$Bankdetail->bank_postalcode = $data['bank_postalcode'];
		$Bankdetail->org_city = $data['org_city'];
		$Bankdetail->org_state = $data['org_state'];
		$Bankdetail->org_country = $data['org_country'];
		$Bankdetail->org_postalcode = $data['org_postelcode'];
		$Bankdetail->org_type = $data['org_type'];
		
		$Bankdetail->incorp_cf = $Bankdetail->generateIncorpcf($data['incorp_file']);
		
		$result_fun = $Bankdetail->createbankdetail();
		if($result_fun == 1){
			http_response_code(200);
			echo json_encode(array("message" => "Bank details created successfully.","status_code"=>200));
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "Unable to create bank details.","status_code"=>503));
		}
	}else{
		http_response_code(422);
		echo json_encode(array("message" => "Invalid token","status_code"=>422));
	}
}else{
	http_response_code(407);
	echo json_encode(array("message" => "Unable to create bank details. Data is incomplete.","status_code"=>407));
}




?>