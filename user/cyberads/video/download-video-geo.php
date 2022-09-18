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
     $result_geo = $video->getGeo();

     if(!empty($result_geo)){
        #calculation
        $data = prepareData($cmsShare,$result_geo,$data->uniq_id,$data->strtdate,$data->enddate);
         
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
function prepareData($cmsShare,$result_geo,$uniqid,$start,$end){
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
        @$sumuplevel_arraymad[$geo]['madreq']+=$rowgeo['madr'];
        @$sumuplevel_array[$geo]['fillrate'] = number_format($sumuplevel_array[$geo]['adimpr']/$sumuplevel_array[$geo]['adreq']*100,1);
        @$click_array[$geo]['clicks']+=$rowgeo['clicks'];
        @$sumuplevel_array[$geo]['covg'] = $sumuplevel_arraymad[$geo]['madreq'] > 0 ? number_format(($sumuplevel_arraymad[$geo]['madreq']*100)/$sumuplevel_array[$geo]['adreq'],1) :0.00;
        @$sumuplevel_array[$geo]['ctr'] = $sumuplevel_array[$geo]['adimpr'] > 0 ? number_format($click_array[$geo]['clicks']/$sumuplevel_array[$geo]['adimpr']*100,1):0.00;
        @$revenue_array[$geo]['revenue_cmsShare'] += round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);
        @$sumuplevel_array[$geo]['ecpm'] = $sumuplevel_array[$geo]['adimpr'] > 0 ? number_format(floor(($revenue_array[$geo]['revenue_cmsShare']/$sumuplevel_array[$geo]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$geo]['revenue_cmsShare'] = $revenue_array[$geo]['revenue_cmsShare'];
        
        

    #first table page inner
    @$datalevel1inner[$geo][$dateindex]['dateinner']= date('j M, Y', strtotime($rowgeo['date']));
    @$datalevel1inner[$geo][$dateindex]['adrinner']+=$rowgeo['adr'];
    @$datalevel1inner[$geo][$dateindex]['adimrinner']+=$rowgeo['adimr'];
    @$datalevel1innermad[$geo][$dateindex]['madrinner']+=$rowgeo['madr'];
    @$datalevel1inner[$geo][$dateindex]['fillrate'] = number_format($datalevel1inner[$geo][$dateindex]['adimrinner']/$datalevel1inner[$geo][$dateindex]['adrinner']*100,1);
    @$clicklevel1inner[$geo][$dateindex]['clicksinner']+=$rowgeo['clicks'];
    @$datalevel1inner[$geo][$dateindex]['covginner'] = $datalevel1innermad[$geo][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1innermad[$geo][$dateindex]['madrinner']*100)/$datalevel1inner[$geo][$dateindex]['adrinner'],1) :0.00;
    @$datalevel1inner[$geo][$dateindex]['ctrinner']+=$datalevel1inner[$geo][$dateindex]['adimrinner'] > 0 ? number_format($clicklevel1inner[$geo][$dateindex]['clicksinner']/$datalevel1inner[$geo][$dateindex]['adimrinner']*100,1):0.00;
    @$revenuelevel1inner[$geo][$dateindex]['revenue_cmsShareinner']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);  
    @$datalevel1inner[$geo][$dateindex]['ecpmxinner'] =$datalevel1inner[$geo][$dateindex]['adimrinner'] > 0 ? number_format(floor(($revenuelevel1inner[$geo][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$geo][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;
    @$datalevel1inner[$geo][$dateindex]['revenue_cmsShareinner'] = $revenuelevel1inner[$geo][$dateindex]['revenue_cmsShareinner'];  

    #Level 1 table data
    #repname means dfp ad unit
    $repname= $rowgeo['rep_name'];
  

    #level 1 outer
    @$sumuplevel2_array[$geo][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$geo][$repname]['adrlvl1']+=$rowgeo['adr'];
    @$sumuplevel2_array[$geo][$repname]['adimrlvl1']+=$rowgeo['adimr'];
    @$sumuplevel2_array[$geo][$repname]['madrlvl1']+=$rowgeo['madr'];
    @$sumuplevel2_array[$geo][$repname]['fillrate'] = number_format($sumuplevel2_array[$geo][$repname]['adimrlvl1']/$sumuplevel2_array[$geo][$repname]['adrlvl1']*100,1);
    @$sumuplevel2_array[$geo][$repname]['clickslvl1']+=$rowgeo['clicks'];
    @$sumuplevel2_array[$geo][$repname]['covglvl1'] = $sumuplevel2_array[$geo][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$geo][$repname]['madrlvl1']*100)/$sumuplevel2_array[$geo][$repname]['adrlvl1'],1) :0.00;    
    @$sumuplevel2_array[$geo][$repname]['ctrlvl1'] = $sumuplevel2_array[$geo][$repname]['adimrlvl1'] > 0 ? number_format($sumuplevel2_array[$geo][$repname]['clickslvl1']/$sumuplevel2_array[$geo][$repname]['adimrlvl1']*100,1):0.00;
    @$rev2_array[$geo][$repname]['revenue_cmsSharelvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$geo][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$geo][$repname]['adimrlvl1'] > 0 ? number_format(floor(($rev2_array[$geo][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$geo][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;
    @$sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']=$rev2_array[$geo][$repname]['revenue_cmsSharelvl1'];
    
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
         $coun_arr[] = $ky;
        }else{
         
                  @$otherAdreq += $value['adreq'];
                  @$otherAdimp += $value['adimpr'];
                  @$otherMaAd += $value['madreq'];
                  @$otherFillrate = number_format($otherAdimp/$otherAdreq*100,1);
                  @$otherClicks += $value['clicks'];
                  @$otherCovg = $otherMaAd > 0 ? number_format(($otherMaAd*100)/$otherAdreq,1) :0.0;
                  @$otherCtr =  $otherAdimp > 0 ? number_format(($otherClicks)/$otherAdimp*100,1) :0.0;
                  @$otherRev += number_format($value['revenue_cmsShare'],2);
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
                            @$finalinnerdata_madr[$finalindex]['madrinner']+=$level2values['madrinner'];

                            @$finalinnerdatalevel1[$finalindex]['fillrate'] = $finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ?number_format($finalinnerdatalevel1[$finalindex]['adimrinner']/$finalinnerdatalevel1[$finalindex]['adrinner']*100,1) :0.0;

                            @$finalinnerdatalevel1[$finalindex]['covginner'] = $finalinnerdata_madr[$finalindex]['madrinner'] > 0 ? number_format(($finalinnerdata_madr[$finalindex]['madrinner']*100)/$finalinnerdatalevel1[$finalindex]['adrinner'],1) :0.0;

                            @$finalinnerdata_clicks[$finalindex]['clicksinner']+=$level2values['clicksinner'];

                            @$finalinnerdatalevel1[$finalindex]['ctr'] = $finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ? number_format(($finalinnerdata_clicks[$finalindex]['clicksinner'])/$finalinnerdatalevel1[$finalindex]['adimrinner']*100,1) :0.0;

                            @$finalinnerdata_rev[$finalindex]['revenue_cmsShareinner']+=number_format($level2values['revenue_cmsShareinner'],2);
                            @$finalinnerdatalevel1[$finalindex]['ecpmxinner'] =$finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ? number_format(floor((($finalinnerdata_rev[$finalindex]['revenue_cmsShareinner'])/$finalinnerdatalevel1[$finalindex]['adimrinner']*1000)*100)/100, 2) :0.00;
                            @$finalinnerdatalevel1[$finalindex]['revenue_cmsShareinner']+=number_format($level2values['revenue_cmsShareinner'],2);


                            
                        }
                    }

                   
                  $other_arr = array(
                    'geo'=>"Other",
                    'adreq'=>$otherAdreq,
                    'adimpr'=>$otherAdimp,
                    'fillrate'=>$otherFillrate,
                    'covg'=>$otherCovg,
                    'ctr'=>$otherCtr,
                    'ecpm'=>$otherEcpm,
                    'revenue_cmsShare'=>$otherRev,
                    'innerdata'=>array_values($finalinnerdatalevel1));
        }
        $count++;
      }
           if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $sumuplevel_newarr['Other'] = $other_arr;
              $other_arr = array();
            }
    #sorted revenue wise
     foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
        {
            //$sumOtherInner1 = array();
         if(in_array($keyfinal2,$coun_arr)){
            foreach($valuelvl1 as $value2)
            {
                $dataarray_array['geo_table_data_lvl1'][$keyfinal2][]=array(
                    'level1value'=>$keyfinal2,
                    'rep_namelvl1'=>$value2['rep_namelvl1'],
                    'adrlvl1'=>$value2['adrlvl1'],
                    'adimrlvl1'=>$value2['adimrlvl1'],
                    'fillratelvl1'=> number_format($value2['adimrlvl1']/$value2['adrlvl1']*100,1),
                    'covglvl1'=>number_format($value2['covglvl1'],1),
                    'ctrlvl1'=>number_format($value2['ctrlvl1'],1),
                    'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
                    'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2));
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

                  @$otherArr[$valOther['rep_namelvl1']]['rev'] += number_format($valOther['revenue_cmsSharelvl1'],2);
                  @$otherArr[$valOther['rep_namelvl1']]['ecpm'] = $otherArr[$valOther['rep_namelvl1']]['imp'] > 0 ? number_format(floor((($otherArr[$valOther['rep_namelvl1']]['rev'])/$otherArr[$valOther['rep_namelvl1']]['imp']*1000)*100)/100, 2) :0.00;
                  
                  

                 }  //loop end

            }
        

        }
     
         
      if(!empty($otherArr)){
         foreach($otherArr as $dvKey => $otherDeviceValue){
                  $dataarray_array['geo_table_data_lvl1']['Other'][] = array(
                    'level1value'=>"Other",
                    'rep_namelvl1'=>$dvKey,
                    'adrlvl1'=>$otherDeviceValue['adr'],
                    'adimrlvl1'=>$otherDeviceValue['imp'],
                    'fillratelvl1'=>$otherDeviceValue['fillrate'],
                    'covglvl1'=>$otherDeviceValue['covg'],
                    'ctrlvl1'=>$otherDeviceValue['ctr'],
                    'ecpmxlvl1'=>$otherDeviceValue['ecpm'],
                    'revenue_cmsSharelvl1'=>$otherDeviceValue['rev']);
               
               }
              
              
              
            }
 

$filename = "Auxo_Network_Report_Video_Geo_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_newarr as $row=>$value)   {
        array_pop($value);
        
        $header=array("--Country--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
        fputcsv($fp,$header);

        fputcsv($fp, $value);
        
        fputcsv($fp,$blank);
        $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
        fputcsv($fp,$header);
            
        foreach($sumuplevel_newarr[$row]['innerdata'] as $row2)   {

            fputcsv($fp, $row2);
        }
        
        fputcsv($fp,$blank);
        
         $header=array("--Country--","--Device--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
            fputcsv($fp,$header);
            
            foreach($dataarray_array['geo_table_data_lvl1'][$row] as $row3){
                
                
                fputcsv($fp, $row3);
            }
                fputcsv($fp,$blank);
                fputcsv($fp,$blank);
                
           
    }
    //Close the file handle.
    fclose($fp);
return $filename;
         
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