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
#previous week Start  date and end date
$prevToPrevWeek = strtotime("-2 week +1 day");
$pstart_week = strtotime("last monday midnight",$prevToPrevWeek);
$pend_week = strtotime("next sunday",$pstart_week);
$pstart_week = date("Y-m-d",$pstart_week);
$pend_week = date("Y-m-d",$pend_week);


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
     $result_lichart = $result_content->toArray();
     # LAST WEEK
    $previous_week = strtotime("-1 week +1 day");
	$start_week = strtotime("last monday midnight",$previous_week);
	$end_week = strtotime("next sunday",$start_week);
	$start_week = date("Y-m-d",$start_week);
	$end_week = date("Y-m-d",$end_week);
	$analytics->pstart_week = $start_week;
    $analytics->pend_week = $end_week;
	$result_lweek = $analytics->getComChartQuery(); 
    $result_lweekchart = $result_lweek->toArray();
    
    if(!empty($result_lichart) || !empty($result_lweekchart)){
           
         #calculation
          $data = prepareData($result_lichart,$result_lweekchart,$pstart_week,$pend_week,$start_week,$end_week);
           
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

function prepareData($resultL,$resultp,$pstart_week,$pend_week,$start_week,$end_week){
$daterangel2l=date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($pend_week));
$total=0;$dateavail=array();

    foreach ($resultL as $val) 
	{
		
		$dateavail[]=$val->date;
		@$request_array[$val->date]+=$val->users;
		
	}
while (strtotime($pend_week) >= strtotime($pstart_week)) 
{
                $date_array[]=$pend_week;
                $pend_week = date ("Y-m-d", strtotime("-1 day", strtotime($pend_week)));
}
 $result=array_diff($date_array, array_unique($dateavail));	
 foreach($result as $valuerest){
        @$request_array[$valuerest] = 0;
	}	
krsort($request_array);	
	foreach($request_array as $valnew){
		//$preUser .= $val.',';
        $l2lweek[] = $valnew;
	}
#last week
$daterangelastweek=date("d M", strtotime($start_week)).' - '.date("d M, Y", strtotime($end_week));
$lastTwoWeek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($end_week));
    foreach ($resultp as $val) 
	{
		$dateavailp[]=$val->date;
		@$request_arraylastweek[$val->date]+=$val->users;
		
	}
while (strtotime($end_week) >= strtotime($start_week)) 
{
                $date_arrayp[]=$end_week;
                $end_week = date ("Y-m-d", strtotime("-1 day", strtotime($end_week)));
}
 $resultp=array_diff($date_arrayp, array_unique($dateavailp));	
 foreach($resultp as $valuerestp){
        @$request_arraylastweek[$valuerestp] = 0;
	}	
krsort($request_arraylastweek);	
	foreach($request_arraylastweek as $vallast){
        $lastweek[] = $vallast;
	}

		
$topdata[]=array('daterange'=>$lastTwoWeek,'daterange1'=>$daterangelastweek);
	
$sendresponse= array('linechartdata'=>array('daterangel2l'=>$daterangel2l,'graphvaluel2lweek'=>array_reverse($l2lweek),'daterangelastweek'=>$daterangelastweek,'graphvaluelastweek'=>array_reverse($lastweek)),'daterangetop'=>$topdata);

   return $sendresponse; 
}/***calculation function end*****/

?>