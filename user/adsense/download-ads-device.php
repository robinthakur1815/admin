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
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Adsense.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$adsense = new Adsense($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->strtdate) &&
    !empty($data->enddate) &&
    !empty($data->adsense_id)
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    
     #set overview property values
     $adsense->uniq_id = $data->uniq_id;
     //$adsense->uniq_id = "CHEE_120618_174513";   
     $adsense->strtdate = $data->strtdate;
     $adsense->enddate = $data->enddate;
     $result_dev = $adsense->getDevice();
     $result_device = $result_dev->toArray();
     if(!empty($result_device)){
        #calculation
        $data = prepareData($result_device,$data->strtdate,$data->uniq_id);
         
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
    echo json_encode(array("message" => "Unable to get adsense device. Data is incomplete.","status_code"=>400));
}
function prepareData($result_device,$uniqid){

   
    foreach ($result_device as $key => $val) {
        if(strtolower($val->_id->device)=='high-end mobile devices') {
        $val->_id->device = str_replace($val->_id->device,"Mobile",$val->_id->device);
    }
        $device = str_replace(".","_",$val->_id->device);
        $dateindex = $val->_id->date;
        #Data merge for first slide by site wise
        @$sumuplevel_array[$device]['device']=$device;
        @$sumuplevel_array[$device]['adreq']+=$val->totalad_requests;
        @$sumuplevel_array[$device]['adimpr']+=$val->totalad_imp;
        @$sumuplevel_madreq[$device]['madreq']+=$val->totalmatchad_requests;
        @$sumuplevel_array[$device]['fillrate'] = number_format($val->totalad_imp/$val->totalad_requests*100,2);
        @$click_array[$device]['clicks']+=$val->total_click;

        @$sumuplevel_array[$device]['covg'] = $sumuplevel_madreq[$device]['madreq'] > 0 ? number_format(($sumuplevel_madreq[$device]['madreq']*100)/$sumuplevel_array[$device]['adreq'],2) :0.00;

        @$sumuplevel_array[$device]['ctr'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format($click_array[$device]['clicks']/$sumuplevel_array[$device]['adimpr']*100,2):0.00;

        @$revenue_array[$device]['revenue_cmsShare'] += round($val->total_earning,2);
        @$sumuplevel_array[$device]['ecpm'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format(floor(($revenue_array[$device]['revenue_cmsShare']/$sumuplevel_array[$device]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$device]['revenue_cmsShare'] += round($val->total_earning,2);

       
   

    #first table page inner
    @$datalevel1inner[$device][$dateindex]['dateinner']= date('d M, Y', strtotime($val->_id->date));
    @$datalevel1inner[$device][$dateindex]['adrinner']+=$val->totalad_requests;
    @$datalevel1inner[$device][$dateindex]['adimrinner']+=$val->totalad_imp;
    @$datalevel1madreq[$device][$dateindex]['madrinner']+=$val->totalmatchad_requests;
    @$datalevel1inner[$device][$dateindex]['fillrate']=number_format($datalevel1inner[$device][$dateindex]['adimrinner']/$datalevel1inner[$device][$dateindex]['adrinner']*100,2);
    @$clicks_inner[$device][$dateindex]['clicksinner']+=$val->total_click;
    @$datalevel1inner[$device][$dateindex]['covginner'] = $datalevel1madreq[$device][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1madreq[$device][$dateindex]['madrinner']*100)/$datalevel1inner[$device][$dateindex]['adrinner'],2) :0.00;
    @$datalevel1inner[$device][$dateindex]['ctrinner']+=$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format($clicks_inner[$device][$dateindex]['clicksinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*100,2):0.00;
    @$revenue2inner[$device][$dateindex]['revenue_cmsShareinner']+=round($val->total_earning,2);  
    @$datalevel1inner[$device][$dateindex]['ecpmxinner'] =$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format(floor(($revenue2inner[$device][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;
    @$datalevel1inner[$device][$dateindex]['revenue_cmsShareinner']+=round($val->total_earning,2);  

   
    
   }
   
      #Date reverse date wise
    foreach($datalevel1inner as $key=>$value1)
        {
            krsort($value1);
           
            foreach($value1 as $innervalue1)
            {
            $req_array['sites_innertable_data'][$key][]= $innervalue1;    
        }
      }
     

   #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         
         $sumuplevel_array[$ky]['innerdata'] = $req_array['sites_innertable_data'][$ky];
      }   
$filename = "Report_Device_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)    {
    array_pop($value);
    
    $header=array("--Device--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    fputcsv($fp, $value);
    
    fputcsv($fp,$blank);
    $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);
        
    foreach($sumuplevel_array[$row]['innerdata'] as $row2)    {

        fputcsv($fp, $row2);
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