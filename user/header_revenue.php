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
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#for number format
ini_set('serialize_precision', 10);
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Common.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->child_net_code)
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    	#set token property values 
       // $header->child_net_code = $data->child_net_code;
       // $header->old_acc_name = $data->old_acc_name;
       // $header->new_acc_name = $data->new_acc_name;
       //$header->ads_id = $data->ads_id;
       $result_header = $header->headerRevenue();
       $result_head = $result_header->get_result();
       $rowHead = $result_head->fetch_array(MYSQLI_ASSOC);

       $rowsHead = $result_head->num_rows;
         if($rowsHead > 0){
            if($rowHead['this_month'] >= 10000){

             $valueThis_month = thousand_format($rowHead['this_month']);
           }else{
             $valueThis_month = number_format($rowHead['this_month'],2);
           }

           if($rowHead['previous_month'] >= 10000){

             $valueLast_month = thousand_format($rowHead['previous_month']);
           }else{
             $valueLast_month = number_format($rowHead['previous_month'],2);
           }
           
           $request_array=array(
				
				'this_month'=>$valueThis_month,
				'last_month'=>$valueLast_month,
				'message'=>"success"
				);
            # JSON-encode the response
			$json_response = json_encode(array("data"=>$request_array,"status_code"=>200));

			# Return the response
			echo $json_response;

         }else{
         	#set response code - 204 No content
	        http_response_code(204);
	  
	        #tell the user
	        echo json_encode(array("message" => "No Data Found!","status_code"=>204));
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
    echo json_encode(array("message" => "Unable to get header. Data is incomplete.","status_code"=>400));
}
function thousand_format($number) {
    $number = (float) $number;
    if ($number >= 10000) {
        $rn = round($number);
        $format_number = number_format($rn);
        $ar_nbr = explode(',', $format_number);
        $x_parts = array('K', 'M', 'B', 'T', 'Q');
        $x_count_parts = count($ar_nbr) - 1;
        $dn = $ar_nbr[0] . ((int) $ar_nbr[1][0] !== 0 ? '.' . $ar_nbr[1][0] : '');
        $dn .= $x_parts[$x_count_parts - 1];

        return $dn;
    }
    return $number;
}
?>