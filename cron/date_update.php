<?php
#Author BY SS
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
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Cron.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$country = new Cron($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));

$result = $country->updateCron();
$result1 = $country->updateCronadtype();
$result2 = $country->updateCronadunit();
$result3 = $country->updateCrondomain();
$result4 = $country->updateCrondevice();
$result5 = $country->updateCroncoun();
$result6 = $country->updateCronappoverview();
$result7 = $country->updateCronappadtype();
$result8 = $country->updateCronappunitwise();
$result9 = $country->updateCronappdomain();
$result10 = $country->updateCronappdevice();
$result11 = $country->updateCronappcoun();
$result12 = $country->updateCronvideoverview();
$result13 = $country->updateCronvideoadtype();
$result14 = $country->updateCronvideounitwise();
$result15 = $country->updateCronvideodomain();
$result16 = $country->updateCronvideodevice();
$result17 = $country->updateCronvideocoun();
if($result == 1 || $result1==1 || $result2==1 || $result3==1 || $result4==1 || $result5==1 || $result6==1 || $result7==1 || $result8==1 || $result9==1 || $result10==1 || $result11==1 || $result12==1 || $result13==1 || $result14==1 || $result15==1 || $result16==1 || $result17==1){
    #set response code - 200 ok
    http_response_code(200);
    echo json_encode(array("message" => "date was updated.","status_code"=>200));
}else{
    #set response code - 404 Not found
    http_response_code(404);
    echo json_encode(array("message" => "No Data Found!","status_code"=>404));
}
         
?>