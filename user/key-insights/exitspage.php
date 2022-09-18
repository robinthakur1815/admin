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
//======================= PREVIOUS To PREVIOUS WEEK=========================//
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
   
     #set traffic property values PREVIOUS To PREVIOUS Week
     $analytics->account_id = $data->account_id;
     $analytics->start_week = $start_week;
     $analytics->end_week = $end_week;
     $result_content = $analytics->getHighBounce(); 
     $result_traffic = $result_content->toArray();
   
    
    if(!empty($result_traffic)){
           
         #calculation
          $data = prepareData($result_traffic,$daterangelastweek);
           
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
    echo json_encode(array("message" => "Unable to get Traffic Source. Data is incomplete.","status_code"=>400));
}

function prepareData($resultL,$daterangelastweek){
$inArray = array(); $tot_exits = $tot_pageviews = 0;
	foreach($resultL as $k=>$val) {
		
		$exitPagePath 									= $val->exitPagePath;
		$inArray[$exitPagePath][$k]['pageviews'] 		= $val->pageviews;
		$inArray[$exitPagePath][$k]['exits'] 			= $val->exits;
		
		if(in_array($exitPagePath,$inArray)){
			
			@$inArray[$exitPagePath][$k]['pageviews'] 		+= $val->pageviews;
			@$inArray[$exitPagePath][$k]['exits'] 			+= $val->exits;
		}else{
			@$inArray[$exitPagePath][$k]['exitPagePath'] 	= $val->exitPagePath;
		}
		
	
	}				

	foreach($inArray as $k=> $arrVal){
	
		$data = array();
		
		foreach ($inArray[$k] as $kk=>$v){
			
			$data['exitPagePath'] 		= $v['exitPagePath'];
			@$data['pageviews'] 			+= $v['pageviews'];
			@$data['exits'] 				+= $v['exits'];
		}
		$newArr[$k] = $data; 
	}
	aasort($newArr,"exits");	
	$xAxis = array();
	$pageViewsArray = $pageSessionsArray = $pageBouncesArray = $pageexitsviewsvalArray = array();
	$kk = 0;
	foreach($newArr as $k=>$v){
	if($kk==10){
	 
	 break;
	 }
	 $rvaindex = strlen($v['exitPagePath']) > 10 ? mb_substr($v['exitPagePath'],0,10)."..." : $v['exitPagePath'];
	 $xAxis[] = $rvaindex;
	 $landingpfull[] = $v['exitPagePath'];
     $pageViewsArray[] =  $v['pageviews'];
	 $pageexitsArray[] =  $v['exits'];
	 $pageexitsviewsvalArray[] = (($v['pageviews'] > 0) ? round($v['exits']/$v['pageviews']*100,2) : 0);
	 $tot_exits += $v['exits'];
	 $tot_pageviews += $v['pageviews'];
	 $kk++;
	}
	
	 
	 // $avgBounceRate = $tot_bounces/$tot_sessions;
	 // $avgSessionDuration = $tot_sessionDuration/$tot_sessions;
foreach($xAxis as $key => $value)
{
	$lp_path[]=$value;
}	
foreach($pageViewsArray as $key => $value1)
{
	$pageviews[]=$value1;
}
foreach($pageexitsArray as $key => $value2)
{
	$exits[]=$value2;
}
foreach($pageexitsviewsvalArray as $key => $value3)
{
	$exitsperpageview[]=$value3;
}
foreach($landingpfull as $key => $value4)
{
	$lp_path_full[]=$value4;
}

for($i=0; $i<10;$i++)
{
	$tabledata[]=array(
						'category'=>$lp_path_full[$i],
						'pageview'=>round(($pageviews[$i]/$tot_pageviews)*100,2),
						'exits'=>round(($exits[$i]/$tot_exits)*100,2),
						'exitsperpageview'=>$exitsperpageview[$i]
						);
}

$topdata=array('Pageviews'=>number_format($tot_pageviews),'Exits'=>number_format($tot_exits),'AvgExits'=>number_format(($tot_exits/$tot_pageviews*100),2),'daterange'=>$daterangelastweek);
$request_array=array('categories'=>$lp_path,'data'=>$pageviews,'data1'=>$exits,'data2'=>$exitsperpageview,'topdata'=>array($topdata),'tabledata'=>$tabledata);
   return $request_array; 
}/***calculation function end*****/
function aasort(&$array, $key) {
	
	$sorter=array(); $ret=array(); reset($array);
    
	foreach ($array as $ii => $va) {
		$sorter[$ii]=$va[$key];
	}
    
	arsort($sorter); 
    
	foreach ($sorter as $ii => $va) {
		$ret[$ii]=$array[$ii];
	}
    
	$array=$ret;
}
?>