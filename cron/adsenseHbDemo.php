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
$adsense = new Cron($db,$dbMongoDb);


$result = $adsense->adsenseDaywiseDemo();
if($result){
	$resultTypewise = $adsense->adsenseTypewiseDemo();
}
if($resultTypewise){
  $resultDomainwise = $adsense->adsenseDomainwiseDemo();
}
if($resultDomainwise){
 $resultDevicewise = $adsense->adsenseDevicewiseDemo();
}

$resultHb = $adsense->hbDemo();

if($resultHb){
 $resultHbGeo = $adsense->hbGeoDemo();
}
if($resultHbGeo){
 $resultDeal = $adsense->directDealDemo();
}
if($resultDeal){
 $resultDealGeo = $adsense->directDealGeoDemo();
}

