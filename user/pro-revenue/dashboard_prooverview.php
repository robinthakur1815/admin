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
     $result_h = $pro->getOverview();
     
     $result_hb = $result_h->toArray(); 
     #$result_deal = $result_h[1]->toArray(); 
     
    if(!empty($result_hb)){
           
          #calculation
          $data = prepareData($result_hb,$cmsShare,$data->strtdate,$data->enddate);
          
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

function prepareData($result_hb,$cmsShare,$start,$end){
	#Date Array    
	while (strtotime($end) >= strtotime($start)){
  
     $date_array[]=$start;
     $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
	}    
	foreach ($result_hb as $val) {
	
       
		$request_array1['hbd_graph_data']['date'][]=date('j M', strtotime($val->_id->date)); 
        $revarrayhbd[date('j M', strtotime($val->_id->date))] =round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2);
        
		
        
    }
	
foreach($date_array as $date_value){
       
        $date_value=date('j M', strtotime($date_value));
        if (in_array($date_value, $request_array1['hbd_graph_data']['date'])){
			$final_sum_array['hbd_graph_data'][$date_value]=$revarrayhbd[$date_value];
		}else{
			$final_sum_array['hbd_graph_data'][$date_value]=0;
		}
    }
    
	foreach($final_sum_array['hbd_graph_data'] as $keyhbd=>$valueahbd){
		$request_array['hbd_graph_data']['date'][]=$keyhbd;
		$request_array['hbd_graph_data']['tot_revenue'][]=$valueahbd;
    }
	
	return $request_array; 
}/***calculation function end*****/

?>