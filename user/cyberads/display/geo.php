<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK");
#for number format
ini_set('serialize_precision', 10);
ini_set('memory_limit', '-1');
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
     $result_geo = $display->getGeo();

     if(!empty($result_geo)){
        #calculation
        $data = prepareData($cmsShare,$result_geo,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get display geo. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_geo,$start,$end){
 #Date Array
    while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_arr[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    }
    foreach ($result_geo as $key => $rowgeo) {
        if(strtolower($rowgeo['rep_name'])=='high-end mobile devices') {
        $rowgeo['rep_name'] = str_replace($rowgeo['rep_name'],"Mobile",$rowgeo['rep_name']);
    }
        $geo = str_replace(".","_",$rowgeo['country_name']);
        $dateindex = date('Y-m-j', strtotime($rowgeo['date']));
        #Data merge for first slide by site wise
        @$sumuplevel_array[$geo]['geo']=$geo;
        @$sumuplevel_array[$geo]['adreq']+=$rowgeo['adr'];
        @$sumuplevel_array[$geo]['adimpr']+=$rowgeo['adimr'];
        @$sumuplevel_array[$geo]['madreq']+=$rowgeo['madr'];
        @$sumuplevel_array[$geo]['fillrate'] = number_format($sumuplevel_array[$geo]['adimpr']/$sumuplevel_array[$geo]['adreq']*100,1);
        @$sumuplevel_array[$geo]['clicks']+=$rowgeo['clicks'];
        @$sumuplevel_array[$geo]['covg'] = $sumuplevel_array[$geo]['madreq'] > 0 ? number_format(($sumuplevel_array[$geo]['madreq']*100)/$sumuplevel_array[$geo]['adreq'],1) :0.0;
        @$sumuplevel_array[$geo]['ctr'] = $sumuplevel_array[$geo]['adimpr'] > 0 ? number_format($sumuplevel_array[$geo]['clicks']/$sumuplevel_array[$geo]['adimpr']*100,1):0.0;
        @$sumuplevel_array[$geo]['revenue_cmsShare'] += round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);
        @$sumuplevel_array[$geo]['ecpm'] = $sumuplevel_array[$geo]['adimpr'] > 0 ? number_format(floor(($sumuplevel_array[$geo]['revenue_cmsShare']/$sumuplevel_array[$geo]['adimpr']*1000)*100)/100, 2) : 0.00;
        
         @$sumuplevel_array[$geo]['expanded']=false;

         @$arraylevel2[$geo][$rowgeo['rep_name']]+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);


    #first table page inner
    @$datalevel1inner[$geo][$dateindex]['dateinner']= date('j M, Y', strtotime($rowgeo['date']));
    @$datalevel1inner[$geo][$dateindex]['adrinner']+=$rowgeo['adr'];
    @$datalevel1inner[$geo][$dateindex]['adimrinner']+=$rowgeo['adimr'];
    @$datalevel1inner[$geo][$dateindex]['madrinner']+=$rowgeo['madr'];
    @$datalevel1inner[$geo][$dateindex]['clicksinner']+=$rowgeo['clicks'];
    @$datalevel1inner[$geo][$dateindex]['covginner'] = $datalevel1inner[$geo][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1inner[$geo][$dateindex]['madrinner']*100)/$datalevel1inner[$geo][$dateindex]['adrinner'],1) :0.0;
    @$datalevel1inner[$geo][$dateindex]['ctrinner']+=$datalevel1inner[$geo][$dateindex]['adimrinner'] > 0 ? number_format($datalevel1inner[$geo][$dateindex]['clicksinner']/$datalevel1inner[$geo][$dateindex]['adimrinner']*100,1):0.0;
    @$datalevel1inner[$geo][$dateindex]['revenue_cmsShareinner']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);  
    @$datalevel1inner[$geo][$dateindex]['ecpmxinner'] =$datalevel1inner[$geo][$dateindex]['adimrinner'] > 0 ? number_format(floor(($datalevel1inner[$geo][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$geo][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;

    #Level 1 table data
    #repname means dfp ad unit
    $repname= $rowgeo['rep_name'];
    #inner
    @$datalevel2inner[$geo][$repname][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowgeo['date']));
    @$datalevel2inner[$geo][$repname][$dateindex]['adrinnerlvl1']+=$rowgeo['adr'];
    @$datalevel2inner[$geo][$repname][$dateindex]['adimrinnerlvl1']+=$rowgeo['adimr'];
    @$datalevel2inner[$geo][$repname][$dateindex]['madrinnerlvl1']+=$rowgeo['madr'];
    @$datalevel2inner[$geo][$repname][$dateindex]['clicksinnerlvl1']+=$rowgeo['clicks'];
    @$datalevel2inner[$geo][$repname][$dateindex]['covginnerlvl1'] = $datalevel2inner[$geo][$repname][$dateindex]['madrinnerlvl1'] > 0 ? number_format(($datalevel2inner[$geo][$repname][$dateindex]['madrinnerlvl1']*100)/$datalevel2inner[$geo][$repname][$dateindex]['adrinnerlvl1'],1) :0.0;
    @$datalevel2inner[$geo][$repname][$dateindex]['ctr']+=$datalevel2inner[$geo][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($datalevel2inner[$geo][$repname][$dateindex]['clicksinnerlvl1']/$datalevel2inner[$geo][$repname][$dateindex]['adimrinnerlvl1']*100,1):0.0;
    @$datalevel2inner[$geo][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);  
    @$datalevel2inner[$geo][$repname][$dateindex]['ecpmxinnerlvl1'] = $datalevel2inner[$geo][$repname][$dateindex]['adimrinnerlvl1'] > 0 ? number_format(floor(($datalevel2inner[$geo][$repname][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$geo][$repname][$dateindex]['adimrinnerlvl1']*1000)*100)/100, 2) : 0.00; 

    #level 1 outer
    @$sumuplevel2_array[$geo][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$geo][$repname]['adrlvl1']+=$rowgeo['adr'];
    @$sumuplevel2_array[$geo][$repname]['adimrlvl1']+=$rowgeo['adimr'];
    @$sumuplevel2_array[$geo][$repname]['madrlvl1']+=$rowgeo['madr'];
    @$sumuplevel2_array[$geo][$repname]['fillrate'] = number_format($sumuplevel2_array[$geo][$repname]['adimrlvl1']/$sumuplevel2_array[$geo][$repname]['adrlvl1']*100,1);
    @$sumuplevel2_array[$geo][$repname]['clickslvl1']+=$rowgeo['clicks'];
    @$sumuplevel2_array[$geo][$repname]['covglvl1'] = $sumuplevel2_array[$geo][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$geo][$repname]['madrlvl1']*100)/$sumuplevel2_array[$geo][$repname]['adrlvl1'],1) :0.0;    
    @$sumuplevel2_array[$geo][$repname]['ctrlvl1'] = $sumuplevel2_array[$geo][$repname]['adimrlvl1'] > 0 ? number_format($sumuplevel2_array[$geo][$repname]['clickslvl1']/$sumuplevel2_array[$geo][$repname]['adimrlvl1']*100,1):0.0;
    @$sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$geo][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$geo][$repname]['adimrlvl1'] > 0 ? number_format(floor(($sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$geo][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;
    
   }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");

    #Merge innerdata of first page
    $count = 0;  
    foreach ($sumuplevel_array as $ky => $value) {
        $sumOtherInner = array();
        if($count < 9){
         $sumuplevel_newarr[$ky] = $value;
         $sumuplevel_newarr[$ky]['innerdata'] = get_sum_index($datalevel1inner[$ky],$date_arr);
         #Total row for first slide 
        @$total_array['adreq']+=$value['adreq'];
        @$total_array['adimpr']+=$value['adimpr'];
        @$total_array['madreq']+=$value['madreq'];
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$value['clicks'];
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.0;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.0;
        @$total_array['revenue_cmsShare']+=$value['revenue_cmsShare'];
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;
        }else{
            @$total_array['adreq']+=$value['adreq'];
            @$total_array['adimpr']+=$value['adimpr'];
            @$total_array['madreq']+=$value['madreq'];
            @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
            @$total_array['clicks']+=$value['clicks'];
            @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.0;
            @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.0;
            @$total_array['revenue_cmsShare']+=$value['revenue_cmsShare'];
            @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;
              
                  @$otherAdreq += $value['adreq'];
                  @$otherAdimp += $value['adimpr'];
                  @$otherMaAd += $value['madreq'];
                  @$otherFillrate = number_format($otherAdimp/$otherAdreq*100,1);
                  @$otherClicks += $value['clicks'];
                  @$otherCovg = $otherMaAd > 0 ? number_format(($otherMaAd*100)/$otherAdreq,1) :0.0;
                  @$otherCtr =  $otherAdimp > 0 ? number_format(($otherClicks)/$otherAdimp*100,1) :0.0;
                  @$otherRev += $value['revenue_cmsShare'];
                  @$otherEcpm = $otherAdimp > 0 ? number_format(floor((($otherRev)/$otherAdimp*1000)*100)/100, 2) :0.00;
                @$sumOtherInner[]=get_sum_index($datalevel1inner[$ky],$date_arr);
                  foreach($sumOtherInner as $level1suminner)
                    {
                        foreach($level1suminner as $level2key=>$level2values)
                        {
                            $finalindex=date('Y-m-j',strtotime($level2values['dateinner']));
                            @$finalinnerdatalevel1[$finalindex]['dateinner']=$level2values['dateinner'];
                            @$finalinnerdatalevel1[$finalindex]['adrinner']+=$level2values['adrinner'];
                            @$finalinnerdatalevel1[$finalindex]['adimrinner']+=$level2values['adimrinner'];
                            @$finalinnerdatalevel1[$finalindex]['madrinner']+=$level2values['madrinner'];

                            @$finalinnerdatalevel1[$finalindex]['fillrate'] = $finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ?number_format($finalinnerdatalevel1[$finalindex]['adimrinner']/$finalinnerdatalevel1[$finalindex]['adrinner']*100,1) :0.0;

                            @$finalinnerdatalevel1[$finalindex]['covginner'] = $finalinnerdatalevel1[$finalindex]['madrinner'] > 0 ? number_format(($finalinnerdatalevel1[$finalindex]['madrinner']*100)/$finalinnerdatalevel1[$finalindex]['adrinner'],1) :0.0;

                            @$finalinnerdatalevel1[$finalindex]['clicksinner']+=$level2values['clicksinner'];

                            @$finalinnerdatalevel1[$finalindex]['ctrinner'] = $finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ? number_format(($finalinnerdatalevel1[$finalindex]['clicksinner'])/$finalinnerdatalevel1[$finalindex]['adimrinner']*100,1) :0.0;

                            @$finalinnerdatalevel1inner[$finalindex]['revenue_cmsShareinner']+=$level2values['revenue_cmsShareinner'];
                            @$finalinnerdatalevel1[$finalindex]['revenue_cmsShareinner']=number_format($finalinnerdatalevel1inner[$finalindex]['revenue_cmsShareinner'],2);

                            @$finalinnerdatalevel1[$finalindex]['ecpmxinner'] =$finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ? number_format(floor((($finalinnerdatalevel1inner[$finalindex]['revenue_cmsShareinner'])/$finalinnerdatalevel1[$finalindex]['adimrinner']*1000)*100)/100, 2) :0.00;

                            
                        }
                    }

                   
                  $other_arr = array(
                    'geo'=>"Other",
                    'adreq'=>$otherAdreq,
                    'adimpr'=>$otherAdimp,
                    'madreq'=>$otherMaAd,
                    'fillrate'=>$otherFillrate,
                    'covg'=>$otherCovg,
                    'ctr'=>$otherCtr,
                    'revenue_cmsShare'=>$otherRev,
                    'ecpm'=>$otherEcpm,
                    'expanded'=>false,
                    'innerdata'=>array_values($finalinnerdatalevel1));
        }
        $count++;
      }

      if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $sumuplevel_newarr['Other'] = $other_arr;
              $other_arr = array();
            }


        /***level 1***/
    foreach ($sumuplevel_newarr as $k => $value) {
         
         $response_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['revenue_cmsShare'],2),
                                        'drilldown'=>$k,
                                    );
          $coun_arr[] = $k;
      }
     
    /***level 1***/ 
    /***level 2***/ 
 
$sumRevDrillMobile =$sumRevDrillDesktop =$sumRevDrillTablets =$sumRevDrillTv = 0;  
    foreach($arraylevel2 as $key2=>$value2){
        $datalevel2 = array();
        arsort($arraylevel2[$key2]);
        foreach($arraylevel2[$key2] as $keyinner=>$valueinner){
            /***condition for top 9 country wise***/ 
            if(in_array($key2,$coun_arr)){
                $datalevel2[]=array(
                                'name'=>$keyinner,
                                'y'=>round($valueinner,2),
                                'drilldown'=>$keyinner.$key2
                            );
            }
        }
        if(in_array($key2,$coun_arr)){
            $response_array['level2'][]=array(
                                        'name'=>$key2,
                                        'id'=>$key2,
                                        'type'=>'pie',
                                        'data'=>$datalevel2);
        }else{
            @$sumRevDrillMobile += $value2['Mobile'];
            @$sumRevDrillDesktop += $value2['Desktop'];
            @$sumRevDrillTablets += $value2['Tablets'];
            @$sumRevDrillTv += $value2['Connected TV'];
        }
    }
    
      $deviceArray = array("Mobile"=>$sumRevDrillMobile,"Desktop"=>$sumRevDrillDesktop,"Tablets"=>$sumRevDrillTablets,"Connected TV"=>$sumRevDrillTv);
      arsort($deviceArray);
    
      foreach($deviceArray as $devKey => $valDev){
              $dataOthDev[] = array(
                                'name'=>$devKey,
                                'y'=>round($valDev,2),
                                'drilldown'=>$devKey."Other"
                            );
      }  
     
    if(in_array('Other',$coun_arr)){
        $response_array['level2'][]=array(
                                        'name'=>"Other",
                                        'id'=>"Other",
                                        'type'=>'pie',
                                        'data'=>$dataOthDev);
       
        
    } 
        /***level 2***/
       
    foreach ($sumuplevel_newarr as $ky => $value) {
         $response_array['geo_table_data'][] = $value;
      }    
    
    $response_array['sum_table_data'][] = $total_array;
    
    #sorted revenue wise
    
    
    foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
        {
            //$sumOtherInner1 = array();
         if(in_array($keyfinal2,$coun_arr)){
            foreach($valuelvl1 as $value2)
            {
                $dataarray_array['geo_table_data_lvl1'][]=array(
                    'level1value'=>$keyfinal2,
                    'rep_namelvl1'=>$value2['rep_namelvl1'],
                    'adrlvl1'=>$value2['adrlvl1'],
                    'adimrlvl1'=>$value2['adimrlvl1'],
                    'madrlvl1'=>$value2['madrlvl1'],
                    'fillratelvl1'=> number_format($value2['adimrlvl1']/$value2['adrlvl1']*100,1),
                    'covglvl1'=>number_format($value2['covglvl1'],1),
                    'ctrlvl1'=>number_format($value2['ctrlvl1'],1),
                    'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
                    'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2),
                    'innerdatalvl1'=>get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_arr),
                    'expanded'=>false);
                    }

            }else{
                
               foreach($valuelvl1 as $valOther)
                 {
                   
                  @$otherArr[$valOther['rep_namelvl1']]['adr'] += $valOther['adrlvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['imp'] += $valOther['adimrlvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['madr'] += $valOther['madrlvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['clicks'] += $valOther['clickslvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['fillrate'] = number_format($otherArr[$valOther['rep_namelvl1']]['imp']/$otherArr[$valOther['rep_namelvl1']]['adr']*100,1);
                  @$otherArr[$valOther['rep_namelvl1']]['covg'] = $otherArr[$valOther['rep_namelvl1']]['madr'] > 0 ? number_format(($otherArr[$valOther['rep_namelvl1']]['madr']*100)/$otherArr[$valOther['rep_namelvl1']]['adr'],1) :0.0;

                  @$otherArr[$valOther['rep_namelvl1']]['ctr'] =  $otherArr[$valOther['rep_namelvl1']]['imp'] > 0 ? number_format(($otherArr[$valOther['rep_namelvl1']]['clicks'])/$otherArr[$valOther['rep_namelvl1']]['imp']*100,1) :0.0;
                  @$otherArrRev[$valOther['rep_namelvl1']]['rev'] += $valOther['revenue_cmsSharelvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['rev'] = number_format($otherArrRev[$valOther['rep_namelvl1']]['rev'],2);
                  @$otherArr[$valOther['rep_namelvl1']]['ecpm'] = $otherArr[$valOther['rep_namelvl1']]['imp'] > 0 ? number_format(floor((($otherArrRev[$valOther['rep_namelvl1']]['rev'])/$otherArr[$valOther['rep_namelvl1']]['imp']*1000)*100)/100, 2) :0.00;
                  @$sumOtherInner1[$valOther['rep_namelvl1']] = get_sum_indexinner($datalevel2inner[$keyfinal2][$valOther['rep_namelvl1']],$date_arr);
                  foreach($sumOtherInner1[$valOther['rep_namelvl1']] as $othervalues)
                    {
                        
                            $finalindexOth = date('Y-m-j',strtotime($othervalues['dateinnerlvl1']));
                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['dateinnerlvl1']=$othervalues['dateinnerlvl1'];
                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adrinnerlvl1']+=$othervalues['adrinnerlvl1'];
                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1']+=$othervalues['adimrinnerlvl1'];
                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['madrinnerlvl1']+=$othervalues['madrinnerlvl1'];

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['fillrateinnerlvl1'] = $otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1'] > 0 ?number_format($otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1']/$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adrinnerlvl1']*100,1) :0.0;

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['covginnerlvl1'] = $otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['madrinnerlvl1'] > 0 ? number_format(($otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['madrinnerlvl1']*100)/$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adrinnerlvl1'],1) :0.0;

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['clicksinnerlvl1'] += $othervalues['clicksinnerlvl1'];

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['ctr'] = $otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1'] > 0 ? number_format(($otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['clicksinnerlvl1'])/$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1']*100,1) :0.0;

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['revenue_cmsShareinnerlvl1']+=$othervalues['revenue_cmsShareinnerlvl1'];

                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['ecpmxinnerlvl1'] =$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1'] > 0 ? number_format(floor((($otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['revenue_cmsShareinnerlvl1'])/$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['adimrinnerlvl1']*1000)*100)/100, 2) :0.00;

                            
                        
                    }
                  

                 }  //loop end

            }
        

        }
     
         
      if(!empty($otherArr)){
         foreach($otherArr as $dvKey => $otherDeviceValue){
                  $dataarray_array['geo_table_data_lvl1'][] = array(
                    'level1value'=>"Other",
                    'rep_namelvl1'=>$dvKey,
                    'adrlvl1'=>$otherDeviceValue['adr'],
                    'adimrlvl1'=>$otherDeviceValue['imp'],
                    'madrlvl1'=>$otherDeviceValue['madr'],
                    'fillratelvl1'=>$otherDeviceValue['fillrate'],
                    'covglvl1'=>$otherDeviceValue['covg'],
                    'ctrlvl1'=>$otherDeviceValue['ctr'],
                    'ecpmxlvl1'=>$otherDeviceValue['ecpm'],
                    'revenue_cmsSharelvl1'=>$otherDeviceValue['rev'],
                    'innerdatalvl1'=>array_values($otherDeviceValue['innerdatalvl1']),
                    'expanded'=>false);
               
               }
              
              
              
            }
            
      $response_array['geo_table_data_lvl1']=array_values($dataarray_array['geo_table_data_lvl1']);    
 
   /***level 3***/
        $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
     
foreach($response_array['geo_table_data_lvl1'] as $lvl3){
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

      /***level 3***/
   $response_array['finaldata']=array_merge($response_array['level2'],$response_array['level3']);
 
$response_array['level3_dates']['dates'] = $date;
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
        'clicksinner'=> @$array_data[$date_value]['clicksinner'],
        'ctrinner'=> number_format(@$array_data[$date_value]['ctrinner'],1),
        'ecpmxinner'=> number_format(@$array_data[$date_value]['ecpmxinner'],2),
        'revenue_cmsShareinner'=> round(@$array_data[$date_value]['revenue_cmsShareinner'],2)
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
        'clicksinner'=> 0,
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
        'fillrateinnerlvl1'=> $array_data[$date_value]['adrinnerlvl1'] > 0 ?number_format(@$array_data[$date_value]['adimrinnerlvl1']/@$array_data[$date_value]['adrinnerlvl1']*100,1):0,
        'covginnerlvl1'=> number_format(@$array_data[$date_value]['covginnerlvl1'],1),
        'clicksinnerlvl1'=> @$array_data[$date_value]['clicksinnerlvl1'],
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