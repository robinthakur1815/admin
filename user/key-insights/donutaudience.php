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
#for number format
ini_set('serialize_precision', 10);
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Analytics.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$analytics = new Analytics($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#previous week Start  date and end date
$previous_week = strtotime("-1 week +1 day");
$start_week = strtotime("last monday midnight",$previous_week);
$end_week = strtotime("next sunday",$start_week);
$start_week = date("Y-m-d",$start_week);
$end_week = date("Y-m-d",$end_week);

#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->account_id) &&
    !empty($data->child_net_code)
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
   
     #set audience property values 
     $analytics->account_id = $data->account_id;
     $analytics->pstart_week = $start_week;
     $analytics->pend_week = $end_week;
     $result_content = $analytics->getComChartQuery(); 
     $result_donutchart = $result_content->toArray();
    
    
    if(!empty($result_donutchart)){
           
         #calculation
          $data = prepareData($result_donutchart);
           
          #set response code - 200 ok
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("data"=>$data,"status_code"=>200));
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
    echo json_encode(array("message" => "Unable to get Audience. Data is incomplete.","status_code"=>400));
}

function prepareData($resultL){

foreach ($resultL as $val) 
	{
		if(strtolower($val->deviceCategory) == 'desktop'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType]+= $val->pageviews;
            }else if(strtolower($val->deviceCategory) == 'mobile'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType]+= $val->pageviews;
            }else if(strtolower($val->deviceCategory) == 'tablet'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType] += $val->pageviews;
            }
	}
	 @$request_chartarray['Mobile']=$deviceinArray['mobile'];
	 @$request_chartarray['Desktop']=$deviceinArray['desktop'];
     @$request_chartarray['Tablet']=$deviceinArray['tablet'];
	 
	 $request_combinedata=array('Mobile'=>number_format($deviceinArray['mobile']),'Desktop'=>number_format($deviceinArray['desktop']),'Tablet'=>number_format($deviceinArray['tablet']));
	 $sendresponse= array('donut_data'=>array($request_combinedata),'donut_chart_data'=>$request_chartarray);

   return $sendresponse; 
}/***calculation function end*****/

?>