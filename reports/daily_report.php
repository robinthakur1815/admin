<?php
#Author BY SS
#error reporting
error_reporting(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
#required headers
// header("Access-Control-Allow-Origin: *");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// header("HTTP/1.1 200 OK");
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Common.php';
include_once '../objects/Report.php';
include_once '../config/connection.php';
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
	  $topAdx = new Report($db,$dbMongoDb);
	  $result_fun = $topAdx->dailyreport();
?>