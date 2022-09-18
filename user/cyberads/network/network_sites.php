<?php
#Author BY SY
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
include_once '../../../objects/Network.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$network = new Network($db);
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
     $network->range = $data->range;
     $network->strtdate = $data->strtdate;
     $network->enddate = $data->enddate;
     $network->child_net_code = $data->child_net_code;
     $result_sites = $network->getSites();

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
    echo json_encode(array("message" => "Unable to get display sites. Data is incomplete.","status_code"=>400));
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
        
        $sites = $rowsite['site_name'];
        // $sites = str_replace(".","_",$rowsite['site_name']);
        // $dateindex = date('Y-m-j', strtotime($rowsite['date']));
        #Data merge for first slide by site wise
        @$sumuplevel_array[$sites]['sites']=$sites;
        @$sumuplevel_array[$sites]['adreq']+=$rowsite['adr'];
        @$sumuplevel_array[$sites]['adimpr']+=$rowsite['adimr'];
        @$sumuplevel_array[$sites]['madreq']+=$rowsite['madr'];
        @$sumuplevel_array[$sites]['fillrate'] = number_format($sumuplevel_array[$sites]['adimpr']/$sumuplevel_array[$sites]['adreq']*100,1);
        @$sumuplevel_array[$sites]['clicks']+=$rowsite['clicks'];
        @$sumuplevel_array[$sites]['covg'] = $sumuplevel_array[$sites]['madreq'] > 0 ? number_format(($sumuplevel_array[$sites]['madreq']*100)/$sumuplevel_array[$sites]['adreq'],1) :0.0;
        @$sumuplevel_array[$sites]['ctr'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format($sumuplevel_array[$sites]['clicks']/$sumuplevel_array[$sites]['adimpr']*100,1):0.0;
        @$sumuplevel_array[$sites]['revenue_cmsShare'] += number_format($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        @$sumuplevel_array[$sites]['ecpm'] = $sumuplevel_array[$sites]['adimpr'] > 0 ? number_format($sumuplevel_array[$sites]['revenue_cmsShare']/$sumuplevel_array[$sites]['adimpr']*1000,2) : 0.00;
        
        #Total row for first slide 
		@$total_array['sites']='Total';
        @$total_array['adreq']+=$rowsite['adr'];
        @$total_array['adimpr']+=$rowsite['adimr'];
        @$total_array['madreq']+=$rowsite['madr'];
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$rowsite['clicks'];
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.0;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.0;
        @$total_array['revenue_cmsShare']+=number_format($rowsite['revenue']-($rowsite['revenue']*$cmsShare),2);
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format($total_array['revenue_cmsShare']/$total_array['adimpr']*1000,2) : 0.00;

    
   }
    aasort($sumuplevel_array,'revenue_cmsShare');
    foreach ($sumuplevel_array as $ky => $value) {
		
		$response_array['sites_table_data'][] = $value;
	} 
	$response_array['sites_table_data'][] = $total_array;


	return $response_array;
         
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