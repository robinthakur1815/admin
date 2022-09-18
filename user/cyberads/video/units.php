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
include_once '../../../objects/Video.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$video = new Video($db);
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
     if(!empty($rowShare)){if($rowShare['pub_video_share'] !=0){$cmsShare = $rowShare['pub_video_share']/100;}else{$cmsShare = 15/100;}}else{$cmsShare = 15/100;} 
     
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
     $video->range = $data->range;
     $video->strtdate = $data->strtdate;
     $video->enddate = $data->enddate;
     $video->child_net_code = $data->child_net_code;
     $result_adunit = $video->getAdunits();

     if(!empty($result_adunit)){
        #calculation
        $data = prepareData($cmsShare,$result_adunit,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get video ad unit. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_adunit,$start,$end){
       #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    } 
    foreach ($result_adunit as $key => $rowadunit) {
        
        $unitName = $rowadunit['unit_name'];
        $dateindex= date('Y-m-j', strtotime($rowadunit['date']));
        #Data merge for first slide by adunit wise
        @$sumuplevel_array[$unitName]['adunit']=$unitName;
        @$sumuplevel_array[$unitName]['adreq']+=$rowadunit['adr'];
        @$sumuplevel_array[$unitName]['adimpr']+=$rowadunit['adimr'];
        @$sumuplevel_array[$unitName]['madreq']+=$rowadunit['madr'];
        @$sumuplevel_array[$unitName]['fillrate'] = number_format($sumuplevel_array[$unitName]['adimpr']/$sumuplevel_array[$unitName]['adreq']*100,1);
        @$sumuplevel_array[$unitName]['clicks']+=$rowadunit['clicks'];
        @$sumuplevel_array[$unitName]['covg'] = $sumuplevel_array[$unitName]['madreq'] > 0 ? number_format(($sumuplevel_array[$unitName]['madreq']*100)/$sumuplevel_array[$unitName]['adreq'],1) :0.0;
        @$sumuplevel_array[$unitName]['ctr'] = $sumuplevel_array[$unitName]['adimpr'] > 0 ? number_format($sumuplevel_array[$unitName]['clicks']/$sumuplevel_array[$unitName]['adimpr']*100,1):0.0;
        @$sumuplevel_array[$unitName]['revenue_cmsShare'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
        @$sumuplevel_array[$unitName]['ecpm'] = $sumuplevel_array[$unitName]['adimpr'] > 0 ? number_format(floor(($sumuplevel_array[$unitName]['revenue_cmsShare']/$sumuplevel_array[$unitName]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$unitName]['expanded']=false;

        @$arraylevel3[$unitName][$rowadunit['rep_name']][$rowadunit['date']]+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
        #Total row for first slide 
        @$total_array['adreq']+=$rowadunit['adr'];
        @$total_array['adimpr']+=$rowadunit['adimr'];
        @$total_array['madreq']+=$rowadunit['madr'];
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$rowadunit['clicks'];
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.0;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.0;
        @$total_array_full['revenue_cmsShare']+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;

        #inner
    @$datalevel2inner[$unitName][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadunit['date']));
    @$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1']+=$rowadunit['adr'];
    @$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']+=$rowadunit['adimr'];
    @$datalevel2inner[$unitName][$dateindex]['madrinnerlvl1']+=$rowadunit['madr'];
    @$datalevel2inner[$unitName][$dateindex]['fillrateinnerlvl1'] = number_format($datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1']*100,2);
    @$datalevel2inner[$unitName][$dateindex]['clicksinnerlvl1']+=$rowadunit['clicks'];
    @$datalevel2inner[$unitName][$dateindex]['covginnerlvl1'] = $datalevel2inner[$unitName][$dateindex]['madrinnerlvl1'] > 0 ? number_format(($datalevel2inner[$unitName][$dateindex]['madrinnerlvl1']*100)/$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1'],1) :0.0;
    @$datalevel2inner[$unitName][$dateindex]['ctr'] = $datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($datalevel2inner[$unitName][$dateindex]['clicksinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']*100,1):0.0;
    @$datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);  
    @$datalevel2inner[$unitName][$dateindex]['ecpmxinnerlvl1'] =$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1'] > 0 ? number_format(floor(($datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']*1000)*100)/100, 2) : 0.00;
    
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
             $request_array['adunit_table_data'][] = $value;
         }else{
            
                  $otherAdreq += $value['adreq'];
                  $otherAdimp += $value['adimpr'];
                  $otherMaAd += $value['madreq'];
                  $otherFillrate = number_format($otherAdimp/$otherAdreq*100,1);
                  $otherClicks += $value['clicks'];
                  $otherCovg = $otherMaAd > 0 ? number_format(($otherMaAd*100)/$otherAdreq,1) :0.0;
                  $otherCtr =  $otherAdimp > 0 ? number_format(($otherClicks)/$otherAdimp*100,1) :0.0;
                  $otherRev += $value['revenue_cmsShare'];
                  $otherEcpm = $otherAdimp > 0 ? number_format(floor((($otherRev)/$otherAdimp*1000)*100)/100, 2) :0.00;
                $sumOtherInner[]=get_sum_index($datalevel2inner[$ky],$date_arr);
                  foreach($sumOtherInner as $level1suminner)
                    {
                        foreach($level1suminner as $level2key=>$level2values)
                        {
                            $finalindex=date('Y-m-j',strtotime($level2values['dateinnerlvl1']));
                            $finalinnerdatalevel1[$finalindex]['dateinnerlvl1']=$level2values['dateinnerlvl1'];
                            $finalinnerdatalevel1[$finalindex]['adrinnerlvl1']+=$level2values['adrinnerlvl1'];
                            $finalinnerdatalevel1[$finalindex]['adimrinnerlvl1']+=$level2values['adimrinnerlvl1'];
                            $finalinnerdatalevel1[$finalindex]['madrinnerlvl1']+=$level2values['madrinnerlvl1'];

                            $finalinnerdatalevel1[$finalindex]['fillrateinnerlvl1'] = $finalinnerdatalevel1[$finalindex]['adimrinnerlvl1'] > 0 ?number_format($finalinnerdatalevel1[$finalindex]['adimrinnerlvl1']/$finalinnerdatalevel1[$finalindex]['adrinnerlvl1']*100,1) :0.0;

                            $finalinnerdatalevel1[$finalindex]['covginnerlvl1'] = $finalinnerdatalevel1[$finalindex]['madrinnerlvl1'] > 0 ? number_format(($finalinnerdatalevel1[$finalindex]['madrinnerlvl1']*100)/$finalinnerdatalevel1[$finalindex]['adrinnerlvl1'],1) :0.0;

                            $finalinnerdatalevel1[$finalindex]['clicksinnerlvl1']+=$level2values['clicksinnerlvl1'];

                            $finalinnerdatalevel1[$finalindex]['ctr'] = $finalinnerdatalevel1[$finalindex]['adimrinnerlvl1'] > 0 ? number_format(($finalinnerdatalevel1[$finalindex]['clicksinnerlvl1'])/$finalinnerdatalevel1[$finalindex]['adimrinnerlvl1']*100,1) :0.0;

                            $finalinnerdatalevel1[$finalindex]['revenue_cmsShareinnerlvl1']+=$level2values['revenue_cmsShareinnerlvl1'];

                            $finalinnerdatalevel1[$finalindex]['ecpmxinnerlvl1'] =$finalinnerdatalevel1[$finalindex]['adimrinnerlvl1'] > 0 ? number_format(floor((($finalinnerdatalevel1[$finalindex]['revenue_cmsShareinnerlvl1'])/$finalinnerdatalevel1[$finalindex]['adimrinnerlvl1']*1000)*100)/100, 2) :0.00;

                            
                        }
                    }

                  //arsort($finalinnerdatalevel1);  
                  $other_arr = array(
                    'adunit'=>"Other",
                    'adreq'=>$otherAdreq,
                    'adimpr'=>$otherAdimp,
                    'madreq'=>$otherMaAd,
                    'fillrate'=>$otherFillrate,
                    'covg'=>$otherCovg,
                    'ctr'=>$otherCtr,
                    'ecpm'=>$otherEcpm,
                    'revenue_cmsShare'=>$otherRev,
                    'innerdata'=>array_values($finalinnerdatalevel1),
                    'expanded'=>false);  
         }
         $cntAdunit++;

      } //loop end
        if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $request_array['adunit_table_data'][] = $other_arr;
              $other_arr = array();
            }
      
   
 
    /***level 3***/
    
    $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
     
foreach($request_array['adunit_table_data'] as $lvl3){
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

 $request_array['sum_table_data'][] = $total_array;
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
        'adrinnerlvl1'=> @$array_data[$date_value]['adrinnerlvl1'],
        'adimrinnerlvl1'=> @$array_data[$date_value]['adimrinnerlvl1'],
        'madrinnerlvl1'=> @$array_data[$date_value]['madrinnerlvl1'],
        'fillrateinnerlvl1'=> number_format(@$array_data[$date_value]['adimrinnerlvl1']/@$array_data[$date_value]['adrinnerlvl1']*100,1),
        'covginnerlvl1'=> number_format(@$array_data[$date_value]['covginnerlvl1'],1),
        'clicksinnerlvl1'=> number_format(@$array_data[$date_value]['clicksinnerlvl1'],2),
        'ctr'=> number_format(@$array_data[$date_value]['ctr'],1),
        'ecpmxinnerlvl1'=> number_format(@$array_data[$date_value]['ecpmxinnerlvl1'],2),
        'revenue_cmsShareinnerlvl1'=> round(@$array_data[$date_value]['revenue_cmsShareinnerlvl1'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinnerlvl1'=> date('j M, Y', strtotime($date_value)),
        'adrinnerlvl1'=> 0,
        'adimrinnerlvl1'=>0,
        'madrinnerlvl1'=> 0,
        'fillrateinnerlvl1'=> 0,
        'covginnerlvl1'=> 0,
        'clicksinnerlvl1'=> 0,
        'ctr'=> 0,
        'ecpmxinnerlvl1'=> 0,
        'revenue_cmsShareinnerlvl1'=> 0
        );
    }
}

    return $formatedarray;
}
?>