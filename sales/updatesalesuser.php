<?php #Author BY SS
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

if(
	!empty($data->uniq_id) &&
	!empty($data->salesuser_id)
){
	$header->access_token = trim($token);
	$header->pub_uniq_id = $data->uniq_id;
	$result_fun = $header->verifyToken();
	$stmt_result = $result_fun->get_result();
	$row = $stmt_result->fetch_array(MYSQLI_ASSOC);
	$rows = $stmt_result->num_rows;
    if($rows > 0){
		$salesUser->salesuser_id = $data->salesuser_id;

		$salesUser->first_name = $data->first_name;
		$salesUser->last_name = $data->last_name;
		$salesUser->email = $data->user_email;
		$salesUser->contact = $data->user_phone;
		$salesUser->role_id = $data->user_role_id;
		$salesUser->status = $data->user_status;

		$result_fun = $salesUser->updateSalesuser();
		if($result_fun == 1){
			http_response_code(200);
			echo json_encode(array("message" => "Sales user updated successfully.","status_code"=>200));
		}else if($result_fun == 2){
			http_response_code(422);
			echo json_encode(array("message" => "E-Mail Address already exist, please check details.!","status_code"=>422));
		}else if($result_fun == 3){
			http_response_code(422);
			echo json_encode(array("message" => "Invalid E-Mail Address.","status_code"=>422));
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "Unable to update sales-user.","status_code"=>503));
		}
	}else{
        http_response_code(422);
        echo json_encode(array("message" => "Invalid token","status_code"=>422));
    }
}else{
	http_response_code(400);
	echo json_encode(array("message" => "Unable to get sales-user. Data is incomplete.","status_code"=>400));
}

?>
