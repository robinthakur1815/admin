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
include_once '../../../config/connection.php';
include_once '../../../objects/Common.php';
include_once '../../../objects/DashboardAuxo.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$dashDevice = new DashboardAuxo($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];

#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->child_net_code) &&
    !empty($data->range)
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
     #set share property values   
     $header->pub_uniq_id = $data->uniq_id;   
     $result_share = $header->getPublisher();   
     $stmt_share = $result_share->get_result();
     $rowShare = $stmt_share->fetch_array(MYSQLI_ASSOC);
      if(!empty($rowShare)){
                 if($rowShare['pub_display_share'] !=0){
                   $cmsShare = $rowShare['pub_display_share']/100;}else{$cmsShare = 15/100;}
                   if($rowShare['pub_app_share'] !=0){
                   $cmsShareApp = $rowShare['pub_app_share']/100;}else{$cmsShareApp = 15/100;}
                   if($rowShare['pub_video_share'] !=0){
                   $cmsShareVid = $rowShare['pub_video_share']/100;}else{$cmsShareVid = 15/100;}

         }else{
            $cmsShare = 15/100;
            $cmsShareApp = 15/100;
            $cmsShareVid = 15/100;
         } 
     
     #check date range validation
     if($data->range == "custom"){
        if($data->strtdate == '' && $data->enddate == ''){
           #set response code - 422 validation error
           http_response_code(422);
  
           #tell the user
          echo json_encode(array("message" => "Date range invalid!","status_code"=>422));
          exit();
        }
     }
     #set Ad type property values
     $dashDevice->range = $data->range;
     $dashDevice->strtdate = $data->strtdate;
     $dashDevice->enddate = $data->enddate;
     $dashDevice->child_net_code = $data->child_net_code;
     $result_device = $dashDevice->getDevice();
   
	if(!empty($result_device['Display']) || !empty($result_device['App']) || !empty($result_device['Video'])){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_device,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get display device. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_device,$start,$end){
    #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    }   
    foreach ($result_device as $key => $rowdevices) {

       foreach($rowdevices as $rowdevice){

        if(strtolower($rowdevice['device'])=='high-end mobile devices') {
          $rowdevice['device'] = str_replace($rowdevice['device'],"Mobile",$rowdevice['device']);
         }
        $device = str_replace(".","_",$rowdevice['device']);
        $dateindex = date('Y-m-j', strtotime($rowdevice['date']));
        @$sumuplevel_array[$device]['device']=$device;
        #repname means dfp ad unit
        $repname= $rowdevice['rep_name'];
        @$sumuplevel2_array[$device][$repname]['rep_namelvl1']=$repname;

        if($key == 'Display'){

            @$sumuplevel_array[$device]['revenue_cmsShare'] += round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
            @$arraylevel2[$device][$repname]+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
            @$sumuplevel2_array[$device][$repname]['revenue_cmsSharelvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);

            #Drill inner
            @$datalevel2inner[$device][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowdevice['date']));
            @$datalevel2inner[$device][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
         }  
          if($key == 'App'){

            @$sumuplevel_array[$device]['revenue_cmsShare'] += round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareApp),2);
            @$arraylevel2[$device][$repname]+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareApp),2);
            @$sumuplevel2_array[$device][$repname]['revenue_cmsSharelvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareApp),2);

            #Drill inner
            @$datalevel2inner[$device][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowdevice['date']));
            @$datalevel2inner[$device][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareApp),2);
         } 
          if($key == 'Video'){

            @$sumuplevel_array[$device]['revenue_cmsShare'] += round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareVid),2);
            @$arraylevel2[$device][$repname]+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareVid),2);
            @$sumuplevel2_array[$device][$repname]['revenue_cmsSharelvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareVid),2);

            #Drill inner
            @$datalevel2inner[$device][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowdevice['date']));
            @$datalevel2inner[$device][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareVid),2);
         } 

    }//inner loop
  }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
   
     /***level 1***/
   foreach ($sumuplevel_array as $k => $value) {
        
         $response_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['revenue_cmsShare'],2),
                                        'drilldown'=>$k,
                                    );
          
      }
    /***level 1***/ 
     /***level 2***/ 
  
     foreach($arraylevel2 as $key2=>$value2)
        {

            $datalevel2 = array();
            arsort($arraylevel2[$key2]); //revenue value sort
            $cntDrill = $sumRevDrill = 0;         
        foreach($arraylevel2[$key2] as $keyinner=>$valueinner){
           if($cntDrill < 9 ){
                        $datalevel2[]=array(
                                        'name'=>$keyinner,
                                        'y'=>round($valueinner,2),
                                        'drilldown'=>$keyinner.$key2
                                    );
                    }else{
                        $sumRevDrill += $valueinner;
                         $otherDrill = array(
                                        'name'=>"Other",
                                        'y'=>round($sumRevDrill,2),
                                        'drilldown'=>"Other".$key2
                                    );
                    }
                    $cntDrill++;
        }
        if(!empty($otherDrill)){
             $datalevel2[] = $otherDrill;
           }
        $response_array1['level2'][]=array(
                                        'name'=>$key2,
                                        'id'=>$key2,
                                        'type'=>'pie',
                                        'data'=>$datalevel2);
                                        
        }
        
      /***level 2***/
    
#sorted revenue wise
foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
    {
            aasort($valuelvl1,'revenue_cmsSharelvl1');
            @$otherRev=0;
            $cnt=0;

    foreach($valuelvl1 as $value2)
        {
           $sumOtherInner = array();

        if($cnt < 9)
            {   

        $dataarray_array['device_table_data_lvl1'][]=array(
            'level1value'=>$keyfinal2,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'innerdatalvl1'=>get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_arr));
         }else{
              
                  $otherRev += number_format($value2['revenue_cmsSharelvl1'],2);
                  
                  $sumOtherInner[] = get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_arr);
                  
                  foreach($sumOtherInner as $level1suminner)
                    {
                        foreach($level1suminner as $level2key=>$level2values)
                        {
                            $finalindex=date('Y-m-j',strtotime($level2values['dateinnerlvl1']));
                            @$finalinnerdatalevel1[$finalindex]['dateinnerlvl1']=$level2values['dateinnerlvl1'];
                             @$finalinnerdatalevel1[$finalindex]['revenue_cmsShareinnerlvl1']+=number_format($level2values['revenue_cmsShareinnerlvl1'],2);
    
                        }
                    }
                   
                  $other_arr = array(
                    'level1value'=>$keyfinal2,
                    'rep_namelvl1'=>"Other",
                    'innerdatalvl1'=>array_values($finalinnerdatalevel1)
                    );
                  
              }
             
              $cnt++;

            }

            if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $dataarray_array['device_table_data_lvl1'][] = $other_arr;
              $other_arr = array();
            }

        }


      $response_array1['device_table_data_lvl1']=array_values($dataarray_array['device_table_data_lvl1']);
    
         /***level 3***/
      $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
   

foreach($response_array1['device_table_data_lvl1'] as $lvl3){
     $datalevel3inner = array();
    foreach($lvl3['innerdatalvl1'] as $lvl3data){
                  $datalevel3inner[] = (float)$lvl3data['revenue_cmsShareinnerlvl1'];
          }
          $response_array2['level3'][]=array(
                                                'name'=>$lvl3['level1value']."By".$lvl3['rep_namelvl1'],
                                                'id'=>$lvl3['rep_namelvl1'].$lvl3['level1value'],
                                                'type'=>'area',
                                                'data'=>$datalevel3inner,
                                                 'marker'=>$mar, 
                                            );
    }
        
   
      /***level 3***/ 
   
$response_array['finaldata']=array_merge($response_array1['level2'],$response_array2['level3']);

$response_array['level3_dates']['dates'] = $date;
    return $response_array;
         
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

function get_sum_indexinner($array_data,$array_fulldate)
{
   

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinnerlvl1'=> @$array_data[$date_value]['dateinnerlvl1'],
        'revenue_cmsShareinnerlvl1'=> round(@$array_data[$date_value]['revenue_cmsShareinnerlvl1'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinnerlvl1'=> date('j M, Y', strtotime($date_value)),
        'revenue_cmsShareinnerlvl1'=> 0
        );
    }
}

    return $formatedarray;
}
?>