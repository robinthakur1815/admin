<?php
#Author BY ST
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
     #set overview property values
     if($data->range == "custom"){
        if($data->strtdate == '' && $data->enddate == ''){
           #set response code - 422 validation error
           http_response_code(422);
  
           #tell the user
          echo json_encode(array("message" => "Date range invalid!","status_code"=>422));
          exit();
        }
     }
     $app->range = $data->range;
     $app->strtdate = $data->strtdate;
     $app->enddate = $data->enddate;
     $app->child_net_code = $data->child_net_code;
     #$result_app = $app->getOverview();
     $result_app = $app->getAdtype();

     if(!empty($result_app)){
           
          #calculation
          $data = prepareData($cmsShare,$result_app,$data->uniq_id,$data->strtdate,$data->enddate);
         
          
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
    echo json_encode(array("message" => "Unable to get app overview. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_app,$uniqid,$start,$end){

foreach ($result_app as $key => $rowcontent) {
        $date =  $rowcontent['date'];
        @$sumuplevel_array[$date]['date'] = date('j M, Y', strtotime($rowcontent['date']));
        @$sumuplevel_array[$date]['adr']+=$rowcontent['adr'];
        @$sumuplevel_array[$date]['adimr']+=$rowcontent['adimr'];
        @$sumuplevel_array_down[$date]['madr']+=$rowcontent['madr'];
        @$sumuplevel_array_dev[$date]['clicks']+=$rowcontent['clicks'];
        @$sumuplevel_array[$date]['fillrate'] = number_format($sumuplevel_array[$date]['adimr']/$sumuplevel_array[$date]['adr']*100,1);
        @$sumuplevel_array[$date]['covg'] = $sumuplevel_array_down[$date]['madr'] > 0 ? number_format(($sumuplevel_array_down[$date]['madr']*100)/$sumuplevel_array[$date]['adr'],1) :0.0;
        @$sumuplevel_array[$date]['clicks_madr'] = $sumuplevel_array[$date]['adimr'] > 0 ? number_format($sumuplevel_array_dev[$date]['clicks']/$sumuplevel_array[$date]['adimr']*100,1):0.0;
        @$sumuplevel_array_rev[$date]['revenue_cmsShare'] += round($rowcontent['revenue']-($rowcontent['revenue']*$cmsShare),2);
        @$sumuplevel_array[$date]['ecpmx'] = $sumuplevel_array[$date]['adimr'] > 0 ? number_format(floor(($sumuplevel_array_rev[$date]['revenue_cmsShare']/$sumuplevel_array[$date]['adimr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$date]['revenue_cmsShare'] += round($rowcontent['revenue']-($rowcontent['revenue']*$cmsShare),2);
            
        
         #Total row for first slide 
        @$total_array['adr']+=$rowcontent['adr'];
        @$total_array['adimr']+=$rowcontent['adimr'];
        @$total_array_down['madr']+=$rowcontent['madr'];
        @$total_array_cli['clicks']+=$rowcontent['clicks'];
        @$total_array['fillrate'] = number_format($total_array['adimr']/$total_array['adr']*100,1);
        @$total_array['covg'] = $total_array_down['madr'] > 0 ? number_format(($total_array_down['madr']*100)/$total_array['adr'],2) :0.00;
        @$total_array['ctr'] = $total_array['adimr'] > 0 ? number_format($total_array_cli['clicks']/$total_array['adimr']*100,2):0.00;
        @$total_array_full['revenue_cmsShare'] += ($rowcontent['revenue']-($rowcontent['revenue']*$cmsShare));
        @$total_array['ecpm'] = $total_array['adimr'] > 0 ? number_format($total_array_full['revenue_cmsShare']/$total_array['adimr']*1000,2) : 0.00;
        @$total_array['revenue_cmsShare'] = $total_array_full['revenue_cmsShare'];
        
        
        $dateavail[]=$rowcontent['date'];
    }

    #if any date data is missing 
    $result=array_diff($date_array, $dateavail);

    foreach($result as $restarr)  {
            $sumuplevel_array[$restarr]= array(
                'date'=>date('j M ,Y', strtotime($restarr)),
                'adr'=>0,
                'adimr'=>0,
                'covg'=>0,
                'ctr'=>0,
                'ecpm'=>0,
                'revenue_cmsShare'=>0
            );
    }
    ksort($sumuplevel_array);
    $request_array['content_table_data'] = array_values($sumuplevel_array);

    $request_array['content_table_data'][] = array("date"=>"Total","adr"=>$total_array['adr'],"adimr"=>$total_array['adimr'],"fillrate"=>$total_array['fillrate'],"covg"=>$total_array['covg'],"ctr"=>$total_array['ctr'],"ecpm"=>$total_array['ecpm'],"revenue_cmsShare"=>round($total_array['revenue_cmsShare'],2));
    
    
    $filename = "Auxo_Network_Report_App_Overview_".$uniqid.".csv";
    $filepath = "./upload/".$filename;
    $fp = fopen($filepath,"w");
    $blank=array("\n","\n");
    
    $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Revenue--");
    fputcsv($fp,$header);
    foreach ($request_array['content_table_data'] as $row) {
        fputcsv($fp, $row);
    }
    // fputcsv($fp,$blank);
    // foreach($request_array['sum_table_data'] as $row1) {
        // fputcsv($fp, $row1);
    // }
    fclose($fp);
    return $filename;
}/***calculation function end*****/

?>