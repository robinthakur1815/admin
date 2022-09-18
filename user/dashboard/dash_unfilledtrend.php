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
     $result_unfilled = $dashAdtype->getAdtype();
     $dashAdtype->uniq_id = $data->uniq_id;
      $result_adsrequest = $dashAdtype->getAdsAdreq();
      $result_adsunfilled = $result_adsrequest->toArray();
        
     if(!empty($result_unfilled['Display']) || !empty($result_unfilled['App']) || !empty($result_unfilled['Video']) || !empty($result_adsunfilled)){
        #calculation
        $data = prepareData($result_unfilled,$result_adsunfilled,$data->strtdate,$data->enddate);
         
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

function prepareData($result_unfilled,$result_adsunfilled,$start,$end){

 #Date Array
        while (strtotime($start) <= strtotime($end))
        {
         
         $date_arr[] = date('Y-m-d', strtotime($start));
         $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
        }
  #mcm
   foreach ($result_unfilled as $key => $rowdevices) {

      foreach($rowdevices as $rowdevice){

        $date = $rowdevice['date'];
         @$sumuplevel_array1[$date]['date'] = $date;
         @$sumuplevel_array1[$date]['adimr'] += $rowdevice['adimr'];
         @$sumuplevel_array1[$date]['adreq'] += $rowdevice['adr'];
         
         

      } //inner loop

    
} //loop end


#Adsense

foreach ($result_adsunfilled as $kyads => $rowdevicesAds) { 

          $dateUn = $rowdevicesAds->_id->date;
        
         @$sumuplevel_array1[$dateUn]['date'] = $dateUn;
         @$sumuplevel_array1[$dateUn]['adimr'] += $rowdevicesAds->totalad_imp;
         @$sumuplevel_array1[$dateUn]['adreq'] += $rowdevicesAds->totalad_requests;
        

} 
$finalArr = get_sum_index($sumuplevel_array1,$date_arr); //zero insert if date data not in database 


foreach($finalArr as $value){

    $dat = strtotime($value['date']);
     $unfilled = 0;
     $unfilled = $value['adreq'] - $value['adimr']; //unfilled calculations
     @$unfilled_array[] = $unfilled > 0 ? round(($unfilled/$value['adreq'])*100,1):0.0;
     @$datearr[] = date('j M', $dat);
  
}



  $request_arr = array("unfilled_trend"=>$unfilled_array,"date"=>$datearr);
  
    return $request_arr;
  } 

function get_sum_index($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'date'=> @$array_data[$date_value]['date'],
        'adimr'=> @$array_data[$date_value]['adimr'],
        'adreq'=> @$array_data[$date_value]['adreq']
        );
    }
    else
    {
        $formatedarray[]=array(
        'date'=> date('Y-m-d', strtotime($date_value)),
        'adimr'=>0,
        'adreq'=> 0
        );
    }
}

    return $formatedarray;
}
?>