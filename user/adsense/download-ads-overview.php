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
     $result_adsen = $adsense->getOverview();
     
     $result_adsense = $result_adsen->toArray();  
     if(!empty($result_adsense)){
          
          #calculation
          $data = prepareData($result_adsense,$data->uniq_id,$data->strtdate,$data->enddate);
         
          
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
    echo json_encode(array("message" => "Unable to get adsense overview. Data is incomplete.","status_code"=>400));
}
function prepareData($result_adsense,$uniqid,$start,$end){
#Date Array    
while (strtotime($end) >= strtotime($start)) 
{
     $date_array[]=$end;
     $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
}
foreach ($result_adsense as $val) {

        $dateavail[]=$val->_id;

        $res_array['ads_table_data'][$val->_id] = array(
            'date'=>date('d M, Y', strtotime($val->_id)),
            'adr'=>number_format($val->totalad_requests),
            'adimr'=>number_format($val->totalad_imp),
            //'madr'=>number_format($val->totalmatchad_requests),
            'fillrate'=>number_format($val->totalad_imp/$val->totalad_requests*100,2),
            'covg'=>$val->totalmatchad_requests > 0 ? number_format(($val->totalmatchad_requests*100)/$val->totalad_requests,2) :'0.00%',
            'ctr'=>($val->totalad_imp > 0 ? number_format(($val->total_click)/($val->totalad_imp)*100,2).'%' : '0%'),
            'ecpmx'=>$val->totalad_imp > 0 ? number_format(floor(($val->total_earning/$val->totalad_imp*1000)*100)/100, 2) :0.00,
            'revenue_cmsShare'=>number_format($val->total_earning,2),);
            
        
         #Total row for first slide 
        @$total_array['adr']+=$val->totalad_requests;
        @$total_array['adimr']+=$val->totalad_imp;
        @$total_arraymadr['madr']+=$val->totalmatchad_requests;
        @$total_array['fillrate']=number_format($total_array['adimr']/$total_array['adr']*100,2);
        @$total_array['clicks']+=$val->total_click;
        @$total_array['covg'] = $total_arraymadr['madr'] > 0 ? number_format(($total_arraymadr['madr']*100)/$total_array['adr'],2) :0.00;
        @$total_array['ctr'] = $total_array['adimr'] > 0 ? number_format($total_array['clicks']/$total_array['adimr']*100,2):0.00;
        @$total_array_full['revenue_cmsShare'] += round($val->total_earning,2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimr']*1000)*100)/100, 2) : 0.00;

        
    }

#if any date data is missing in MongoDB
$result=array_diff($date_array, $dateavail);

foreach($result as $restarr)  {
        $res_array['ads_table_data'][$restarr]= array(
            'date'=>date('d M ,Y', strtotime($restarr)),
            'adr'=>0,
            'adimr'=>0,
            'madr'=>0,
            'fillrate'=>0,
            'covg'=>0,
            'ctr'=>0,
            'ecpmx'=>0,
            'revenue_cmsShare'=>0
        );
}
krsort($res_array['ads_table_data']);
$request_array['ads_table_data'] = array_values($res_array['ads_table_data']);

$request_array['ads_table_data'][] = array("date"=>"Total","adr"=>number_format($total_array['adr']),"adimr"=>number_format($total_array['adimr']),"fillrate"=>number_format($total_array['fillrate']),"covg"=>$total_array['covg'],"ctr"=>$total_array['ctr'],"ecpmx"=>$total_array['ecpm'],"revenue_cmsShare"=>$total_array['revenue_cmsShare']);

$filename = "Report_Overview_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

$header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
fputcsv($fp,$header);
//Loop through the array containing our CSV data.
foreach ($request_array['ads_table_data'] as $row) {
    //fputcsv formats the array into a CSV format.
    //It then writes the result to our output stream.
    fputcsv($fp, $row);
}


//Close the file handle.
fclose($fp);
    return $filename; 
}/***calculation function end*****/

?>