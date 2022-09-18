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
     $result_adunit = $display->getAdunits();

     if(!empty($result_adunit)){
        #calculation
        $data = prepareData($cmsShare,$result_adunit,$data->uniq_id);
         
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
    echo json_encode(array("message" => "Unable to get display ad unit. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_adunit,$uniqid){
    
    foreach ($result_adunit as $key => $rowadunit) {
        
        $unitName = $rowadunit['unit_name'];
        $dateindex= $rowadunit['date'];
        #Data merge for first slide by adunit wise
        @$sumuplevel_array[$unitName]['adunit']=$unitName;
        @$sumuplevel_array[$unitName]['adreq']+=$rowadunit['adr'];
        @$sumuplevel_array[$unitName]['adimpr']+=$rowadunit['adimr'];
        @$sumuplevel_arraymad[$unitName]['madreq']+=$rowadunit['madr'];
        @$sumuplevel_array[$unitName]['fillrate'] = number_format($sumuplevel_array[$unitName]['adimpr']/$sumuplevel_array[$unitName]['adreq']*100,1);
        @$click_array[$unitName]['clicks']+=$rowadunit['clicks'];
        @$sumuplevel_array[$unitName]['covg'] = $sumuplevel_arraymad[$unitName]['madreq'] > 0 ? number_format(($sumuplevel_arraymad[$unitName]['madreq']*100)/$sumuplevel_array[$unitName]['adreq'],1) :0.0;
        @$sumuplevel_array[$unitName]['ctr'] = $sumuplevel_array[$unitName]['adimpr'] > 0 ? number_format($click_array[$unitName]['clicks']/$sumuplevel_array[$unitName]['adimpr']*100,1):0.0;
        @$revenue_array[$unitName]['revenue_cmsShare'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);
        @$sumuplevel_array[$unitName]['ecpm'] = $sumuplevel_array[$unitName]['adimpr'] > 0 ? number_format($revenue_array[$unitName]['revenue_cmsShare']/$sumuplevel_array[$unitName]['adimpr']*1000,2) : 0.00;
        @$sumuplevel_array[$unitName]['revenue_cmsShare'] = $revenue_array[$unitName]['revenue_cmsShare'];
        

        #inner
    @$datalevel2inner[$unitName][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($rowadunit['date']));
    @$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1']+=$rowadunit['adr'];
    @$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']+=$rowadunit['adimr'];
    @$datalevel2innermad[$unitName][$dateindex]['madrinnerlvl1']+=$rowadunit['madr'];
    @$datalevel2inner[$unitName][$dateindex]['fillrate'] = number_format($datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1']*100,1);

    @$clicks_inner[$unitName][$dateindex]['clicksinnerlvl1']+=$rowadunit['clicks'];
    @$datalevel2inner[$unitName][$dateindex]['covginnerlvl1'] = $datalevel2innermad[$unitName][$dateindex]['madrinnerlvl1'] > 0 ? number_format(($datalevel2innermad[$unitName][$dateindex]['madrinnerlvl1']*100)/$datalevel2inner[$unitName][$dateindex]['adrinnerlvl1'],1) :0.0;
    @$datalevel2inner[$unitName][$dateindex]['ctr']+=$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($clicks_inner[$unitName][$dateindex]['clicksinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']*100,1):0.0;
    @$revenue2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1'] += round($rowadunit['revenue']-($rowadunit['revenue']*$cmsShare),2);  
    @$datalevel2inner[$unitName][$dateindex]['ecpmxinnerlvl1'] =$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($revenue2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$unitName][$dateindex]['adimrinnerlvl1']*1000,2) : 0.00;
    @$datalevel2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1']=$revenue2inner[$unitName][$dateindex]['revenue_cmsShareinnerlvl1'];  
    
   }
    #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    #Date reverse date wise
    foreach($datalevel2inner as $key=>$value1)
        {
            #date sort
            // asort($value1);
            ksort($value1);
            foreach($value1 as $innervalue1)
            {
            $req_array['adunit_innertable_data'][$key][]= $innervalue1;    
        }
      }
    
    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = $req_array['adunit_innertable_data'][$ky];
      }
   

$filename = "Auxo_Network_Report_Display_AdUnits_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)    {
    array_pop($value);
    
    $header=array("--Ad Unit Name--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
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