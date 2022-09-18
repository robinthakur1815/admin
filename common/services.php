<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK"); 
#Time Zone
//date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Common.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$common = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));

$result_fun = $common->services();
$stmt_result = $result_fun->get_result();
$row = $stmt_result->fetch_all(MYSQLI_ASSOC);
$rows = $stmt_result->num_rows;
	if($rows > 0){
	         $tree = categoriesToTree($row);     
          
		   #set response code - 200 ok
	        http_response_code(200); 
           # JSON-encode the response
			$json_response = json_encode(array("data"=>$tree,"status_code"=>200));

			# Return the response
			echo $json_response;
		}else{
         	#set response code - 404 Not found
	        http_response_code(404);
	  
	        #tell the user
	        echo json_encode(array("message" => "No Data Found!","status_code"=>404));
         }

 function categoriesToTree(&$categories) {

    $map = array(
        0 => array('subcategories' => array())
    );

    foreach ($categories as &$category) {
        $category['subcategories'] = array();
        $map[$category['serv_id']] = &$category;
    }

    foreach ($categories as &$category) {
        $map[$category['parent_id']]['subcategories'][] = &$category;
    }

    return $map[0]['subcategories'];

}        
         
?>