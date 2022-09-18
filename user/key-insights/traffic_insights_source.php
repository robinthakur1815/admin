<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
#date_default_timezone_set('Asia/Kolkata');
date_default_timezone_set("Pacific/Honolulu");
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Analytics.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$analytics = new Analytics($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];


#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->account_id) &&
    !empty($data->child_net_code)
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
   
    $startingdate = date("Y-m-d", strtotime('-8 day'));
    $endingdate = date("Y-m-d", strtotime('-2 day'));
    if(date('H') > 03) {
        $startingdate = date("Y-m-d",strtotime("-7 days"));
        $endingdate =  date("Y-m-d",strtotime("-1 days"));
    }
    else{
        $startingdate = date("Y-m-d",strtotime("-8 days"));
        $endingdate =  date("Y-m-d",strtotime("-2 days"));
    }
    $analytics->uniq_id = $data->uniq_id;
    $analytics->account_id = $data->account_id;
    $analytics->pstart_week = $startingdate;
    $analytics->pend_week = $endingdate;
    $result_week = $analytics->getTrafficSource(); 
    $result_traffic = $result_week->toArray();
/****Adx and Adsense revenue***/
     $analytics->strtdate = $startingdate;
     $analytics->enddate = $endingdate;
     $result_contentL = $analytics->getContent(); 
     $result_ads_rev = $result_contentL[0]->toArray(); 
     $result_dis_rev = $result_contentL[1];


    if(!empty($result_traffic)){
           
         #calculation
          $data = prepareData($result_traffic,$result_ads_rev,$result_dis_rev,$startingdate,$endingdate);
           
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
    echo json_encode(array("message" => "Unable to get Traffic Source. Data is incomplete.","status_code"=>400));
}

function prepareData($result_traffic,$adsense_revL,$display_revL,$start_date,$end_date){

/****revenue calculation display and adsense***/
$adx_avg_imp_per_pageL = round(($display_revL['adimr']*($display_revL['ecpmx']/7))/1000,2);    
$ads_imp_totalL = 0;
$ads_pageview_totalL = 0;
$ads_earn_totalL = 0;
foreach($adsense_revL as $adsL){
    $ads_imp_totalL += ($adsL->totalad_imp >=0 ? $adsL->totalad_imp : 0);
    $ads_pageview_totalL += ($adsL->total_pageviews >=0 ? $adsL->total_pageviews : 0);
    $ads_earn_totalL += ($adsL->total_earning >=0 ? $adsL->total_earning : 0);
}
if($ads_imp_totalL > 0){
  $ads_rpm_totalL = round(($ads_earn_totalL/$ads_imp_totalL)*1000,2);
  $ads_avg_imp_per_pageL = round(($ads_rpm_totalL*$ads_imp_totalL)/1000,2);

}else{
  $ads_rpm_totalL = 0;
  $ads_avg_imp_per_pageL = 0;  
}

$average_rev = ($ads_avg_imp_per_pageL >=0 ? $ads_avg_imp_per_pageL : 0)+($adx_avg_imp_per_pageL >=0 ? $adx_avg_imp_per_pageL : 0); 
/**** revenue calculation display and adsense end***/

 foreach($result_traffic as $k=>$val) {
	
		// $medium	= $val->medium;
		if(strtolower($val->medium) == "(none)") {
			$medium = "Direct";
		}
		else if(strtolower($val->medium) == "organic") {
			$medium = "Organic";
		}
		else if(strtolower($val->medium) == "referral") {
			$medium = "Referral";
		}
		else if(strtolower($val->medium) == "social" || strtolower($val->medium) == "facebook" || strtolower($val->medium) == "whatsapp" || strtolower($val->medium) == "instagram" || strtolower($val->medium) == "whatsapp") {
			$medium = "Social";
		}
		else{
			$medium = "Other";
		}
        @$tot_pageViewsP += $val->pageviews;
        
        /***********************DailyData********************************************/
        
        @$dailyPnewArrP[$medium]['users']		+= $val->users;
		@$dailyPnewArrP[$medium]['pageviews']	+= $val->pageviews;
		         
	}                                                        

 
	$cpc_chartP = $organic_chartP = $direct_chartP = $referral_chartP = 0.00;
	
		foreach($dailyPnewArrP as $key => $value){ 
			if($key == "Social"){
             $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
			$cpc_chartP = round(($agregate_pageviewP*$average_rev)/100,2); 
            }
            if($key == "Organic"){
             $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100);   
             $organic_chartP = round(($agregate_pageviewP*$average_rev)/100,2); 
            }
             if($key == "Direct"){
             $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
             $direct_chartP = round(($agregate_pageviewP*$average_rev)/100,2); 
            }
            if($key == "Referral"){
              $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
              $referral_chartP = round(($agregate_pageviewP*$average_rev)/100,2); 
            }
             if($key == "Other"){
              $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100);
              $other_chartP = round(($agregate_pageviewP*$average_rev)/100,2); 
            }
		 }
	
	
	/****************************************** chart data array ************************************/
	
    if(isset($cpc_chartP)){
        @$trafficsoursePcomp[] = array(
                                    'name'=>'Social',
                                    'y'=>$cpc_chartP,
                                    'drilldown'=>'Social'
                                    );
    }
    if(isset($referral_chartP)){
        @$trafficsoursePcomp[] = array(
                                    'name'=>'Referral',
                                    'y'=>$referral_chartP,
                                    'drilldown'=>'Social'
                                    );
    }
    if(isset($direct_chartP)){
        @$trafficsoursePcomp[] = array(
                                    'name'=>'Direct',
                                    'y'=>$direct_chartP,
                                    'drilldown'=>'Social'
                                    );
    }
    if(isset($organic_chartP)){
        @$trafficsoursePcomp[] = array(
                                    'name'=>'Organic',
                                    'y'=>$organic_chartP,
                                    'drilldown'=>'Social'
                                    );
	}
	if(isset($other_chartP)){
        @$trafficsoursePcomp[] = array(
                                    'name'=>'Other',
                                    'y'=>$other_chartP,
                                    'drilldown'=>'Other'
                                    );
    }
    
echo "<pre>";
print_r($trafficsoursePcomp);die;
$daterange = date("d M",strtotime($start_date))." - ".date("d M, Y",strtotime($end_date));	

    
	$requestarray=array('daterange'=>$daterange,'chartdata'=>$trafficsoursePcomp);

   return $requestarray; 
}/***calculation function end*****/

?>