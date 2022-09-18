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
$header = new Common($db);
$dbMongoDb = $database->getConnectionMongoDb();

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];

if(
	!empty($data->uniq_id) &&
	!empty($data->sales_id)
){
	$header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
	if($rows > 0){
		$sales = new Salesapi($db,$dbMongoDb, $data->strtdate, $data->enddate);
		$sales->sales_id = $data->sales_id;
		$result_fun = $sales->toppatti();
		$datafound = json_decode($result_fun);
		$result_funsale = $sales->toppattisale();
		$stmt_mcmon = $result_funsale->get_result();
		$rowMcmon = $stmt_mcmon->fetch_all(MYSQLI_ASSOC);

		if(!empty($datafound) || !empty($rowMcmon)){
			$json_response = json_encode(array("data"=>$datafound,"sales name"=>$rowMcmon, "message" => "success","status_code"=>200));
			echo $json_response;
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "No data found","status_code"=>503));
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
