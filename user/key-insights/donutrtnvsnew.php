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

function prepareData($resultL,$resultLw,$prevtoprevweek,$lastweek,$lastTwoWeek){
   $tot_visits = $tot_bounces=$tot_sessionDuration=$tot_sessions=$tot_pageviews=0;
   $tot_visits1 = $tot_bounces1=$tot_sessionDuration1=$tot_sessions1=$tot_pageviews1=0;

     foreach ($resultL as $val) 
	{
		if($val->userType == 'New Visitor'){
    			
    			$pUserType							    = $val->userType;
    			@$inArray[$pUserType]['userType']		= $val->userType;
    			@$inArray[$pUserType]['users']			+= $val->users;
    			@$inArray[$pUserType]['bounces']			+= $val->bounces;
    			@$inArray[$pUserType]['sessions']		+= $val->sessions;
    			@$inArray[$pUserType]['pageviews']		+= $val->pageviews;
    			@$inArray[$pUserType]['bounceRate']		+= $val->bounceRate;
    			@$inArray[$pUserType]['visits']			+= $val->visits;
    			@$inArray[$pUserType]['sessionDuration']	+= $val->sessionDuration;
    		}else{    			
    			$pUserType								= $val->userType;
    			@$inArray[$pUserType]['userType']		= $val->userType;
    			@$inArray[$pUserType]['users']			+= $val->users;
    			@$inArray[$pUserType]['bounces']			+= $val->bounces;
    			@$inArray[$pUserType]['sessions']		+= $val->sessions;
    			@$inArray[$pUserType]['pageviews']		+= $val->pageviews;
    			@$inArray[$pUserType]['bounceRate']		+= $val->bounceRate;
    			@$inArray[$pUserType]['visits']			+= $val->visits;
    			@$inArray[$pUserType]['sessionDuration']	+= $val->sessionDuration;
    		}

    		 $tot_visits += $val->visits;
    		 $tot_bounces += $val->bounces;
    		 $tot_sessions += $val->sessions;
    		 $tot_pageviews += $val->pageviews;
    		 $tot_sessionDuration += $val->sessionDuration;		
	}
	 $avgBounceRate = $tot_bounces/$tot_sessions;
	 $totalSessionDuration = $tot_sessionDuration/$tot_sessions;


//======================= LAST WEEK=========================//

    //////////////////////// donut chart  ////////////////////////////////////////////////

    foreach ($resultLw as $val1) 
	{
		if($val1->userType == 'New Visitor'){
    			
    			$pUserType1								= $val1->userType;
    			@$inArray1[$pUserType1]['userType']		= $val1->userType;
    			@$inArray1[$pUserType1]['users']			+= $val1->users;
    			@$inArray1[$pUserType1]['bounces']			+= $val1->bounces;
    			@$inArray1[$pUserType1]['sessions']		+= $val1->sessions;
    			@$inArray1[$pUserType1]['pageviews']		+= $val1->pageviews;
    			@$inArray1[$pUserType1]['bounceRate']		+= $val1->bounceRate;
    			@$inArray1[$pUserType1]['visits']			+= $val1->visits;
    			@$inArray1[$pUserType1]['sessionDuration']	+= $val1->sessionDuration;
    		}else{    			
    			$pUserType1								= $val1->userType;
    			@$inArray1[$pUserType1]['userType']		= $val1->userType;
    			@$inArray1[$pUserType1]['users']			+= $val1->users;
    			@$inArray1[$pUserType1]['bounces']			+= $val1->bounces;
    			@$inArray1[$pUserType1]['sessions']		+= $val1->sessions;
    			@$inArray1[$pUserType1]['pageviews']		+= $val1->pageviews;
    			@$inArray1[$pUserType1]['bounceRate']		+= $val1->bounceRate;
    			@$inArray1[$pUserType1]['visits']			+= $val1->visits;
    			@$inArray1[$pUserType1]['sessionDuration']	+= $val1->sessionDuration;
    		}
    		$tot_visits1 += $val1->visits;
    		$tot_bounces1 += $val1->bounces;
    		$tot_sessions1 += $val1->sessions;
    		$tot_pageviews1 += $val1->pageviews;
    		$tot_sessionDuration1 += $val1->sessionDuration;		
	}
    $avgBounceRate1 = $tot_bounces1/$tot_sessions1;
    $totalSessionDuration1 = $tot_sessionDuration1/$tot_sessions1;
    
   foreach($inArray1 as $audKey=>$value){
		$avgBounce = $value['bounces']/$value['sessions'];
		$avgSessDur = $value['sessionDuration']/$value['sessions'];
		$tabledata[]=array(
						'audtype'=>ucwords($value['userType']),
						// 'pageviews'=>number_format($value['pageviews']),
						'pageviews'=>number_format(($value['pageviews']/$tot_pageviews1)*100,2),
						'bounce'=>number_format(((($avgBounce))*100), 2),
						'pagevisit'=>number_format($value['pageviews']/$value['visits'], 2),
						// 'avgduration'=>number_format(((($avgSessDur/$totalSessionDuration1))*100),2)
						'avgduration'=>gmdate("H:i:s",($value['sessionDuration']/$value['sessions']))
						);
	
			}


	$topdata[]=array(
				'daterange'=>$lastweek,
				'pageviews'=>number_format($tot_pageviews1),
				'avgbounce'=>number_format((($avgBounceRate1)*100), 2),
				'avgpagevisit'=>number_format($tot_pageviews1/$tot_visits1, 2),
				'avgduration'=>gmdate("H:i:s",($tot_sessionDuration1/$tot_sessions1))
				);
						
	foreach($inArray as $audKey=>$vals){
		$avgBounce = $vals['bounces']/$vals['sessions'];
		$avgSessDur = $vals['sessionDuration']/$vals['sessions'];
		$tabledataP[]=array(
						'audtype'=>ucwords($vals['userType']),
						'pageviews'=>number_format(($vals['pageviews']/$tot_pageviews)*100,2),
						// 'pageviews'=>number_format($vals['pageviews']),
						'bounce'=>number_format(((($avgBounce))*100), 2),
						'pagevisit'=>number_format($vals['pageviews']/$vals['visits'], 2),
						// 'avgduration'=>number_format(((($avgSessDur))*100),2)
						'avgduration'=>gmdate("H:i:s",($vals['sessionDuration']/$vals['sessions']))
						);
	
	}
	
	$topdataP[]=array(
				'daterange'=>$prevtoprevweek,
				'pageviews'=>number_format($tot_pageviews),
				'avgbounce'=>number_format((($avgBounceRate)*100), 2),
				'avgpagevisit'=>number_format($tot_pageviews/$tot_visits, 2),
				'avgduration'=>gmdate("H:i:s",($tot_sessionDuration/$tot_sessions))
				);

	$daterange[]=array('daterange'=>$lastTwoWeek);
	$request_chartarray=array('returnvisitor'=>$inArray['New Visitor']['users'],'newvisitor'=>$inArray['Returning Visitor']['users'],'daterange'=>$prevtoprevweek);
	$request_chartarray1=array('returnvisitor'=>$inArray1['New Visitor']['users'],'newvisitor'=>$inArray1['Returning Visitor']['users'],'daterange'=>$lastweek);
	$sendresponse=array('donut1_chart_data'=>$request_chartarray,'donut2_chart_data'=>$request_chartarray1,'tabulardata'=>$tabledata,'topbardata'=>$topdata,'tabulardata1'=>$tabledataP,'topbardata1'=>$topdataP,'daterange'=>$daterange);

   return $sendresponse; 
}/***calculation function end*****/

?>