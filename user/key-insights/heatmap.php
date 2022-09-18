<?php
#Author BY SS
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '-1');
set_time_limit(0);
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

$start_date=date('Y-m-d',strtotime("-8 days"));
$end_date=date('Y-m-d',strtotime("-2 days"));

if(date('H') >= 21){
	$start_date=date('Y-m-d',strtotime("-7 days"));
	$end_date=date('Y-m-d',strtotime("-1 days"));
}


#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->account_id)
    
){
	#set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
	if($rows > 0){
		$analytics->uniq_id = $data->uniq_id;
		$analytics->account_id = $data->account_id;
		// $analytics->child_net_code = $data->child_net_code;
		$analytics->strtdate = $start_date;
		$analytics->enddate = $end_date;
		$result_content = $analytics->getHeatmapUrl();
		$result_ads = $result_content->toArray();
		if(!empty($result_ads)){
			$deviceinArray = array();
			$i = 0;
			foreach($result_ads as $val){
				$deviceinArray['result'][$i] = array('siteurl'=>$val->_id);
				$i++;
			}
			if(!empty($deviceinArray)){
				$analytics->pagePath = $deviceinArray['result'][0]['siteurl'];
				$result_heatmapData = $analytics->getHeatmapData();
				$result_heatData = $result_heatmapData->toArray();
				if(!empty($result_heatData)){
					// print_r($result_heatData);die;
					$deviceinArray['graphData'] = prepareData($result_heatData,$start_date,$end_date);
					#set response code - 200 ok
					http_response_code(200);
			  
					#tell the user
					echo json_encode(array("data"=>$deviceinArray,"status_code"=>200));
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
				echo json_encode(array("message" => "No Data Found!","status_code"=>422));
			}
			
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
	
}
#tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to get key insights. Data is incomplete.","status_code"=>400));
}
	
function prepareData($cursor,$start_date,$end_date){
	foreach ($cursor as $document){
		$deviceinArray[$document->_id->date][] = array(
										'hour'=>$document->_id->hour,
										'view'=>$document->totalPageViews
										);
	}
	foreach($deviceinArray AS $devList=>$value){
		$dates[]=date('d M', strtotime($devList));
		$last_names[] = array_column($value, 'view');
	}
	$arrTime=array();
	$x=0;
	$y=1;
	foreach($last_names AS $viewarray){
		for($i=0;$i<24;$i+=2){
			if($y==13){
				$y=1;
			}
			$arrTime[]=array(
							'x'=>$x,
							'y'=>$y,
							'value'=>$viewarray[$i]+$viewarray[$i+1],
							);
			$y++;
		}
		$x++;
	}
	$daterange=date("j M", strtotime($start_date)).' - '.date("j M, Y", strtotime($end_date));	
	$send_resp = array('date'=>$dates,'heatmapdata'=>$arrTime,'date_heatmap'=>$daterange);
	return $send_resp;
}
	
	
	
	