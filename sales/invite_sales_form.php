<?php
#Author BY SS
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
include_once '../objects/Salesapi.php';
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$salesUser = new Salesapi($db,$connMongoDb,$strtdate,$enddate);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    // !empty($data->sales_uniq_id) && 
    !empty($data->pub_id) && 
    !empty($data->pub_type) && 
    !empty($data->org_type) && 
    !empty($data->pub_uniq_id)
   
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->sales_uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    	#set token property values
      
       $salesUser->pub_id = $data->pub_id;
       $salesUser->pub_uniq_id = $data->pub_uniq_id;
       $salesUser->pub_type = $data->pub_type;
       $salesUser->org_type = $data->org_type;
       $salesUser->org_name = $data->org_name;
       $salesUser->domain_managed = $data->domain_managed;
       $salesUser->team_size = $data->team_size;
       $salesUser->direct_sales = $data->direct_sales;
       $salesUser->adx_for_display = $data->adx_for_display;
       $salesUser->adx_for_video = $data->adx_for_video;
       $salesUser->adx_for_app = $data->adx_for_app;
       $salesUser->display_share = $data->display_share;
       $salesUser->video_share = $data->display_share;
       $salesUser->app_share = $data->display_share;
       $salesUser->adsense_id = $data->adsense_id;
       $salesUser->adsense_share = $data->adsense_share;
       $salesUser->sales_id = $data->sales_id;
       $salesUser->channel_id = $data->channel_id;
       $salesUser->remark = $data->remark;
       $salesUser->refer = $data->refer;
       $salesUser->refer_name = $data->refer_name;
       $salesUser->refer_email = $data->refer_email;
       $salesUser->refer_contact = $data->refer_contact;
       //$obm->app_names = $data->app_names;
       $salesUser->analytics_id = $data->analytics_id;
       $salesUser->email_status = $data->email_status;
       $salesUser->pub_email = $data->pub_email;
       $result_data = $salesUser->updateInviteData();
		
		if($result_data == 1){
			http_response_code(200);
			echo json_encode(array("message" => "Data Updated Sucessfully.","status_code"=>200));
		}else{
			http_response_code(503);
			echo json_encode(array("message" => "Unable to update bdata.","status_code"=>503));
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
    echo json_encode(array("message" => "Unable to post Ad Manager Invite. Data is incomplete.","status_code"=>400));
}
?>