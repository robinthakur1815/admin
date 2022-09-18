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
     $result_sites = $video->getSites();

     if(!empty($result_sites)){
        #calculation
        $data = prepareData($cmsShare,$result_sites,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get video sites. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_sites,$start,$end){
     #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    } 
    foreach ($result_sites as $key => $rowsite) {
        
        $sites = str_replace(".","_",$rowsite['site_name']);
        $dateindex = date('Y-m-j', strtotime($rowsite['date']));
        #Data merge for first slide by site wise
        @$sumuplevel_array[$sites]['sites']=$sites;
        @$sumuplevel_array[$sites]['adreq']+=$rowsite['adr'];
        @$sumuplevel_array[$sites]['adimpr']+=$rowsite['adimr'];
        @$sumuplevel_array[$sites]['madreq']+=$rowsite['madr'];
        @$sumuplevel_array[$sites]['fillrate'] = number_format($sumuplevel_array[$sites]['adimpr']/$sumuplevel_array[$sites]['adreq']*100,1);
        @$sumuplevel_array[$sites]['clicks']+=$rowsite['clicks'];
        @$sumuplevel_array[$sites]['covg'] = $sumuplevel_array[$sites]['madreq'] > 0 ? number_format(($sumuplevel_array[$sites]['madreq']*100)/$sumuplevel_array[$sites]['adreq'],1) :0.0;
        @$sumuplevel_array[$sites]['ctr'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format($sumuplevel_array[$sites]['clicks']/$sumuplevel_array[$sites]['adimpr']*100,1):0.0;
        @$sumuplevel_array[$sites]['revenue_cmsShare'] += round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        @$sumuplevel_array[$sites]['ecpm'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format(floor(($sumuplevel_array[$sites]['revenue_cmsShare']/$sumuplevel_array[$sites]['adimpr']*1000)*100)/100, 2) : 0.00;
         @$sumuplevel_array[$sites]['expanded']=false;

         @$arraylevel2[$sites][$rowsite['rep_name']]+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);

        @$arraylevel3[$sites][$rowsite['rep_name']][date('j M', strtotime($rowsite['date']))]+=number_format($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        #Total row for first slide 
        @$total_array['adreq']+=$rowsite['adr'];
        @$total_array['adimpr']+=$rowsite['adimr'];
        @$total_array['madreq']+=$rowsite['madr'];
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$rowsite['clicks'];
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.0;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.0;
        @$total_array_full['revenue_cmsShare']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;

    #first table page inner
    @$datalevel1inner[$sites][$dateindex]['dateinner']= date('j M, Y', strtotime($rowsite['date']));
    @$datalevel1inner[$sites][$dateindex]['adrinner']+=$rowsite['adr'];
    @$datalevel1inner[$sites][$dateindex]['adimrinner']+=$rowsite['adimr'];
    @$datalevel1inner[$sites][$dateindex]['madrinner']+=$rowsite['madr'];
    @$datalevel1inner[$sites][$dateindex]['clicksinner']+=$rowsite['clicks'];
    @$datalevel1inner[$sites][$dateindex]['covginner'] = $datalevel1inner[$sites][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1inner[$sites][$dateindex]['madrinner']*100)/$datalevel1inner[$sites][$dateindex]['adrinner'],1) :0.0;
    @$datalevel1inner[$sites][$dateindex]['ctrinner']+=$datalevel1inner[$sites][$dateindex]['adimrinner'] > 0 ? number_format($datalevel1inner[$sites][$dateindex]['clicksinner']/$datalevel1inner[$sites][$dateindex]['adimrinner']*100,1):0.0;
    @$datalevel1inner[$sites][$dateindex]['revenue_cmsShareinner']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);  
    @$datalevel1inner[$sites][$dateindex]['ecpmxinner'] =$datalevel1inner[$sites][$dateindex]['adimrinner'] > 0 ? number_format(floor(($datalevel1inner[$sites][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$sites][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;

    #Level 1 table data
    #repname means dfp ad unit
    $repname= $rowsite['rep_name'];
    #inner
    @$datalevel2inner[$sites][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowsite['date']));
    @$datalevel2inner[$sites][$repname][$dateindex]['adrinnerlvl1']+=$rowsite['adr'];
    @$datalevel2inner[$sites][$repname][$dateindex]['adimrinnerlvl1']+=$rowsite['adimr'];
    @$datalevel2inner[$sites][$repname][$dateindex]['madrinnerlvl1']+=$rowsite['madr'];
    @$datalevel2inner[$sites][$repname][$dateindex]['clicksinnerlvl1']+=$rowsite['clicks'];
    @$datalevel2inner[$sites][$repname][$dateindex]['covginnerlvl1'] = $datalevel2inner[$sites][$repname][$dateindex]['madrinnerlvl1'] > 0 ? number_format(($datalevel2inner[$sites][$repname][$dateindex]['madrinnerlvl1']*100)/$datalevel2inner[$sites][$repname][$dateindex]['adrinnerlvl1'],1) :0.0;
    @$datalevel2inner[$sites][$repname][$dateindex]['ctr']+=$datalevel2inner[$sites][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($datalevel2inner[$sites][$repname][$dateindex]['clicksinnerlvl1']/$datalevel2inner[$sites][$repname][$dateindex]['adimrinnerlvl1']*100,1):0.0;
    @$datalevel2inner[$sites][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);  
    @$datalevel2inner[$sites][$repname][$dateindex]['ecpmxinnerlvl1'] = $datalevel2inner[$sites][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format(floor(($datalevel2inner[$sites][$repname][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$sites][$repname][$dateindex]['adimrinnerlvl1']*1000)*100)/100, 2) : 0.00; 

    #level 1 outer
    @$sumuplevel2_array[$sites][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$sites][$repname]['adrlvl1']+=$rowsite['adr'];
    @$sumuplevel2_array[$sites][$repname]['adimrlvl1']+=$rowsite['adimr'];
    @$sumuplevel2_array[$sites][$repname]['madrlvl1']+=$rowsite['madr'];
    @$sumuplevel2_array[$sites][$repname]['fillrate'] = number_format($sumuplevel2_array[$sites][$repname]['adimrlvl1']/$sumuplevel2_array[$sites][$repname]['adrlvl1']*100,1);
    @$sumuplevel2_array[$sites][$repname]['clickslvl1']+=$rowsite['clicks'];
    @$sumuplevel2_array[$sites][$repname]['covglvl1'] = $sumuplevel2_array[$sites][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$sites][$repname]['madrlvl1']*100)/$sumuplevel2_array[$sites][$repname]['adrlvl1'],1) :0.0;    
    @$sumuplevel2_array[$sites][$repname]['ctrlvl1'] = $sumuplevel2_array[$sites][$repname]['adimrlvl1'] > 0 ? number_format($sumuplevel2_array[$sites][$repname]['clickslvl1']/$sumuplevel2_array[$sites][$repname]['adimrlvl1']*100,1):0.0;
    @$sumuplevel2_array[$sites][$repname]['revenue_cmsSharelvl1']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$sites][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$sites][$repname]['adimrlvl1'] > 0 ? number_format(floor(($sumuplevel2_array[$sites][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$sites][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;
    
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
                         @$otherDrill = array(
                                        'name'=>"Other",
                                        'y'=>round($sumRevDrill,2),
                                        'drilldown'=>"Other".$key2
                                    );
                    }
                    $cntDrill++;
        }
        if(!empty($otherDrill)){
               @$datalevel2[] = $otherDrill;
           }
        $response_array['level2'][]=array(
                                        'name'=>$key2,
                                        'id'=>$key2,
                                        'type'=>'pie',
                                        'data'=>$datalevel2);
                                        
        }
        
      /***level 2***/
      

    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         @$sumuplevel_array[$ky]['innerdata'] = get_sum_index($datalevel1inner[$ky],$date_arr);
      } 
    foreach ($sumuplevel_array as $ky => $value) {
         $response_array['sites_table_data'][] = $value;
      } 
   
    
    #sorted revenue wise
   
foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
    {
            aasort($valuelvl1,'revenue_cmsSharelvl1');
             
             $otherAdreq = $otherAdimp = $otherMaAd=$otherClicks=$otherCovg=$otherCtr=$otherRev=$otherEcpm =0;
              $cnt=0;

    foreach($valuelvl1 as $value2)
        {
           $sumOtherInner = array();

        if($cnt < 9)
            {   

        $dataarray_array['sites_table_data_lvl1'][]=array(
            'level1value'=>$keyfinal2,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'adrlvl1'=>$value2['adrlvl1'],
            'adimrlvl1'=>$value2['adimrlvl1'],
            'madrlvl1'=>$value2['madrlvl1'],
            'fillratelvl1'=>number_format($value2['fillrate'],1),
            'covglvl1'=>number_format($value2['covglvl1'],1),
            'ctrlvl1'=>number_format($value2['ctrlvl1'],1),
            'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
            'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2),
            'innerdatalvl1'=>get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_arr),
            'expanded'=>false);
         }else{
                  $otherAdreq += $value2['adrlvl1'];
                  $otherAdimp += $value2['adimrlvl1'];
                  $otherMaAd += $value2['madrlvl1'];
                  $otherFillrate = number_format($otherAdimp/$otherAdreq*100,1);
                  $otherClicks += $value2['clickslvl1'];
                  $otherCovg = $otherMaAd > 0 ? number_format(($otherMaAd*100)/$otherAdreq,1) :0.0;
                  $otherCtr =  $otherAdimp > 0 ? number_format(($otherClicks)/$otherAdimp*100,1) :0.0;
                  $otherRev += $value2['revenue_cmsSharelvl1'];
                  $otherEcpm = $otherAdimp > 0 ? number_format(floor((($otherRev)/$otherAdimp*1000)*100)/100, 2) :0.00;
                  $sumOtherInner[] = get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_arr);
                  
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
                    'level1value'=>$keyfinal2,
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
              $dataarray_array['sites_table_data_lvl1'][] = $other_arr;
              $other_arr = array();
            }

        }
      $response_array['sites_table_data_lvl1']=array_values($dataarray_array['sites_table_data_lvl1']);  
       /***level 3***/
      $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
   

foreach($response_array['sites_table_data_lvl1'] as $lvl3){
     $datalevel3inner = array();
    foreach($lvl3['innerdatalvl1'] as $lvl3data){

                  $datalevel3inner[] = array(date('j M',strtotime($lvl3data['dateinnerlvl1'])),(float)$lvl3data['revenue_cmsShareinnerlvl1']);
          }
          $response_array['level3'][]=array(
                                                'name'=>$lvl3['level1value']."By".$lvl3['rep_namelvl1'],
                                                'id'=>$lvl3['rep_namelvl1'].$lvl3['level1value'],
                                                'type'=>'area',
                                                'data'=>$datalevel3inner,
                                                 'marker'=>$mar, 
                                            );
    }
        
  
      /***level 3***/
$response_array['finaldata']=array_merge($response_array['level2'],$response_array['level3']);
      
$response_array['sum_table_data'][] = $total_array;

return $response_array;
         
}/***calculation function end*****/
function get_sum_index($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinner'=> @$array_data[$date_value]['dateinner'],
        'adrinner'=> @$array_data[$date_value]['adrinner'],
        'adimrinner'=> @$array_data[$date_value]['adimrinner'],
        'madrinner'=> @$array_data[$date_value]['madrinner'],
        'fillrate'=> number_format(@$array_data[$date_value]['adimrinner']/@$array_data[$date_value]['adrinner']*100,1),
        'covginner'=> number_format(@$array_data[$date_value]['covginner'],1),
        'ctrinner'=> number_format(@$array_data[$date_value]['ctrinner'],1),
        'ecpmxinner'=> number_format(@$array_data[$date_value]['ecpmxinner'],2),
        'revenue_cmsShareinner'=> number_format(@$array_data[$date_value]['revenue_cmsShareinner'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinner'=> date('j M, Y', strtotime($date_value)),
        'adrinner'=> 0,
        'adimrinner'=>0,
        'madrinner'=> 0,
        'fillrate'=> 0,
        'covginner'=> 0,
        'ctrinner'=> 0,
        'ecpmxinner'=> 0,
        'revenue_cmsShareinner'=> 0
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