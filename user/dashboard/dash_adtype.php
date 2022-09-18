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
     $dashAdtype->range = $data->range;
     $dashAdtype->strtdate = $data->strtdate;
     $dashAdtype->enddate = $data->enddate;
     $dashAdtype->child_net_code = $data->child_net_code;
     $result_adtype = $dashAdtype->getAdtype();
        
     if(!empty($result_adtype['Display']) || !empty($result_adtype['App']) || !empty($result_adtype['Video'])){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adtype,$data->strtdate,$data->enddate);
         
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

function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adtype,$start,$end){


   foreach ($result_adtype as $key => $rowadtypes) {

      foreach($rowadtypes as $rowadtype){

        $AdType= str_replace(")","",str_replace("(","",str_replace(" ","",$rowadtype['ad_type'])));
        
         @$sumuplevel_array[$AdType]['adimr'] += $rowadtype['adimr'];
         if($key == 'Display'){
                 
                @$sumuplevel_array[$AdType]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
             }
            if($key == 'App'){

                @$sumuplevel_array[$AdType]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShareApp),2);
                                
            }
            if($key == 'Video'){
                @$sumuplevel_array[$AdType]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShareVid),2);
                
            }

      } //inner loop

    
} //loop end

$arrAdtype = array("Richmedia","Image","Text","Video","Animatedimage","Unmatchedadrequests");
if(!empty($sumuplevel_array)){

 foreach($sumuplevel_array as $ky => $valSum){
    
    if($ky == "Richmedia"){

       $adtype_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adtype_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';  
         
    }
    if($ky == "Image"){

       $adtype_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adtype_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';  
    }
    if($ky == "Text"){

       $adtype_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adtype_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';   
    }
    if($ky == "Video"){

       $adtype_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adtype_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';   
    }
    if($ky == "Animatedimage"){

       $adtype_arr[$ky]['earnings'] = $valSum['revenue_cmsShare'];
       $adtype_arr[$ky]['cpm'] = $valSum['adimr'] > 0 ? number_format(floor(($valSum['revenue_cmsShare']/$valSum['adimr'] *1000)*100)/100,2):'0.00';   
    }
    if($ky == "Unmatchedadrequests" || $ky == "Native:AppInstallAd" || $ky == "Native:ContentAd"){

       $adtype_arr['Unmatchedadrequests']['earnings'] += $valSum['revenue_cmsShare'];
       @$revenue_new +=$valSum['revenue_cmsShare'];
       @$adimr_new +=$valSum['adimr'];
       $adtype_arr['Unmatchedadrequests']['cpm'] = $adimr_new > 0 ? number_format(floor(($revenue_new/$adimr_new *1000)*100)/100,2):'0.00';
     $ky = "Unmatchedadrequests";     
    }
    $keyArr[] = $ky;
 }

}

foreach($arrAdtype as $v){
    
    if (!in_array($v, $keyArr)) 
    {
       $adtype_arr[$v]['earnings'] = 0.00;
       $adtype_arr[$v]['cpm'] =0.00;
    }
}

  $request_arr = array("Richmedia"=>$adtype_arr['Richmedia'],"Image"=>$adtype_arr['Image'],"Text"=>$adtype_arr['Text'],"Video"=>$adtype_arr['Video'],"Animated"=>$adtype_arr['Animatedimage'],"Unmatched"=>$adtype_arr['Unmatchedadrequests']);
    return $request_arr;
  }  
  
?>