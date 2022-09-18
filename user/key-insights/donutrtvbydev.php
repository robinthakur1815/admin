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
$prevToPrevWeek = strtotime("-2 week +1 day");
$pstart_week = strtotime("last monday midnight",$prevToPrevWeek);
$pend_week = strtotime("next sunday",$pstart_week);
$pstart_week = date("Y-m-d",$pstart_week);
$pend_week = date("Y-m-d",$pend_week);
$prevtoprevweek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($pend_week));

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
     $analytics->pstart_week = $pstart_week;
     $analytics->pend_week = $pend_week;
     $result_content = $analytics->getComChartQuery(); 
     $result_prechart = $result_content->toArray();
    //======================= LAST WEEK=========================//
    $previous_week = strtotime("-1 week +1 day");
    $start_week = strtotime("last monday midnight",$previous_week);
    $end_week = strtotime("next sunday",$start_week);
    $start_week = date("Y-m-d",$start_week);
    $end_week = date("Y-m-d",$end_week);
    $lastweek = date("d M", strtotime($start_week)).' - '.date("d M, Y", strtotime($end_week));
    $lastTwoWeek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($end_week));
    $analytics->pstart_week = $start_week;
    $analytics->pend_week = $end_week;
    $result_lweek = $analytics->getComChartQuery(); 
    $result_lweekchart = $result_lweek->toArray();
    
    if(!empty($result_prechart) || !empty($result_lweekchart)){
           
         #calculation
          $data = prepareData($result_prechart,$result_lweekchart,$prevtoprevweek,$lastweek,$lastTwoWeek);
           
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

function prepareData($resultL,$resultp,$prevtoprevweek,$lastweek,$lastTwoWeek){

$tot_users = $tot_bounces=$tot_sessionDuration=$tot_sessions=$tot_pageviews=0;
$tot_bouncesL = $tot_usersL=$tot_sessionsL=$tot_sessionDurationL=$tot_pageviewsL=0;
   foreach ($resultL as $k=>$val) 
	{
		if($val->userType == 'Returning Visitor'){
			
			$deviceCategory										= $val->deviceCategory;
			@$inArrayP[$deviceCategory][$k]['users']				= $val->users;
			@$inArrayP[$deviceCategory][$k]['sessionDuration']	= $val->sessionDuration;
			@$inArrayP[$deviceCategory][$k]['pageviews']			= $val->pageviews;
			@$inArrayP[$deviceCategory][$k]['bounces']			= $val->bounces;
			@$inArrayP[$deviceCategory][$k]['sessions']			= $val->sessions;

			if(in_array($deviceCategory,$inArrayP)){

				@$inArrayP[$deviceCategory][$k]['users']				+= $val->users;
				@$inArrayP[$deviceCategory][$k]['sessionDuration']	+= $val->sessionDuration;
				@$inArrayP[$deviceCategory][$k]['pageviews']			+= $val->pageviews;
				@$inArrayP[$deviceCategory][$k]['bounces']			+= $val->bounces;
				@$inArrayP[$deviceCategory][$k]['sessions']			+= $val->sessions;
			}else{
			
				@$inArrayP[$deviceCategory][$k]['deviceCategory']	= $val->deviceCategory;
			}
			
			$tot_bounces += $val->bounces;
			$tot_users += $val->users;
			$tot_pageviews += $val->pageviews;
			$tot_sessions += $val->sessions;
			$tot_sessionDuration += $val->sessionDuration;
		}
	}
foreach($inArrayP as $k=> $arrVal){

		$data = array();

		foreach ($inArrayP[$k] as $kk=>$v){

			$data['deviceCategory']		= $v['deviceCategory'];
			$data['users']				+= $v['users'];
			$data['sessionDuration']	+= $v['sessionDuration'];
			$data['pageviews']			+= $v['pageviews'];
			$data['bounces']			+= $v['bounces'];
			$data['sessions']			+= $v['sessions']; 
		}
		$newArrP[$k] = $data; 
	}
    
	foreach($newArrP as $key=>$nval){

		$tableP['deviceCategory'][$key] 	= $nval['deviceCategory'];
		$tableP['users'][$key] 				= $nval['users'];
		$tableP['sessionDuration'][$key] 	= $nval['sessionDuration'];
		$tableP['pageviews'][$key]			= $nval['pageviews'];
		$tableP['bounces'][$key]			= $nval['bounces'];
		$tableP['sessions'][$key]			= $nval['sessions'];
	}


//======================= LAST WEEK=========================//
 foreach ($resultp as $k1=>$v1als) 
	{
		if($v1als->userType == 'Returning Visitor'){
			
			$deviceCategory										= $v1als->deviceCategory;
			@$inArrayL[$deviceCategory][$k1]['users']				= $v1als->users;
			@$inArrayL[$deviceCategory][$k1]['sessionDuration']	= $v1als->sessionDuration;
			@$inArrayL[$deviceCategory][$k1]['pageviews']			= $v1als->pageviews;
			@$inArrayL[$deviceCategory][$k1]['bounces']			= $v1als->bounces;
			@$inArrayL[$deviceCategory][$k1]['sessions']			= $v1als->sessions;

			if(in_array($deviceCategory,$inArrayL)){

				@$inArrayL[$deviceCategory][$k1]['users']				+= $v1als->users;
				@$inArrayL[$deviceCategory][$k1]['sessionDuration']	+= $v1als->sessionDuration;
				@$inArrayL[$deviceCategory][$k1]['pageviews']			+= $v1als->pageviews;
				@$inArrayL[$deviceCategory][$k1]['bounces']			+= $v1als->bounces;
				@$inArrayL[$deviceCategory][$k1]['sessions']			+= $v1als->sessions;
			}else{
			
				@$inArrayL[$deviceCategory][$k1]['deviceCategory']	= $v1als->deviceCategory;
			}
			
			$tot_bouncesL += $v1als->bounces;
			$tot_usersL += $v1als->users;
			$tot_pageviewsL += $v1als->pageviews;
			$tot_sessionsL += $v1als->sessions;
			$tot_sessionDurationL += $v1als->sessionDuration;
		}
	}
foreach($inArrayL as $k1=> $arrVals){

		$dataL = array();

		foreach ($inArrayL[$k1] as $k1k1=>$v1){

			$dataL['deviceCategory']		= $v1['deviceCategory'];
			@$dataL['users']				+= $v1['users'];
			@$dataL['sessionDuration']	+= $v1['sessionDuration'];
			@$dataL['pageviews']			+= $v1['pageviews'];
			@$dataL['bounces']			+= $v1['bounces'];
			@$dataL['sessions']			+= $v1['sessions']; 
		}
		$newArrL[$k1] = $dataL; 
	}
    
	foreach($newArrL as $k1ey=>$nval1){

		$tableL['deviceCategory'][$k1ey] 	= $nval1['deviceCategory'];
		$tableL['usersL'][$k1ey] 				= $nval1['users'];
		$tableL['sessionDurationL'][$k1ey] 	= $nval1['sessionDuration'];
		$tableL['pageviewsL'][$k1ey]			= $nval1['pageviews'];
		$tableL['bouncesL'][$k1ey]			= $nval1['bounces'];
		$tableL['sessionsL'][$k1ey]			= $nval1['sessions'];
	}
	
	$counter=0;
  
	foreach($tableL['deviceCategory'] AS $ka=>$va){

		if(in_array($va, $tableL['deviceCategory'])){
		
			$index 										= array_search($va, $tableL['deviceCategory']);
			@$mergeArray[$counter]['deviceCategory']		= $va;
			@$mergeArray[$counter]['users']				= $tableP['users'][$ka];
			@$mergeArray[$counter]['usersL']				= $tableL['usersL'][$index];
			@$mergeArray[$counter]['sessionDuration']	= $tableP['sessionDuration'][$ka];
			@$mergeArray[$counter]['sessionDurationL']	= $tableL['sessionDurationL'][$index];
			@$mergeArray[$counter]['pageviews']			= $tableP['pageviews'][$ka];
			@$mergeArray[$counter]['pageviewsL']			= $tableL['pageviewsL'][$index];
			@$mergeArray[$counter]['bounces']			= $tableP['bounces'][$ka];
			@$mergeArray[$counter]['bouncesL']			= $tableL['bouncesL'][$index];
			@$mergeArray[$counter]['sessions']			= $tableP['sessions'][$ka];
			@$mergeArray[$counter]['sessionsL']			= $tableL['sessionsL'][$index];
			$counter++;
		}else{
		
			@$mergeArray[$counter]['deviceCategory']		= $va;
			@$mergeArray[$counter]['users']				= $tableP['users'][$ka];
			@$mergeArray[$counter]['usersL']				= 0;
			@$mergeArray[$counter]['sessionDuration']	= $tableP['sessionDuration'][$ka];
			@$mergeArray[$counter]['sessionDurationL']	= 0;
			@$mergeArray[$counter]['pageviews']			= $tableP['pageviews'][$ka];
			@$mergeArray[$counter]['pageviewsL']			= 0;
			@$mergeArray[$counter]['bounces']			= $tableP['bounces'][$ka];
			@$mergeArray[$counter]['bouncesL']			= 0;
			@$mergeArray[$counter]['sessions']			= $tableP['sessions'][$ka];
			@$mergeArray[$counter]['sessionsL']			= 0;
			$counter++;
		}
	}
    aasort($mergeArray,"usersL"); $i=1; 
	
	$avgBounceRateL			= $tot_bouncesL/$tot_sessionsL;
	$avgBounceRate			= $tot_bounces/$tot_sessions;
	$avgSessionDurationL	= $tot_sessionDurationL/$tot_sessionsL;
	$avgSessionDuration		= $tot_sessionDuration/$tot_sessions;
	
	foreach($mergeArray as $key=>$values){
	$avgBounceL 	= $values['bouncesL']/$values['sessionsL'];
    $avgSessionL	= $values['sessionDurationL']/$values['sessionsL'];
	$avgBounce	= $values['bounces']/$values['sessions'];
    $avgSession	= $values['sessionDuration']/$values['sessions'];
		$tabledata[]=array(
						'category'=>ucwords($values['deviceCategory']),
						'unqvisitor'=>round(($values['usersL']/$tot_usersL)*100,2),
						'pageviews'=>round(($values['pageviewsL']/$tot_pageviewsL)*100,2),
						// 'avgsession'=>(($values['deviceCategory'] != 'TV') ? gmdate("H:i:s",($avgSessionL)) : '00:00:00'),
						'avgsession'=>gmdate("H:i:s",($avgSessionL)),
						'avgbounce'=>(($values['deviceCategory'] != 'TV') ? number_format(((($avgBounceL/$avgBounceRateL))*100), 2) : 0)
						);
		$tabledataP[]=array(
						'category'=>ucwords($values['deviceCategory']),
						'unqvisitor'=>round(($values['users']/$tot_users)*100,2),
						'pageviews'=>round(($values['pageviews']/$tot_pageviews)*100,2),
						// 'avgsession'=>(($values['deviceCategory'] != 'TV') ? gmdate("H:i:s",($avgSession)) : '00:00:00'),
						'avgsession'=>gmdate("H:i:s",($avgSession)),
						'avgbounce'=>(($values['deviceCategory'] != 'TV') ? number_format(((($avgBounce/$avgBounceRate))*100), 2) : 0)
						);
	}
	
	$topdata[]=array(
						'daterange'=>$lastweek,
						'unqvisitor'=>number_format($tot_usersL),
						'pageviews'=>number_format($tot_pageviewsL),
						'avgsession'=>gmdate("H:i:s",($avgSessionDurationL)),
						'avgbounce'=>number_format(($avgBounceRateL*100), 2)
						);
	
	
	$topdataP[]=array(
						'daterange'=>$prevtoprevweek,
						'unqvisitor'=>number_format($tot_users),
						'pageviews'=>number_format($tot_pageviews),
						'avgsession'=>gmdate("H:i:s",($avgSessionDuration)),
						'avgbounce'=>number_format(($avgBounceRate*100), 2)
						);
	$rvdsec_arr = array('date'=>$prevtoprevweek);
	$merged_arr = array_merge($tableP['users'], $rvdsec_arr);
	$request_chartarray=array('returnvisitorbydev'=>$tableP['users']);
	$donutdata=array($merged_arr);
	$request_chartarray1=array('returnvisitorbydev'=>$tableL['usersL']);
	$rvdsec_arrL = array('date'=>$lastweek);
	$merged_arrL = array_merge($tableL['usersL'], $rvdsec_arrL);
	$donutdata1=array($merged_arrL);
	
	$daterange[]=array('daterange'=>$lastTwoWeek);
	
	
	$sendresponse=array('donut1_chart_data'=>$request_chartarray,'donut2_chart_data'=>$request_chartarray1,'donutdata1'=>$donutdata,'donutdata2'=>$donutdata1,'tabulardata'=>$tabledata,'topbardata'=>$topdata,'tabulardata1'=>$tabledataP,'topbardata1'=>$topdataP,'daterange'=>$daterange);

   return $sendresponse; 
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