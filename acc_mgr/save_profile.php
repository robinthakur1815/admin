<?php
#Author BY SY
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
$data = $_POST;
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];
// echo"<pre>";
// print_r($data);die;
#make sure data is not empty

if(
	!empty($data['uniq_id']) &&
	!empty($data['manager_id']) &&
	!empty($data['pub_id']) &&
	!empty($data['partner_uniq_id']) 
){
	
	#set token property values 
	$header->access_token = trim($token);
	$header->pub_uniq_id = $data['uniq_id'];
	$result_fun = $header->verifyToken();
	$stmt_result = $result_fun->get_result();
	$rows = $stmt_result->num_rows;
	if($rows > 0){
		#set user property values
		$accmgr->uniq_id = $data['uniq_id'];
		$accmgr->manager_id = $data['manager_id'];
		$accmgr->pub_id = $data['pub_id'];
		$accmgr->parner_uniq_id = $data['partner_uniq_id'];
		$accmgr->pub_adsense_id = $data['pub_adsense_id'];
		$accmgr->pub_display_share = $data['pub_display_share'];
		$accmgr->pub_video_share = $data['pub_video_share'];
		$accmgr->pub_app_share = $data['pub_app_share'];
		$result_fun = $accmgr->updateProfile(); 
		if($result_fun == 1){
			http_response_code(200);
			echo json_encode(array("message" => "Publisher deatials updated sucessfully.","status_code"=>200));
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "Unable to updated publisher deatials.","status_code"=>503));
		}
	}else{
		http_response_code(422);
		echo json_encode(array("message" => "Invalid token","status_code"=>422));
	}
}else{
	http_response_code(407);
	echo json_encode(array("message" => "Unable to updated publisher deatials. Data is incomplete.","status_code"=>407));
}




?>