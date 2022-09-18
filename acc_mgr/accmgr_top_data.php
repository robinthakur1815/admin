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
$Hour = date('G');
if($Hour < 16){
$Date = date('Y-m-d', strtotime(' -1 day'));
}else{ 

$Date = date('Y-m-d');
}
    
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
    	#set token property values
        $accmgr->date = $Date;
       $accmgr->manager_id = $data->manager_id;
       $result_top = $accmgr->topData();
       $result_accCount = $accmgr->countPublisher();
       $result_adxReport = $accmgr->adxReports();
       if(!empty($result_top) || !empty($result_accCount) ){
            
             $fillrate = $accmgr->fillRate();
            $ecpm = $result_top[0]['adimr'] > 0 ? number_format($result_top[0]['revenue']/$result_top[0]['adimr']*1000,2) : 0.00; 
            //$percent = $result_top[0]['revenue'] > 0 ?($result_top[1]['revenue'] - $result_top[0]['revenue']) /$result_top[0]['revenue'] * 100:0;
            $adx_earn = array("unfilled"=>number_format($result_top[2]['unfilled']),"impression"=>number_format($result_top[0]['adimr']),"ecpm"=>number_format($ecpm,2),"fillrate"=>$fillrate,"cur_week"=>number_format($result_top[1]['revenue'],2));
       
            # JSON-encode the response
            $json_response = json_encode(array("data"=>array("Total_accounts"=>$result_accCount,"adx_earning"=>$adx_earn,"adx_preWeek"=>number_format($result_top[0]['revenue'],2),"today_adxReports"=>$result_adxReport),"status_code"=>200));

            # Return the response
            echo $json_response;

         }else{
            #set response code - 422 validation error
                http_response_code(422);
          
                #tell the user
                echo json_encode(array("message" => "No Data Found!","status_code"=>422));
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
    echo json_encode(array("message" => "Unable to get Account Manager Top. Data is incomplete.","status_code"=>400));
}
?>