<?php #Author BY SS
#error reporting
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
include_once '../objects/Salesapi.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$connMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$salesUser = new Salesapi($db,$connMongoDb,$strtdate,$enddate);

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];

#make sure data is not empty
if(
!empty($data->uniq_id)
){
	#set token property values
	$header->access_token = trim($token);
	$header->pub_uniq_id = $data->uniq_id;
	$result_fun = $header->verifyToken();
	$stmt_result = $result_fun->get_result();
	$rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
	$rows = $stmt_result->num_rows;
	if($rows > 0){
		$whereCondition = "WHERE u.role_id IN(7,8)";
		if($data->status == 'Y' && $data->role_id == '7'){
			$whereCondition = "WHERE u.role_id='7' AND u.user_status='Y'";
		}elseif($data->status == 'Y' && $data->role_id == '8'){
			$whereCondition = "WHERE u.role_id='8' AND u.user_status='Y'";
		}elseif($data->status == 'N' && $data->role_id == '7'){
			$whereCondition = "WHERE u.role_id='7' AND u.user_status='N'";
		}elseif($data->status == 'N' && $data->role_id == '8'){
			$whereCondition = "WHERE u.role_id='8' AND u.user_status='N'";
		}elseif($data->status == 'N' && $data->role_id == ''){
			$whereCondition = "WHERE u.role_id IN(7,8) AND u.user_status='N'";
		}elseif($data->status == 'Y' && $data->role_id == ''){
			$whereCondition = "WHERE u.role_id IN(7,8) AND u.user_status='Y'";
		}elseif($data->status == '' && $data->role_id == '7'){
			$whereCondition = "WHERE u.role_id='7'";
		}elseif($data->status == '' && $data->role_id == '8'){
			$whereCondition = "WHERE u.role_id='8'";
		}

		$result_sub = $salesUser->getallSalesuser($whereCondition);
		$stmt_sub = $result_sub->get_result();
		$rowSub = $stmt_sub->fetch_all(MYSQLI_ASSOC);
		$rowsSub = $stmt_sub->num_rows;
		if($rowsSub > 0){
			$json_response = json_encode(array("data"=>$rowSub,"message" => "success","status_code"=>200));
			echo $json_response;
		}else{
			http_response_code(204);
			echo json_encode(array("message" => "No Data Found!","status_code"=>204));
		}
	}else{
		http_response_code(422);
		echo json_encode(array("message" => "Invalid token","status_code"=>422));
	}
}else{
	http_response_code(400);
	echo json_encode(array("message" => "Unable to get Data.Incomplete Data.","status_code"=>400));
}

?>
