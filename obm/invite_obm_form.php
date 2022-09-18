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
    !empty($data->pub_id) && 
    !empty($data->pub_type) && 
    !empty($data->org_type) && 
    !empty($data->pub_uniq_id)
   
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->obm_uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    	#set token property values
      
       $obm->pub_id = $data->pub_id;
       $obm->pub_uniq_id = $data->pub_uniq_id;
       $obm->pub_type = $data->pub_type;
       $obm->org_type = $data->org_type;
       $obm->org_name = $data->org_name;
       $obm->domain_managed = $data->domain_managed;
       $obm->team_size = $data->team_size;
       $obm->direct_sales = $data->direct_sales;
       $obm->adx_for_display = $data->adx_for_display;
       $obm->adx_for_video = $data->adx_for_video;
       $obm->adx_for_app = $data->adx_for_app;
       $obm->display_share = $data->display_share;
       $obm->video_share = $data->video_share;
       $obm->app_share = $data->app_share;
       $obm->adsense_id = $data->adsense_id;
       $obm->adsense_share = $data->adsense_share;
       $obm->sales_id = $data->sales_id;
       $obm->channel_id = $data->channel_id;
       $obm->remark = $data->remark;
       $obm->refer = $data->refer;
       $obm->refer_name = $data->refer_name;
       $obm->refer_email = $data->refer_email;
       $obm->refer_contact = $data->refer_contact;
       //$obm->app_names = $data->app_names;
       $obm->analytics_id = $data->analytics_id;
       $obm->email_status = $data->email_status;
       $obm->pub_email = $data->pub_email;
       $result_data = $obm->updateInviteData();
		
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