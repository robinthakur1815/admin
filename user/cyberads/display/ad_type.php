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
include_once '../../../objects/Display.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$display = new Display($db);
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
     if(!empty($rowShare)){if($rowShare['pub_display_share'] !=0){$cmsShare = $rowShare['pub_display_share']/100;}else{$cmsShare = 15/100;}}else{$cmsShare = 15/100;} 
     
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
     $display->range = $data->range;
     $display->strtdate = $data->strtdate;
     $display->enddate = $data->enddate;
     $display->child_net_code = $data->child_net_code;
     $result_adtype = $display->getAdtype();

     if(!empty($result_adtype)){
        #calculation
        $data = prepareData($cmsShare,$result_adtype,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get display ad type. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_adtype,$start,$end){
     #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    }
    
    foreach ($result_adtype as $key => $rowadtype) {
        $AdType= str_replace(")","",str_replace("(","",str_replace(" ","",$rowadtype['ad_type'])));
        #Data merge for first slide by adtype wise
        @$sumuplevel_array[$AdType]['adtype']=$AdType;
        @$sumuplevel_array[$AdType]['adreq']+=$rowadtype['adr'];
        @$sumuplevel_array[$AdType]['adimpr']+=$rowadtype['adimr'];
        @$sumuplevel_array[$AdType]['madreq']+=$rowadtype['madr'];
        @$sumuplevel_array[$AdType]['fillrate'] = number_format($sumuplevel_array[$AdType]['adimpr']/$sumuplevel_array[$AdType]['adreq']*100,1);
        @$sumuplevel_array[$AdType]['clicks']+=$rowadtype['clicks'];
        @$sumuplevel_array[$AdType]['covg'] = $sumuplevel_array[$AdType]['madreq'] > 0 ? number_format(($sumuplevel_array[$AdType]['madreq']*100)/$sumuplevel_array[$AdType]['adreq'],1) :0.0;
        @$sumuplevel_array[$AdType]['ctr'] = $sumuplevel_array[$AdType]['adimpr'] > 0 ? number_format($sumuplevel_array[$AdType]['clicks']/$sumuplevel_array[$AdType]['adimpr']*100,1):0.0;
        @$sumuplevel_array[$AdType]['revenue_cmsShare'] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
        @$sumuplevel_array[$AdType]['ecpm'] = $sumuplevel_array[$AdType]['adimpr'] > 0 ? number_format(floor(($sumuplevel_array[$AdType]['revenue_cmsShare']/$sumuplevel_array[$AdType]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$AdType]['expanded']=false;

        @$arraylevel2[$AdType][$rowadtype['rep_name']] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);

        @$arraylevel3[$AdType][$rowadtype['rep_name']][date('j M', strtotime($rowadtype['date']))] += number_format($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);

        #Total row for first slide 
        @$total_array['adreq']+=$rowadtype['adr'];
        @$total_array['adimpr']+=$rowadtype['adimr'];
        @$total_array['madreq']+=$rowadtype['madr'];
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$rowadtype['clicks'];
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.00;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.00;
        @$total_array_full['revenue_cmsShare'] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;
    #First slide datewise data   
    #$dateindex = $rowadtype['date'];
    $dateindex = date('Y-m-j', strtotime($rowadtype['date']));
    @$datalevel1inner[$AdType][$dateindex]['dateinner']= date('j M, Y', strtotime($rowadtype['date']));
    @$datalevel1inner[$AdType][$dateindex]['adreq']+=$rowadtype['adr'];
    @$datalevel1inner[$AdType][$dateindex]['adimpr']+=$rowadtype['adimr'];
    @$datalevel1inner[$AdType][$dateindex]['madreq']+=$rowadtype['madr'];
    @$datalevel1inner[$AdType][$dateindex]['clicks']+=$rowadtype['clicks'];
    @$datalevel1inner[$AdType][$dateindex]['covg'] = $datalevel1inner[$AdType][$dateindex]['madreq'] > 0 ? number_format(($datalevel1inner[$AdType][$dateindex]['madreq']*100)/$datalevel1inner[$AdType][$dateindex]['adreq'],1) :0.0;
    @$datalevel1inner[$AdType][$dateindex]['ctr'] = $datalevel1inner[$AdType][$dateindex]['adimpr'] > 0 ? number_format($datalevel1inner[$AdType][$dateindex]['clicks']/$datalevel1inner[$AdType][$dateindex]['adimpr']*100,1):0.0;
    @$datalevel1inner[$AdType][$dateindex]['revenue_cmsShare'] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
    @$datalevel1inner[$AdType][$dateindex]['ecpmx'] = $datalevel1inner[$AdType][$dateindex]['adimpr'] > 0 ? number_format(floor(($datalevel1inner[$AdType][$dateindex]['revenue_cmsShare']/$datalevel1inner[$AdType][$dateindex]['adimpr']*1000)*100)/100, 2) : 0.00;

    #Level 1 table data
    #repname means dfp ad unit
    $repname= str_replace(")","",str_replace("(","",str_replace(" ","",$rowadtype['rep_name'])));
    #inner
    @$datalevel2inner[$AdType][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadtype['date']));
    @$datalevel2inner[$AdType][$repname][$dateindex]['adrinnerlvl1']+=$rowadtype['adr'];
    @$datalevel2inner[$AdType][$repname][$dateindex]['adimrinnerlvl1']+=$rowadtype['adimr'];
    @$datalevel2inner[$AdType][$repname][$dateindex]['madrinnerlvl1']+=$rowadtype['madr'];
    @$datalevel2inner[$AdType][$repname][$dateindex]['clicksinnerlvl1']+=$rowadtype['clicks'];
    @$datalevel2inner[$AdType][$repname][$dateindex]['covginnerlvl1'] = $datalevel2inner[$AdType][$repname][$dateindex]['madrinnerlvl1'] > 0 ? number_format(($datalevel2inner[$AdType][$repname][$dateindex]['madrinnerlvl1']*100)/$datalevel2inner[$AdType][$repname][$dateindex]['adrinnerlvl1'],1) :0.0;
    @$datalevel2inner[$AdType][$repname][$dateindex]['ctr'] = $datalevel2inner[$AdType][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($datalevel2inner[$AdType][$repname][$dateindex]['clicksinnerlvl1']/$datalevel2inner[$AdType][$repname][$dateindex]['adimrinnerlvl1']*100,1):0.0;
    @$datalevel2inner[$AdType][$repname][$dateindex]['revenue_cmsShareinnerlvl1'] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);  
    @$datalevel2inner[$AdType][$repname][$dateindex]['ecpmxinnerlvl1'] = $datalevel2inner[$AdType][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format(floor(($datalevel2inner[$AdType][$repname][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$AdType][$repname][$dateindex]['adimrinnerlvl1']*1000)*100)/100, 2) : 0.00; 

    #level 1 outer
    @$sumuplevel2_array[$AdType][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$AdType][$repname]['adrlvl1']+=$rowadtype['adr'];
    @$sumuplevel2_array[$AdType][$repname]['adimrlvl1']+=$rowadtype['adimr'];
    @$sumuplevel2_array[$AdType][$repname]['madrlvl1']+=$rowadtype['madr'];
    @$sumuplevel2_array[$AdType][$repname]['fillrate'] = number_format($sumuplevel2_array[$AdType][$repname]['adimrlvl1']/$sumuplevel2_array[$AdType][$repname]['adrlvl1']*100,1);
    @$sumuplevel2_array[$AdType][$repname]['clickslvl1']+=$rowadtype['clicks'];
    @$sumuplevel2_array[$AdType][$repname]['covglvl1'] = $sumuplevel2_array[$AdType][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$AdType][$repname]['madrlvl1']*100)/$sumuplevel2_array[$AdType][$repname]['adrlvl1'],1) :0.0;    
    @$sumuplevel2_array[$AdType][$repname]['ctr'] = $sumuplevel2_array[$AdType][$repname]['adimrlvl1'] > 0 ? number_format($sumuplevel2_array[$AdType][$repname]['clickslvl1']/$sumuplevel2_array[$AdType][$repname]['adimrlvl1']*100,1):0.0;
    @$sumuplevel2_array[$AdType][$repname]['revenue_cmsSharelvl1'] += round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$AdType][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$AdType][$repname]['adimrlvl1'] > 0 ? number_format(floor(($sumuplevel2_array[$AdType][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$AdType][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;

    }

    $request_array['sum_table_data'][] = $total_array;
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
  /***level 1***/
    
     foreach ($sumuplevel_array as $k => $value) {
         
         $request_array['level1'][]=array(
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
        $response_array['level2'][]=array(
                                        'name'=>$key2,
                                        'id'=>$key2,
                                        'type'=>'pie',
                                        'data'=>$datalevel2);
                                        
        }
        
      /***level 2***/
     
         
    
   #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
      #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = get_sum_index($datalevel1inner[$ky],$date_arr);
      } 
    foreach ($sumuplevel_array as $ky => $value) {
         $request_array['adtype_table_data'][] = $value;
      } 
   
    #sorted revenue wise
     
 
foreach($sumuplevel2_array as $k=>$val)
        {
            
            aasort($val,'revenue_cmsSharelvl1');
            
            $otherAdreq = $otherAdimp = $otherMaAd=$otherClicks=$otherCovg=$otherCtr=$otherRev=$otherEcpm =0;
            
            
             $cnt=0;
       
    foreach($val as $value2)
            {
              $sumOtherInner = array();

        if($cnt < 9)
            {
        $dataarray_array['adtype_table_data_lvl1'][]=array(
            'level1value'=>$k,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'adrlvl1'=>$value2['adrlvl1'],
            'adimrlvl1'=>$value2['adimrlvl1'],
            'madrlvl1'=>$value2['madrlvl1'],
            'fillratelvl1'=>number_format($value2['fillrate'],1),
            'covglvl1'=>number_format($value2['covglvl1'],1),
            'ctrlvl1'=>number_format($value2['ctr'],1),
            'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
            'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2),
            'innerdatalvl1'=>get_sum_indexinner($datalevel2inner[$k][$value2['rep_namelvl1']],$date_arr),
            'expanded'=>false);

            


              }else{
                  $otherAdreq += $value2['adrlvl1'];
                  $otherAdimp += $value2['adimrlvl1'];
                  $otherMaAd += $value2['madrlvl1'];
                  $otherClicks += $value2['clickslvl1'];
                  $otherFillrate = number_format($otherAdimp/$otherAdreq*100,1);
                  $otherCovg = $otherMaAd > 0 ? number_format(($otherMaAd*100)/$otherAdreq,1) :0.0;
                  $otherCtr =  $otherAdimp > 0 ? number_format(($otherClicks)/$otherAdimp*100,1) :0.0;
                  $otherRev += $value2['revenue_cmsSharelvl1'];
                  $otherEcpm = $otherAdimp > 0 ? number_format(floor((($otherRev)/$otherAdimp*1000)*100)/100, 2) :0.00;
                  $sumOtherInner[]=get_sum_indexinner($datalevel2inner[$k][$value2['rep_namelvl1']],$date_arr);
                  
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
                  //krsort($finalinnerdatalevel1);  
                  $other_arr = array(
                    'level1value'=>$k,
                    'rep_namelvl1'=>"Other",
                    'adrlvl1'=>$otherAdreq,
                    'adimrlvl1'=>$otherAdimp,
                    'madrlvl1'=>$otherMaAd,
                    'fillratelvl1'=>$otherFillrate,
                    'covglvl1'=>$otherCovg,
                    'ctrlvl1'=>$otherCtr,
                    'ecpmxlvl1'=>$otherEcpm,
                    'revenue_cmsSharelvl1'=>$otherRev,
                    'innerdatalvl1'=>array_values($finalinnerdatalevel1),
                    'expanded'=>false);
                  
              }
             
              $cnt++;

            }
           
            if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $dataarray_array['adtype_table_data_lvl1'][] = $other_arr;
              $other_arr = array();
            }

          
            
       }
     
     $request_array['adtype_table_data_lvl1']=array_values($dataarray_array['adtype_table_data_lvl1']); 

       /****Level 3*****/
       $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
   foreach($request_array['adtype_table_data_lvl1'] as $lvl3){
     $datalevel3inner = array();
    foreach($lvl3['innerdatalvl1'] as $lvl3data){
                  $datalevel3inner[] = (float)$lvl3data['revenue_cmsShareinnerlvl1'];
          }
          $response_array['level3'][]=array(
                                                'name'=>$lvl3['level1value']."By".$lvl3['rep_namelvl1'],
                                                'id'=>$lvl3['rep_namelvl1'].$lvl3['level1value'],
                                                'type'=>'area',
                                                'data'=>$datalevel3inner,
                                                 'marker'=>$mar, 
                                            );
    }
     /****Level 3*****/


 $request_array['finaldata']=array_merge($response_array['level2'],$response_array['level3']);
 

$request_array['level3_dates']['dates'] = $date;     
    
    return $request_array;
         
}/***calculation function end*****/
function get_sum_index($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinner'=> @$array_data[$date_value]['dateinner'],
        'adreq'=> @$array_data[$date_value]['adreq'],
        'adimpr'=> @$array_data[$date_value]['adimpr'],
        'madr'=> @$array_data[$date_value]['madr'],
        'fillrate'=> number_format(@$array_data[$date_value]['adimpr']/@$array_data[$date_value]['adreq']*100,1),
        'covg'=> number_format(@$array_data[$date_value]['covg'],1),
        'ctr'=> number_format(@$array_data[$date_value]['ctr'],1),
        'ecpmx'=> number_format(@$array_data[$date_value]['ecpmx'],2),
        'revenue_cmsShare'=> number_format(@$array_data[$date_value]['revenue_cmsShare'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinner'=> date('j M, Y', strtotime($date_value)),
        'adreq'=> 0,
        'adimpr'=>0,
        'madr'=> 0,
        'fillrate'=> 0,
        'covg'=> 0,
        'ctr'=> 0,
        'ecpmx'=> 0,
        'revenue_cmsShare'=> 0
        );
    }
}

    return $formatedarray;
}
function get_sum_indexinner($array_data,$array_fulldate)
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