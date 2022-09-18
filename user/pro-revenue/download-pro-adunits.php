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
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/ProRevenue.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$pro = new ProRevenue($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->strtdate) &&
    !empty($data->enddate)
    // !empty($data->acc_name) &&
    // !empty($data->new_acc_name)
    
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

     #set overview property values
     $pro->uniq_id = $data->uniq_id;
     $pro->strtdate = $data->strtdate;
     $pro->enddate = $data->enddate;
     $result_ad = $pro->getAdunits();
     
     $result_adunit = $result_ad->toArray(); 
      
     
    if(!empty($result_adunit)){
           
        #calculation
        $data = prepareData($result_adunit,$cmsShare,$data->uniq_id);
        
          
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
    echo json_encode(array("message" => "Unable to get display overview. Data is incomplete.","status_code"=>400));
}

function prepareData($result_adunit,$cmsShare,$uniqid){
   
  
foreach ($result_adunit as $val) {

         $device = $val->_id->category;
        @$sumuplevel_array[$device]['adunits']=$device;
        @$sumuplevel_array[$device]['tot_lineitmlvl_imp']+=$val->totalline_lvl_imp;
        @$sumuplevel_arrayrev[$device]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);

        @$sumuplevel_array[$device]['tot_lineitmlvl_cpm'] = $sumuplevel_array[$device]['tot_lineitmlvl_imp'] > 0 ? number_format($sumuplevel_arrayrev[$device]['tot_lineitmlvl_rev']/$sumuplevel_array[$device]['tot_lineitmlvl_imp']*1000,2) : 0.00;

        @$sumuplevel_array[$device]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);


         #Total row for first slide 
        @$total_array['totalline_lvl_imp']+=$val->totalline_lvl_imp;
        @$total_array['revenue_cmsShare']+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);
        @$total_array['ecpm'] = $total_array['totalline_lvl_imp'] > 0 ? number_format($total_array['revenue_cmsShare']/$total_array['totalline_lvl_imp']*1000,2) : 0.00;

      
    $dateindex = $val->_id->date;    
        #first table page inner
    @$datalevel1inner[$device][$dateindex]['dateinner']= date('d M, Y', strtotime($val->_id->date));
   
    @$datalevel1inner[$device][$dateindex]['adimrinner']+=$val->totalline_lvl_imp;

    @$revenue1inner[$device][$dateindex]['revenue_cmsShareinner']+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);

    @$datalevel1inner[$device][$dateindex]['ecpmxinner'] = $datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format($revenue1inner[$device][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*1000,2) : 0.00;
    @$datalevel1inner[$device][$dateindex]['revenue_cmsShareinner']+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);  
        
    }
       #Date reverse date wise
    foreach($datalevel1inner as $key=>$value1)
        {
            foreach($value1 as $innervalue1)
            {
            $req_array['pro_innertable_data'][$key][]= $innervalue1;    
        }
      }
    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = $req_array['pro_innertable_data'][$ky];
      }
    

$filename = "CyberAds_Pro_Report_Adunits_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)    {
    array_pop($value);
    
    $header=array("--Ad units--","--Total Imp.--","--Estimated CPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    fputcsv($fp, $value);
    
    fputcsv($fp,$blank);
    $header=array("--Date--","--Total Imp.--","--Estimated CPM--","--Estimated Earnings--");
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


?>