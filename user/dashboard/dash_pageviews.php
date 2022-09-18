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
include_once '../../objects/DashboardPub.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$dashAdtype = new DashboardPub($db,$dbMongoDb);
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
     $header->pub_uniq_id =$data->uniq_id;   
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
     
     $dashAdtype->range = $data->range;
     $dashAdtype->strtdate = $data->strtdate;
     $dashAdtype->enddate = $data->enddate;
     $dashAdtype->child_net_code = $data->child_net_code;
     $result_adReq = $dashAdtype->getDevice();
     $dashAdtype->uniq_id = $data->uniq_id;
     $result_adsrequest = $dashAdtype->getAdsAdreq();
     $result_adsreq = $result_adsrequest->toArray();
        
     if(!empty($result_adReq['Display']) || !empty($result_adReq['App']) || !empty($result_adReq['Video']) || !empty($result_adsreq)){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adReq,$result_adsreq);
         
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
    echo json_encode(array("message" => "Unable to get publisher dashboard. Data is incomplete.","status_code"=>400));
}

function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adtype,$result_adsreq){

  #mcm
   foreach ($result_adtype as $key => $rowdevices) {

      foreach($rowdevices as $rowdevice){

        $device = str_replace(".","_",$rowdevice['device']);
        
         @$sumuplevel_array[$device]['adimr'] += $rowdevice['adimr'];
         @$sumuplevel_array[$device]['adreq'] += $rowdevice['adr'];
         if($key == 'Display'){
                 
                @$sumuplevel_array[$device]['revenue_cmsShare']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShare),2);
             }
            if($key == 'App'){

                @$sumuplevel_array[$device]['revenue_cmsShare']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareApp),2);
                                
            }
            if($key == 'Video'){
                @$sumuplevel_array[$device]['revenue_cmsShare']+=round($rowdevice['revenue']-($rowdevice['revenue']*$cmsShareVid),2);
                
            }

      } //inner loop

    
} //loop end


#Adsense

foreach ($result_adsreq as $kyads => $rowdevicesAds) { 

          $device = str_replace(".","_",$rowdevicesAds->_id->device);
        
         @$sumuplevel_array[$device]['adimr'] += $rowdevicesAds->totalad_imp;
         @$sumuplevel_array[$device]['adreq'] += $rowdevicesAds->totalad_requests;
         @$sumuplevel_array[$device]['revenue_cmsShare']+=round($rowdevicesAds->total_earning,2);

} 


$arrAddevice = array("Desktop","High-end mobile devices","Tablets","Connected TV");
if(!empty($sumuplevel_array)){

 foreach($sumuplevel_array as $ky => $valSum){
    
    if($ky == "Desktop"){

       $adevice_arr[$ky]['adreq'] = $valSum['adreq'];
       $adevice_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adevice_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';  
         
    }
    if($ky == "High-end mobile devices"){
       $adevice_arr[$ky]['adreq'] = $valSum['adreq'];
       $adevice_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adevice_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';  
    }
    if($ky == "Tablets"){
       $adevice_arr[$ky]['adreq'] = $valSum['adreq'];
       $adevice_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adevice_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';   
    }
    if($ky == "Connected TV"){
       $adevice_arr[$ky]['adreq'] = $valSum['adreq'];
       $adevice_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adevice_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';   
    }
   
    $keyArr[] = $ky;
 }

}

foreach($arrAddevice as $v){
    
    if (!in_array($v, $keyArr)) 
    {
       $adevice_arr[$v]['adreq'] = 0;
       $adevice_arr[$v]['earnings'] = 0.00;
       $adevice_arr[$v]['cpm'] =0.00;
    }
}


  $request_arr = array("Desktop"=>$adevice_arr['Desktop'],"Tablet"=>$adevice_arr['Tablets'],"Mobile"=>$adevice_arr['High-end mobile devices'],"ConnectedTV"=>$adevice_arr['Connected TV']);
  
    return $request_arr;
  }  
  
?>