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
include_once '../../../objects/App.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$app = new App($db);
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
     if(!empty($rowShare)){if($rowShare['pub_app_share'] !=0){$cmsShare = $rowShare['pub_app_share']/100;}else{$cmsShare = 15/100;}}else{$cmsShare = 15/100;} 
     
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
     $app->range = $data->range;
     $app->strtdate = $data->strtdate;
     $app->enddate = $data->enddate;
     $app->child_net_code = $data->child_net_code;
     $result_device = $app->getDevice();

     if(!empty($result_device)){
        #calculation
        $result_filename = prepareData($cmsShare,$result_device,$data->uniq_id);
         
        #set response code - 200 ok
        http_response_code(200);
        
        #tell the user
        echo json_encode(array("data"=>$result_filename,"status_code"=>200));
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
function prepareData($cmsShare,$result_device,$uniqid){
    
    foreach ($result_device as $key => $rowdevice) {

        if(strtolower($rowdevice['device'])=='high-end mobile devices') {
          $rowdevice['device'] = str_replace($rowdevice['device'],"Mobile",$rowdevice['device']);
         }
        $device = str_replace(".","_",$rowdevice['device']);
        $dateindex = $rowdevice['date'];
        #Data merge for first slide by site wise
        @$sumuplevel_array[$device]['device']=$device;
        @$sumuplevel_array[$device]['adreq']+=$rowdevice['adr'];
        @$sumuplevel_array[$device]['adimpr']+=$rowdevice['adimr'];
        @$sumuplevel_arraymad[$device]['madreq']+=$rowdevice['madr'];
        @$sumuplevel_array[$device]['fillrate'] = number_format($sumuplevel_array[$device]['adimpr']/$sumuplevel_array[$device]['adreq']*100,1);
        @$click_array[$device]['clicks']+=$rowdevice['clicks'];
        @$sumuplevel_array[$device]['covg'] = $sumuplevel_arraymad[$device]['madreq'] > 0 ? number_format(($sumuplevel_arraymad[$device]['madreq']*100)/$sumuplevel_array[$device]['adreq'],1) :0.00;
        @$sumuplevel_array[$device]['ctr'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format($click_array[$device]['clicks']/$sumuplevel_array[$device]['adimpr']*100,1):0.00;
        @$revenue_array[$device]['revenue_cmsShare'] += round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
        @$sumuplevel_array[$device]['ecpm'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format(floor(($revenue_array[$device]['revenue_cmsShare']/$sumuplevel_array[$device]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$device]['revenue_cmsShare'] = $revenue_array[$device]['revenue_cmsShare'];
       
         
    #first table page inner
    @$datalevel1inner[$device][$dateindex]['dateinner']= date('j M, Y', strtotime($rowdevice['date']));
    @$datalevel1inner[$device][$dateindex]['adrinner']+=$rowdevice['adr'];
    @$datalevel1inner[$device][$dateindex]['adimrinner']+=$rowdevice['adimr'];
    @$datalevel1innermad[$device][$dateindex]['madrinner']+=$rowdevice['madr'];
    @$datalevel1inner[$device][$dateindex]['fillrate'] = number_format($datalevel1inner[$device][$dateindex]['adimrinner']/$datalevel1inner[$device][$dateindex]['adrinner']*100,1);
    @$clicklevel1inner[$device][$dateindex]['clicksinner']+=$rowdevice['clicks'];
    @$datalevel1inner[$device][$dateindex]['covginner'] = $datalevel1innermad[$device][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1innermad[$device][$dateindex]['madrinner']*100)/$datalevel1inner[$device][$dateindex]['adrinner'],1) :0.00;
    @$datalevel1inner[$device][$dateindex]['ctrinner']+=$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format($clicklevel1inner[$device][$dateindex]['clicksinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*100,1):0.00;
     @$revenuelevel1inner[$device][$dateindex]['revenue_cmsShareinner']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2); 
    @$datalevel1inner[$device][$dateindex]['ecpmxinner'] =$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format(floor(($revenuelevel1inner[$device][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;
    @$datalevel1inner[$device][$dateindex]['revenue_cmsShareinner']=$revenuelevel1inner[$device][$dateindex]['revenue_cmsShareinner'];


    #Level 1 table data
    #repname means dfp ad unit
    $repname= $rowdevice['rep_name'];
    
   

    #level 1 outer
    @$sumuplevel2_array[$device][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$device][$repname]['adrlvl1']+=$rowdevice['adr'];
    @$sumuplevel2_array[$device][$repname]['adimrlvl1']+=$rowdevice['adimr'];
    @$sumuplevel2_arraymad[$device][$repname]['madrlvl1']+=$rowdevice['madr'];
    @$sumuplevel2_array[$device][$repname]['fillrate'] = number_format($sumuplevel2_array[$device][$repname]['adimrlvl1']/$sumuplevel2_array[$device][$repname]['adrlvl1']*100,1);
    @$click2_array[$device][$repname]['clickslvl1']+=$rowdevice['clicks'];
    @$sumuplevel2_array[$device][$repname]['covglvl1'] = $sumuplevel2_arraymad[$device][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_arraymad[$device][$repname]['madrlvl1']*100)/$sumuplevel2_array[$device][$repname]['adrlvl1'],1) :0.00;    
    @$sumuplevel2_array[$device][$repname]['ctrlvl1'] = $sumuplevel2_array[$device][$repname]['adimrlvl1'] > 0 ? number_format($click2_array[$device][$repname]['clickslvl1']/$sumuplevel2_array[$device][$repname]['adimrlvl1']*100,1):0.00;
    @$rev2_array[$device][$repname]['revenue_cmsSharelvl1']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$device][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$device][$repname]['adimrlvl1'] > 0 ? number_format(floor(($rev2_array[$device][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$device][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;
    @$sumuplevel2_array[$device][$repname]['revenue_cmsSharelvl1']=$rev2_array[$device][$repname]['revenue_cmsSharelvl1'];
    
   }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    
    #Date reverse date wise
    foreach($datalevel1inner as $key=>$value1)
        {
           
           asort($value1);
            foreach(array_reverse($value1) as $innervalue1)
            {
            
            $request_array['device_innertable_data'][$key][]= $innervalue1;    
        }
      }
    
    
    #Merge innerdata of first page
   
    foreach ($sumuplevel_array as $ky => $value) {
        $sumuplevel_array[$ky]['innerdata'] = $request_array['device_innertable_data'][$ky];
      }

   
  
  
    
    #sorted revenue wise
    foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
        {
            
            foreach(array_reverse($valuelvl1) as $value2)
            {
        $dataarray_array['device_table_data_lvl1'][$keyfinal2][]=array(
            'level1value'=>$keyfinal2,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'adrlvl1'=>$value2['adrlvl1'],
            'adimrlvl1'=>$value2['adimrlvl1'],
            //'madrlvl1'=>$value2['madrlvl1'],
            'fillratelvl1'=>number_format($value2['fillrate'],2),
            'covglvl1'=>number_format($value2['covglvl1'],2),
            'ctrlvl1'=>number_format($value2['ctrlvl1'],2),
            'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
            'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2)
              );
            }
            
        }
   
        


$filename = "Auxo_Network_Report_App_Device_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)  {
    array_pop($value);
    
    $header=array("--Device Types--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    fputcsv($fp, $value);
    
    fputcsv($fp,$blank);
    $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);
        
    foreach($sumuplevel_array[$row]['innerdata'] as $row2)  {

        fputcsv($fp, $row2);
    }
    
    fputcsv($fp,$blank);
    $header=array("--Devices--","--Ad Units--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    foreach($dataarray_array['device_table_data_lvl1'][$row] as $row3){
        
    fputcsv($fp, $row3);
    }
        fputcsv($fp,$blank);
        fputcsv($fp,$blank);
        
}

//Close the file handle.
fclose($fp);
 return $filename;    
     
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
?>