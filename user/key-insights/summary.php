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
header("HTTP/1.1 200 OK");
#Time Zone
date_default_timezone_set('Asia/Kolkata');
//date_default_timezone_set("Pacific/Honolulu");
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
          $data = prepareData($result_prechart,$result_lweekchart,$lastweek,$prevtoprevweek);
           
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

function prepareData($resultL,$resultLw,$lastweek,$prevtoprevweek){


    foreach ($resultL as $val) 
    {
        if(strtolower($val->deviceCategory) == 'desktop'){
            @$deviceinArray[strtolower($val->deviceCategory)]['bounce']  += $val->bounces;
            @$deviceinArray[strtolower($val->deviceCategory)]['session'] += $val->sessions;
        }else if(strtolower($val->deviceCategory) == 'mobile'){
            @$deviceinArray[strtolower($val->deviceCategory)]['bounce']  += $val->bounces;
            @$deviceinArray[strtolower($val->deviceCategory)]['session'] += $val->sessions;
        }else if(strtolower($val->deviceCategory) == 'tablet'){
            @$deviceinArray[strtolower($val->deviceCategory)]['bounce']  += $val->bounces;
            @$deviceinArray[strtolower($val->deviceCategory)]['session'] += $val->sessions;
        }
            
        
    }
   

    $bouncerateP['mobile'] = round(($deviceinArray['mobile']['bounce']/($deviceinArray['mobile']['session']>0 ? $deviceinArray['mobile']['session'] : 1))*100,2);
    $bouncerateP['desktop'] = round(($deviceinArray['desktop']['bounce']/($deviceinArray['desktop']['session']>0 ? $deviceinArray['desktop']['session'] : 1))*100,2);
    $bouncerateP['tablet'] = round(($deviceinArray['tablet']['bounce']/($deviceinArray['tablet']['session']>0 ? $deviceinArray['tablet']['session'] : 1))*100,2);

//======================= LAST WEEK=========================//

    //////////////////////// donut chart  ////////////////////////////////////////////////

    foreach ($resultLw as $vals) 
    {
       if(strtolower($vals->deviceCategory) == 'desktop'){
            @$deviceinArray1[strtolower($vals->deviceCategory)]['bounce']  += $vals->bounces;
            @$deviceinArray1[strtolower($vals->deviceCategory)]['session'] += $vals->sessions;
        }else if(strtolower($vals->deviceCategory) == 'mobile'){
            @$deviceinArray1[strtolower($vals->deviceCategory)]['bounce']  += $vals->bounces;
            @$deviceinArray1[strtolower($vals->deviceCategory)]['session'] += $vals->sessions;
        }else if(strtolower($vals->deviceCategory) == 'tablet'){
            @$deviceinArray1[strtolower($vals->deviceCategory)]['bounce']  += $vals->bounces;
            @$deviceinArray1[strtolower($vals->deviceCategory)]['session'] += $vals->sessions;
        }
     
    }
   
   $bouncerate['mobile'] = round(($deviceinArray1['mobile']['bounce']/$deviceinArray1['mobile']['session'])*100,2);
    $bouncerate['desktop'] = round(($deviceinArray1['desktop']['bounce']/$deviceinArray1['desktop']['session'])*100,2);
    $bouncerate['tablet'] = round(($deviceinArray1['tablet']['bounce']/$deviceinArray1['tablet']['session'])*100,2);
    
    $tabledata_bouncerate1[] = array("position2"=>"Mobile","curr"=>number_format($bouncerate['mobile'],1),"pre"=>number_format($bouncerateP['mobile'],1));
    $tabledata_bouncerate2[] = array("position2"=>"Desktop","curr"=>number_format($bouncerate['desktop'],1),"pre"=>number_format($bouncerateP['desktop'],1));
    $tabledata_bouncerate3[] = array("position2"=>"Tablet","curr"=>number_format($bouncerate['tablet'],1),"pre"=>number_format($bouncerateP['tablet'],1));
  
  // $tabledata_bouncerate[]=array(
  //       'mobile'=>$bouncerate['mobile'],
  //       'desktop'=>$bouncerate['desktop'],
  //       'tablet'=>$bouncerate['tablet'],
  //       'mobileP'=>$bouncerateP['mobile'],
  //       'desktopP'=>$bouncerateP['desktop'],
  //       'tabletP'=>$bouncerateP['tablet']
  //   );
$tabledata_bouncerate = array_merge($tabledata_bouncerate1,$tabledata_bouncerate2,$tabledata_bouncerate3);

    $headerDateRange = $lastweek.' v/s '.$prevtoprevweek;
    $daterange[]=array('daterange'=>$headerDateRange);
    $sendresponse=array('tabulardata_bouncerate'=>$tabledata_bouncerate,'daterange'=>$headerDateRange);

   return $sendresponse; 
}/***calculation function end*****/

?>