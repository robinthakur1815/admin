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
     $result_sites = $display->getSites();

     if(!empty($result_sites)){
        #calculation
        $data = prepareData($cmsShare,$result_sites,$data->uniq_id);
         
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
    echo json_encode(array("message" => "Unable to get display sites. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_sites,$uniqid){
    
    foreach ($result_sites as $key => $rowsite) {
        
        $sites = str_replace(".","_",$rowsite['site_name']);
        $dateindex = $rowsite['date'];
        #Data merge for first slide by site wise
        @$sumuplevel_array[$sites]['sites']=$sites;
        @$sumuplevel_array[$sites]['adreq']+=$rowsite['adr'];
        @$sumuplevel_array[$sites]['adimpr']+=$rowsite['adimr'];
        @$sumuplevel_arraymad[$sites]['madreq']+=$rowsite['madr'];
        @$sumuplevel_array[$sites]['fillrate'] = number_format($sumuplevel_array[$sites]['adimpr']/$sumuplevel_array[$sites]['adreq']*100,1);
        @$click_array[$sites]['clicks']+=$rowsite['clicks'];
        @$sumuplevel_array[$sites]['covg'] = $sumuplevel_arraymad[$sites]['madreq'] > 0 ? number_format(($sumuplevel_arraymad[$sites]['madreq']*100)/$sumuplevel_array[$sites]['adreq'],1) :0.0;
        @$sumuplevel_array[$sites]['ctr'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format($click_array[$sites]['clicks']/$sumuplevel_array[$sites]['adimpr']*100,1):0.0;
        @$revenue_array[$sites]['revenue_cmsShare'] += round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        @$sumuplevel_array[$sites]['ecpm'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format($revenue_array[$sites]['revenue_cmsShare']/$sumuplevel_array[$sites]['adimpr']*1000,2) : 0.00;
        @$sumuplevel_array[$sites]['revenue_cmsShare'] = $revenue_array[$sites]['revenue_cmsShare'];
        
     

    #first table page inner
    @$datalevel1inner[$sites][$dateindex]['dateinner']= date('j M, Y', strtotime($rowsite['date']));
    @$datalevel1inner[$sites][$dateindex]['adrinner']+=$rowsite['adr'];
    @$datalevel1inner[$sites][$dateindex]['adimrinner']+=$rowsite['adimr'];
    @$datalevel1innermad[$sites][$dateindex]['madrinner']+=$rowsite['madr'];
    @$datalevel1inner[$sites][$dateindex]['fillrate'] = number_format($datalevel1inner[$sites][$dateindex]['adimrinner']/$datalevel1inner[$sites][$dateindex]['adrinner']*100,1);

    @$clicklevel1inner[$sites][$dateindex]['clicksinner']+=$rowsite['clicks'];
    @$datalevel1inner[$sites][$dateindex]['covginner'] = $datalevel1innermad[$sites][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1innermad[$sites][$dateindex]['madrinner']*100)/$datalevel1inner[$sites][$dateindex]['adrinner'],1) :0.0;
    @$datalevel1inner[$sites][$dateindex]['ctrinner']+=$datalevel1inner[$sites][$dateindex]['adimrinner'] > 0 ? number_format($clicklevel1inner[$sites][$dateindex]['clicksinner']/$datalevel1inner[$sites][$dateindex]['adimrinner']*100,1):0.0;
    @$revenuelevel1inner[$sites][$dateindex]['revenue_cmsShareinner']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);  
    @$datalevel1inner[$sites][$dateindex]['ecpmxinner'] =$datalevel1inner[$sites][$dateindex]['adimrinner'] > 0 ? number_format($revenuelevel1inner[$sites][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$sites][$dateindex]['adimrinner']*1000,2) : 0.00;
    @$datalevel1inner[$sites][$dateindex]['revenue_cmsShareinner']=$revenuelevel1inner[$sites][$dateindex]['revenue_cmsShareinner'];  

    #Level 1 table data
    #repname means dfp ad unit
    $repname= $rowsite['rep_name'];
    #inner
    
    #level 1 outer
    @$sumuplevel2_array[$sites][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$sites][$repname]['adrlvl1']+=$rowsite['adr'];
    @$sumuplevel2_array[$sites][$repname]['adimrlvl1']+=$rowsite['adimr'];
    @$sumuplevel2_array[$sites][$repname]['madrlvl1']+=$rowsite['madr'];
    @$sumuplevel2_array[$sites][$repname]['fillrate'] = number_format($sumuplevel2_array[$sites][$repname]['adimrlvl1']/$sumuplevel2_array[$sites][$repname]['adrlvl1']*100,1);
    @$click2_array[$sites][$repname]['clickslvl1']+=$rowsite['clicks'];
    @$sumuplevel2_array[$sites][$repname]['covglvl1'] = $sumuplevel2_array[$sites][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$sites][$repname]['madrlvl1']*100)/$sumuplevel2_array[$sites][$repname]['adrlvl1'],1) :0.0;    
    @$sumuplevel2_array[$sites][$repname]['ctrlvl1'] = $sumuplevel2_array[$sites][$repname]['adimrlvl1'] > 0 ? number_format($click2_array[$sites][$repname]['clickslvl1']/$sumuplevel2_array[$sites][$repname]['adimrlvl1']*100,1):0.0;
    @$rev2_array[$sites][$repname]['revenue_cmsSharelvl1']+=round($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$sites][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$sites][$repname]['adimrlvl1'] > 0 ? number_format($rev2_array[$sites][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$sites][$repname]['adimrlvl1']*1000,2) : 0.00;
    @$sumuplevel2_array[$sites][$repname]['revenue_cmsSharelvl1']=$rev2_array[$sites][$repname]['revenue_cmsSharelvl1'];
    
   }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    #Date reverse date wise
    foreach($datalevel1inner as $key=>$value1)
        {
            #date sort
            // asort($value1);
            ksort($value1);
            foreach($value1 as $innervalue1)
            {
            $request_array['sites_innertable_data'][$key][]= $innervalue1;    
        }
      }

      
    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = $request_array['sites_innertable_data'][$ky];
      } 
    // foreach ($sumuplevel_array as $ky => $value) {
    //      $response_array['sites_table_data'][] = $value;
    //   } 
   
  
    #sorted revenue wise
    foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
        {
            usort($valuelvl1,'sortByRev');
            foreach(array_reverse($valuelvl1) as $value2)
            {
        $dataarray_array['sites_table_data_lvl1'][$keyfinal2][]=array(
            'level1value'=>$keyfinal2,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'adrlvl1'=>$value2['adrlvl1'],
            'adimrlvl1'=>$value2['adimrlvl1'],
            //'madrlvl1'=>$value2['madrlvl1'],
            'fillratelvl1'=>number_format($value2['fillrate'],1),
            'covglvl1'=>number_format($value2['covglvl1'],1),
            'ctrlvl1'=>number_format($value2['ctrlvl1'],1),
            'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
            'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2)
             );
            }
            
        }
      
        
$filename = "Auxo_Network_Report_Display_Sites_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)   {
    array_pop($value);
    
    $header=array("--Website--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    fputcsv($fp, $value);
    
    fputcsv($fp,$blank);
    $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);
        
    foreach($sumuplevel_array[$row]['innerdata'] as $row2)   {

        fputcsv($fp, $row2);
    }
    
    fputcsv($fp,$blank);
    $header=array("--Website--","--Ad Units--","--Ad Requests--","--Fill Rate--","--Matched Ad Request--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    foreach($dataarray_array['sites_table_data_lvl1'][$row] as $row3){
       fputcsv($fp, $row3);
    }
        fputcsv($fp,$blank);
        fputcsv($fp,$blank);
        
}
return $filename;
         
}/***calculation function end*****/
function sortByRev($a, $b)
{
    $a = $a['revenue_cmsSharelvl1'];
    $b = $b['revenue_cmsSharelvl1'];

    if ($a == $b) return 0;
    return ($a < $b) ? -1 : 1;
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
function get_datesort_array($finaldataarr)
{
    krsort($finaldataarr);
    foreach($finaldataarr as $finalkeys=> $finalvalues) {
        $finals[] =array(
            '0'=>$finalkeys,
            '1'=>$finalvalues
        );
    }
    return(array_values($finals));
}
?>