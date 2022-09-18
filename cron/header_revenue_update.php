<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Cron.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Cron($db,$dbMongoDb);

//display
$result = $header->thisMonth();
$resultLastMonth = $header->lastMonth();
//video
$resultvid = $header->thisMonthvid();
$resultLastMonthvid = $header->lastMonthvid();
//app
$resultapp = $header->thisMonthapp();
$resultLastMonthapp = $header->lastMonthapp();
//adsense
$resultAds = $header->adsThisMonth();
$resultAdsLast = $header->adsLastMonth();

         
?>