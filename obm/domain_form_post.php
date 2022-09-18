<?php
#Author BY AD
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
include_once '../objects/OBM.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$obm = new OBM($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->obm_uniq_id) && 
    !empty($data->pub_name) && 
    !empty($data->domain_id)
   
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->obm_uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    	#set token property values
      
       $obm->domain_id = $data->domain_id;
       $obm->traffic_source = $data->traffic_source;
       $obm->primary_geo = $data->primary_geo;
       $obm->inventory_quality = $data->inventory_quality;
       $obm->vertical = $data->vertical1;
       $obm->vertical2 = $data->vertical2;
       $obm->analytics_id = $data->web_analytics_id;
       $obm->email_status = $data->email_status;
       $obm->pub_name = $data->pub_name;
       $obm->web_name = $data->web_name;
       $obm->pub_email = $data->pub_email;
       $obm->mcm_nonmcm_status = $data->mcm_nonmcm_status;
       
       $result_data = $obm->updateDomainData();
		
		if($result_data == 1){
			http_response_code(200);
			echo json_encode(array("message" => "Data Updated Sucessfully.","status_code"=>200));
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "Unable to update data.","status_code"=>503));
		}
		


     }
     else{
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
    echo json_encode(array("message" => "Unable to post Ad Manager Domain. Data is incomplete.","status_code"=>400));
}
?>