<?php
	#Author BY SY
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
	#for number format
	ini_set('serialize_precision', 10);
	#include database and object files
	include_once '../config/connection.php';
	include_once '../objects/Common.php';
	include_once '../objects/Accmgr.php';

	#instantiate database and product object
	$database = new Database();
	$db = $database->getConnection();
	$dbMongoDb = $database->getConnectionMongoDb();
	$header = new Common($db);
	$accmgr = new Accmgr($db,$dbMongoDb);
	#get posted data
	$data = json_decode(file_get_contents("php://input"));
	$headers = apache_request_headers();
	preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
	$token = $matches[1];
	#make sure data is not empty
	if(
		!empty($data->uniq_id) && 
		!empty($data->manager_id) 
	){
		#set token property values 
		$header->access_token = trim($token);
		$header->pub_uniq_id = $data->uniq_id;
		$result_fun = $header->verifyToken();
		$stmt_result = $result_fun->get_result();
		$rows = $stmt_result->num_rows;
		if($rows > 0){
			$accmgr->manager_id = $data->manager_id;
			//$result_top = $accmgr->topData();
			$result_data = $accmgr->getPartners();
			
			$stmt_res = $result_data->get_result();
			$partner_list = $stmt_res->fetch_all(MYSQLI_ASSOC);
			// print_r($partner_list);die;
			foreach($partner_list as $key => $value){
				if($value['status_bank']=='1'){
					$partner_list[$key]['status_bank'] = $value['status_bank'];
				}else if($value['status_bank']=='2'){
					$partner_list[$key]['status_bank'] = $value['status_bank'];
				}else if($value['aadhaar_card_file']!=''|| $value['pan_card_file']!=''|| $value['incorp_certificate_fille']!=''|| $value['cancel_check_file']!=''|| $value['gst_certificate']!=''){
					$partner_list[$key]['status_bank'] = '3'; #Doc Not Verified
				}else{
					$partner_list[$key]['status_bank'] = '4'; #New 
				}
				$partner_list[$key]['created_at'] = date('j M, Y', strtotime($value['created_at'])); #New 
			}
			if(!empty($partner_list)){
				# JSON-encode the response
				$json_response = json_encode(array("data"=>$partner_list,"status_code"=>200));
				# Return the response
				echo $json_response;
			}else{
				#set response code - 422 validation error
                http_response_code(422);
                #tell the user
                echo json_encode(array("message" => "No Data Found!","status_code"=>422));
			}
		}else{
			#set response code - 422 validation error
			http_response_code(422);
			#tell the user
			echo json_encode(array("message" => "Invalid token","status_code"=>422));
		}
	}else{
		#set response code - 400 bad request
		http_response_code(400);
  
		#tell the user
		echo json_encode(array("message" => "Unable to get Account Manager Partner Data. Data is incomplete.","status_code"=>400));
	}
	
?>
