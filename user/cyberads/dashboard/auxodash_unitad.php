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
$dashAdunit = new DashboardAuxo($db);
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
     $dashAdunit->range = $data->range;
     $dashAdunit->strtdate = $data->strtdate;
     $dashAdunit->enddate = $data->enddate;
     $dashAdunit->child_net_code = $data->child_net_code;
     $result_adunit = $dashAdunit->getAdunits();
  
     if(!empty($result_adunit['Display']) || !empty($result_adunit['App']) || !empty($result_adunit['Video'])){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adunit,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get display ad unit. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adunit,$start,$end){
      #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    } 
    foreach ($result_adunit as $key => $rowadunits) {

        foreach($rowadunits as $rowadunit){

        $unitName = $rowadunit['unit_name'];
        $dateindex= date('Y-m-j', strtotime($rowadunit['date']));
        #Data merge for first slide by adunit wise
        @$sumuplevel_array[$unitName]['adunit']=$unitName;

       if($key == 'Display'){
              @$sumuplevel_array[$unitName]['revenue_cmsShare'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
                  #inner
             @$datalevel2inner[$unitName][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadunit['date']));
             @$datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
         }
       if($key == 'App'){
              @$sumuplevel_array[$unitName]['revenue_cmsShare'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShareApp),2);
                    #inner
             @$datalevel2inner[$unitName][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadunit['date']));
             @$datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShareApp),2);
         }
       if($key == 'Video'){
              @$sumuplevel_array[$unitName]['revenue_cmsShare'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShareVid),2);
                    #inner
             @$datalevel2inner[$unitName][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadunit['date']));
             @$datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShareVid),2);
         }

      
     } //inner loop
   }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");

  /***level 1***/
    $cntDrill = $sumRevDrill = 0;
     foreach ($sumuplevel_array as $k => $value) {
       
         if($cntDrill < 9 ){
         $request_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['revenue_cmsShare'],2),
                                        'drilldown'=>$k,
                                    );

             }else{
                
                        $sumRevDrill += $value['revenue_cmsShare'];
                         $otherDrill = array(
                                        'name'=>"Other",
                                        'y'=>round($sumRevDrill,2),
                                        'drilldown'=>"Other"
                                    );
                    }
          $cntDrill++;
      }
      if(!empty($otherDrill)){
        
      $request_array['level1'][] = $otherDrill;
      }
    
    /***level 1***/
  
    #Merge innerdata of first page
  foreach ($sumuplevel_array as $ky => $value) {

        $sumuplevel_array[$ky]['innerdata'] = get_sum_index($datalevel2inner[$ky],$date_arr);
      }
    
    $cntAdunit = 0;  
    foreach ($sumuplevel_array as $ky => $value) {
        $sumOtherInner = array();
        if($cntAdunit < 9){
             $request_array1['adunit_table_data'][] = $value;
         }else{
            
              @$otherRev += number_format($value['revenue_cmsShare'],2);
                  
                $sumOtherInner[]=get_sum_index($datalevel2inner[$ky],$date_arr);
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
                    'adunit'=>"Other",
                    'revenue_cmsShare'=>$otherRev,
                    'innerdata'=>array_values($finalinnerdatalevel1)
                    );  
         }
         $cntAdunit++;

      } //loop end
        if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $request_array1['adunit_table_data'][] = $other_arr;
              $other_arr = array();
            }
      
   
 
    /***level 3***/
    
    $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
     
foreach($request_array1['adunit_table_data'] as $lvl3){
     $datalevel3inner = array();
    foreach($lvl3['innerdata'] as $lvl3data){
                  $datalevel3inner[] = (float)$lvl3data['revenue_cmsShareinnerlvl1'];
          }
          $request_array['level3'][]=array(
                                                'name'=>$lvl3['adunit'],
                                                'id'=>$lvl3['adunit'],
                                                'type'=>'area',
                                                'data'=>$datalevel3inner,
                                                 'marker'=>$mar, 
                                            );
    }
              
      /***level 3***/ 

$request_array['level3_dates']['dates'] = $date;   
    
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
function get_sum_index($array_data,$array_fulldate)
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