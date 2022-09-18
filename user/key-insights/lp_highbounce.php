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
#for number format
ini_set('serialize_precision', 10);
ini_set('memory_limit', '-1');
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
$previous_week = strtotime("-1 week +1 day");
$start_week = strtotime("last monday midnight",$previous_week);
$end_week = strtotime("next sunday",$start_week);
$start_week = date("Y-m-d",$start_week);
$end_week = date("Y-m-d",$end_week);
$daterangelastweek=date("d M", strtotime($start_week)).' - '.date("d M, Y", strtotime($end_week));

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
   
     #set audience property values PREVIOUS To PREVIOUS Week
     $analytics->account_id = $data->account_id;
     $analytics->start_week = $start_week;
     $analytics->end_week = $end_week;
     $result_content = $analytics->getHighBounce(); 
     $result_hbounce = $result_content->toArray();
    
    if(!empty($result_hbounce)){
           
         #calculation
          $data = prepareData($result_hbounce,$daterangelastweek);
           
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

function prepareData($row,$daterangelastweek){

 $inArray = array();
	foreach($row as $k=>$val) {
		
		$landingPagePath = $val->landingPagePath;
		@$inArray[$landingPagePath][$k]['pageviews'] = $val->pageviews;
		@$inArray[$landingPagePath][$k]['bounces'] = $val->bounces;
		@$inArray[$landingPagePath][$k]['sessions'] = $val->sessions;
		
		if(in_array($landingPagePath,$inArray)){
			
			@$inArray[$landingPagePath][$k]['pageviews'] += $val->pageviews;
			@$inArray[$landingPagePath][$k]['bounces'] += $val->bounces;
			@$inArray[$landingPagePath][$k]['sessions'] += $val->sessions;
		}else{
			@$inArray[$landingPagePath][$k]['landingPagePath'] = $val->landingPagePath;
		}
	}				

		foreach($inArray as $k=> $arrVal){
		
			$data = array();
			
			foreach ($inArray[$k] as $kk=>$v){
				@$data['landingPagePath'] = $v['landingPagePath'];
				@$data['pageviews'] += $v['pageviews'];
				@$data['bounces'] += $v['bounces'];
				@$data['sessions'] += $v['sessions']; 
				
			}
			
			$newArr[$k] = $data; 
		}
		function sortByView($a, $b)
	{
	    $a = $a['pageviews'];
	    $b = $b['pageviews'];

	    if ($a == $b) return 0;
	    return ($a > $b) ? -1 : 1;
	}
	usort($newArr, 'sortByView');
		
		$xAxis = array();
		$pageViewsArray = $pageSessionsArray = $pageBouncesArray = array();

	    
	    $kk = 0;
		foreach($newArr as $k=>$v){
	        if($kk==5){
	            break;
	        }
	        if($v['sessions'] >=10) {
	            $avgBounceGraph 	= $v['bounces']/$v['sessions'];
	        
	            $rvaindex = strlen($v['landingPagePath']) > 10 ? mb_substr($v['landingPagePath'],0,10)."..." : $v['landingPagePath'];
	            $xAxis[] = $rvaindex;
	            $landingpfull[] = $v['landingPagePath'];
	            $pageViewsArray[] =  $v['pageviews'];
	            $pageBouncesArray[] = round(($avgBounceGraph)*100, 2);
	        }
		}
	foreach($xAxis as $key => $value)
	{
		$lp_path[]=$value;
	}
	foreach($landingpfull as $key => $value4)
	{
		$lp_path_full[]=$value4;
	}	
	foreach($pageViewsArray as $key => $value1)
	{
		$pageviews[]=$value1;
	}

	foreach($pageBouncesArray as $key => $value3)
	{
		$bounce[]=$value3;
	}

	for($i=0; $i<=4;$i++)
	{
		$tabledata[]=array(
							'pagepath'=>$lp_path_full[$i],
							'pageview'=>number_format($pageviews[$i]),
							'bounce'=>$bounce[$i]
							);
	}

	usort($tabledata, function($a, $b) {
	    return $b['bounce'] <=> $a['bounce'];
	});

	$daterange[]=array('daterange'=>$daterangelastweek);
	$request_array=array('tabledata_lp_highbounce'=>$tabledata,'daterange'=>$daterange);

   return $request_array; 
}/***calculation function end*****/

?>