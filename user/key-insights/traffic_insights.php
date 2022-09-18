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
//======================= PREVIOUS To PREVIOUS WEEK=========================//
$prevToPrevWeek = strtotime("-2 week +1 day");
$pstart_week = strtotime("last monday midnight",$prevToPrevWeek);
$pend_week = strtotime("next sunday",$pstart_week);
$pstart_week = date("Y-m-d",$pstart_week);
$pend_week = date("Y-m-d",$pend_week);
$prevtoprevweek = date("j M", strtotime($pstart_week)).' - '.date("j M, Y", strtotime($pend_week));

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
   
     #set traffic property values PREVIOUS To PREVIOUS Week
     $analytics->account_id = $data->account_id;
     $analytics->pstart_week = $pstart_week;
     $analytics->pend_week = $pend_week;
     $result_content = $analytics->getTrafficSource(); 
     $result_traffic = $result_content->toArray();
      /****Adx and Adsense revenue for previous to previous week***/
     $analytics->uniq_id = $data->uniq_id;
     $analytics->child_net_code = $data->child_net_code;
     $analytics->strtdate = $pstart_week;
     $analytics->enddate = $pend_week;
     $result_content = $analytics->getContent(); 
     $result_ads_revP = $result_content[0]->toArray(); 
     $result_dis_revP = $result_content[1];

 /****Adx and Adsense revenue for previous to previous week end***/
    //======================= LAST WEEK=========================//
    $previous_week = strtotime("-1 week +1 day");
    $start_week = strtotime("last monday midnight",$previous_week);
    $end_week = strtotime("next sunday",$start_week);
    $start_week = date("Y-m-d",$start_week);
    $end_week = date("Y-m-d",$end_week);
    $lastweek = date("j M", strtotime($start_week)).' - '.date("j M, Y", strtotime($end_week));
    #$lastTwoWeek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($end_week));
    $analytics->pstart_week = $start_week;
    $analytics->pend_week = $end_week;
    $result_lweek = $analytics->getTrafficSource(); 
    $result_lweekchart = $result_lweek->toArray();
/****Adx and Adsense revenue for last week***/
     $analytics->strtdate = $start_week;
     $analytics->enddate = $end_week;
     $result_contentL = $analytics->getContent(); 
     $result_ads_revL = $result_contentL[0]->toArray(); 
     $result_dis_revL = $result_contentL[1];

 /****Adx and Adsense revenue for last week end***/
    if(!empty($result_traffic) || !empty($result_lweekchart)){
           
         #calculation
          $data = prepareData($result_traffic,$result_ads_revP,$result_dis_revP,$result_lweekchart,$result_ads_revL,$result_dis_revL,$pstart_week,$pend_week,$start_week,$end_week,$prevtoprevweek,$lastweek);
           
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

function prepareData($resultL,$adsense_revP,$display_revP,$resultp,$adsense_revL,$display_revL,$pstart_week,$pend_week,$start_week,$end_week,$prevtoprevweek,$lastweek){

while ($pstart_week <= $pend_week) {
                @$daterangep[] .= date("j M", strtotime($pstart_week));
                @$datepre[] = date("Y-m-d", strtotime($pstart_week));
                $pstart_week = date ("Y-m-d", strtotime("+1 day", strtotime($pstart_week)));
	}
	while ($start_week <= $end_week) {
                @$daterangeL[] .= date("j M", strtotime($start_week));
                @$dateLast[] = date("Y-m-d", strtotime($start_week));
                $start_week = date ("Y-m-d", strtotime("+1 day", strtotime($start_week)));
	}
/****previous week revenue calculation display and adsense***/	
$adx_avg_imp_per_page = round(($display_revP['adimr']*($display_revP['ecpmx']/7))/1000,2);    
$ads_imp_total=0;
$ads_pageview_total=0;
$ads_earn_total=0;
foreach($adsense_revP as $ads){
    $ads_imp_total += ($ads->totalad_imp >=0 ? $ads->totalad_imp : 0);
    $ads_pageview_total += ($ads->total_pageviews >=0 ? $ads->total_pageviews : 0);
    $ads_earn_total += ($ads->total_earning >=0 ? $ads->total_earning : 0);
}
if($ads_imp_total > 0){
  $ads_rpm_total = round(($ads_earn_total/$ads_imp_total)*1000,2);
  $ads_avg_imp_per_page = round(($ads_rpm_total*$ads_imp_total)/1000,2);

}else{
  $ads_rpm_total = 0;
  $ads_avg_imp_per_page = 0;  
}

$average_rev = ($ads_avg_imp_per_page >=0 ? $ads_avg_imp_per_page : 0)+($adx_avg_imp_per_page >=0 ? $adx_avg_imp_per_page : 0);
/****previous week revenue calculation display and adsense end***/
/****Last week revenue calculation display and adsense***/
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

$average_revL = ($ads_avg_imp_per_pageL >=0 ? $ads_avg_imp_per_pageL : 0)+($adx_avg_imp_per_pageL >=0 ? $adx_avg_imp_per_pageL : 0); 
/****Last week revenue calculation display and adsense end***/

     $tot_bouncesP = $tot_sessionsP = $tot_sessionDurationP = $tot_usersP =  $tot_bouncesL = $tot_sessionsL = $tot_sessionDurationL = $tot_usersL = 0;
	
  $rowP =  $resultL; //previous to previous
	
  
    foreach($rowP as $k=>$val) {
	
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

	
		@$tot_bouncesP += $val->bounces;
		@$tot_sessionsP += $val->sessions;
		@$tot_usersP += $val->users;
		@$tot_sessionDurationP += $val->sessionDuration; 
		@$tot_pageViewsP += $val->pageviews; 
        
        /***********************DailyData********************************************/
        $dailyPmedium	= $medium;
        $dailyPpdate  = $val->date;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['users']		= $val->users;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['pageviews']	= $val->pageviews;
		

		if(in_array($dailyPmedium,$dailyPinArrayP)){

			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['users']				+= $val->users;
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['pageviews']			+= $val->pageviews;
			
		}else{
			
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['medium']	= $medium;
			
		}
		
	         
	}                                                        
 
    
    /********************Datewise Data*************************************************/
    foreach($dailyPinArrayP as $dailyPk=> $dailyParrVal){

		$dailyPdata = array();

		foreach ($dailyPinArrayP[$dailyPk] as $dailyPkk=>$dailyPv){
            foreach($dailyPinArrayP[$dailyPk][$dailyPkk] AS $dailyPtcc){
    			
    			@$dailyPdata[$dailyPkk]['pageviews']+= $dailyPtcc['pageviews'];
    			
            }
		}
		@$dailyPnewArrP[$dailyPk] = $dailyPdata; 
	}

		
	
foreach($datepre as $date_value)
{
	if (!in_array($date_value, array_keys(@$dailyPnewArrP['Social'])))
    { 
      $dailyPnewArrP['Social'][$date_value]['pageviews'] = 0; 
    }
    if (!in_array($date_value, array_keys($dailyPnewArrP['Organic'])))
    { 
      $dailyPnewArrP['Organic'][$date_value]['pageviews'] = 0; 
    }
    if (!in_array($date_value, array_keys($dailyPnewArrP['Direct'])))
    { 
      $dailyPnewArrP['Direct'][$date_value]['pageviews'] = 0; 
    }
    if (!in_array($date_value, array_keys($dailyPnewArrP['Referral'])))
    { 
      $dailyPnewArrP['Referral'][$date_value]['pageviews'] = 0; 
    }
    if (!in_array($date_value, array_keys($dailyPnewArrP['Other'])))
    { 
      $dailyPnewArrP['Other'][$date_value]['pageviews'] = 0; 
    }
}

	
	$cpc_chartP = array();
	$organic_chartP = array();
	$direct_chartP = array();
	$referral_chartP = array();
	if(ksort($dailyPnewArrP['Social'])){
		foreach($dailyPnewArrP['Social'] as $key => $value){ 
			//$cpc_chartP[] = $value['users'];
			$agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
			$cpc_chartP[] = round(($agregate_pageviewP*$average_rev)/100,2); 
		 }
	}
	if(ksort($dailyPnewArrP['Organic'])){
		foreach($dailyPnewArrP['Organic'] as $key => $value){
		    $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100);   
			$organic_chartP[] = round(($agregate_pageviewP*$average_rev)/100,2); 
		}
	}
	if(ksort($dailyPnewArrP['Direct'])){
		foreach($dailyPnewArrP['Direct'] as $key => $value){
		   $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
			$direct_chartP[] = round(($agregate_pageviewP*$average_rev)/100,2); 
		}
	}
	if(ksort($dailyPnewArrP['Referral'])){
		foreach($dailyPnewArrP['Referral'] as $key => $value){
		    $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100); 
			$referral_chartP[] = round(($agregate_pageviewP*$average_rev)/100,2); }
	}
	if(ksort($dailyPnewArrP['Other'])){
		foreach($dailyPnewArrP['Other'] as $key => $value){ 
            $agregate_pageviewP = (($value['pageviews']/$tot_pageViewsP)*100);
			$other_chartP[] = round(($agregate_pageviewP*$average_rev)/100,2); }
	}
		

	/****************************************** chart data array ************************************/
	
    if(!empty($cpc_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Social',
                                    'type'=>'column',
                                    'data'=>$cpc_chartP
                                    );
    }
    if(!empty($referral_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Referral',
                                    'type'=>'column',
                                    'data'=>$referral_chartP
                                    );
    }
    if(!empty($direct_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Direct',
                                    'type'=>'column',
                                    'data'=>$direct_chartP
                                    );
    }
    if(!empty($organic_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Organic',
                                    'type'=>'column',
                                    'data'=>$organic_chartP
                                    );
	}
	if(!empty($other_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Other',
                                    'type'=>'column',
                                    'data'=>$other_chartP
                                    );
    }
    //========================= Last Week Query ========================//
   
    foreach($resultp as $k=>$val) {
    
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

        
        
        @$tot_bouncesL += $val->bounces;
        @$tot_sessionsL += $val->sessions;
        @$tot_usersL += $val->users;
        @$tot_sessionDurationL += $val->sessionDuration; 
        @$tot_pageViewsL += $val->pageviews;
        /***********************DailyData********************************************/
        $dailyLmedium   = $medium;
        $dailyLpdate   = $val->date;
        $dailyLinArrayL[$medium][$dailyLpdate][$k]['users'] = $val->users;
        $dailyLinArrayL[$medium][$dailyLpdate][$k]['pageviews'] = $val->pageviews;
        

        if(in_array($dailyLmedium,$dailyLinArrayL)){

            @$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['users']              += $val->users;
            
            @$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['pageviews']          += $val->pageviews;
          
        }else{
            
            @$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['medium'] = $medium;
          
        }
              
    }                                                        
   
    
    /********************Datewise Data*************************************************/
    foreach($dailyLinArrayL as $dailyLk=> $dailyLarrVal){

        $dailyLdata = array();

        foreach ($dailyLinArrayL[$dailyLk] as $dailyLkk=>$dailyLv){
            foreach($dailyLinArrayL[$dailyLk][$dailyLkk] AS $dailyLtcc){
               
               @$dailyLdata[$dailyLkk]['pageviews'] += $dailyLtcc['pageviews'];
                 
            }
        }
        @$dailyLnewArrL[$dailyLk] = $dailyLdata; 
    }
    
   foreach($dateLast as $date_valuel)
    {
        if (!in_array($date_valuel, array_keys(@$dailyLnewArrL['Social'])))
        { 
          $dailyLnewArrL['Social'][$date_valuel]['pageviews'] = 0; 
        }
        if (!in_array($date_valuel, array_keys($dailyLnewArrL['Organic'])))
        { 
          $dailyLnewArrL['Organic'][$date_valuel]['pageviews'] = 0; 
        }
        if (!in_array($date_valuel, array_keys($dailyLnewArrL['Direct'])))
        { 
          $dailyLnewArrL['Direct'][$date_valuel]['pageviews'] = 0; 
        }
        if (!in_array($date_valuel, array_keys($dailyLnewArrL['Referral'])))
        { 
          $dailyLnewArrL['Referral'][$date_valuel]['pageviews'] = 0; 
        }
        if (!in_array($date_valuel, array_keys($dailyLnewArrL['Other'])))
        { 
          $dailyLnewArrL['Other'][$date_valuel]['pageviews'] = 0; 
        }
    } 
    $cpc_chart = array();
    $organic_chart = array();
    $direct_chart = array();
    $referral_chart = array();
    if(ksort($dailyLnewArrL['Social'])){
        foreach($dailyLnewArrL['Social'] as $key => $value){ 
            $agregate_pageviewL = (($value['pageviews']/$tot_pageViewsL)*100); 
            $cpc_chart[] = round(($agregate_pageviewL*$average_revL)/100,2); 
        }
    }
    if(ksort($dailyLnewArrL['Organic'])){
        foreach($dailyLnewArrL['Organic'] as $key => $value){ 
            $agregate_pageviewL = (($value['pageviews']/$tot_pageViewsL)*100); 
            $organic_chart[] = round(($agregate_pageviewL*$average_revL)/100,2);
            }
    }
    if(ksort($dailyLnewArrL['Direct'])){
        foreach($dailyLnewArrL['Direct'] as $key => $value){ 
            $agregate_pageviewL = (($value['pageviews']/$tot_pageViewsL)*100); 
            $direct_chart[] = round(($agregate_pageviewL*$average_revL)/100,2);
            }
    }
    if(ksort($dailyLnewArrL['Referral'])){
        foreach($dailyLnewArrL['Referral'] as $key => $value){ 
           $agregate_pageviewL = (($value['pageviews']/$tot_pageViewsL)*100); 
            $referral_chart[] = round(($agregate_pageviewL*$average_revL)/100,2);
            }
    }
    if(ksort($dailyLnewArrL['Other'])){
        foreach($dailyLnewArrL['Other'] as $key => $value){ 
            $agregate_pageviewL = (($value['pageviews']/$tot_pageViewsL)*100); 
            $other_chart[] = round(($agregate_pageviewL*$average_revL)/100,2);
            }
    }
    
    
    if(!empty($cpc_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Social',
                                    'type'=>'column',
                                    'data'=>$cpc_chart
                                    );
    }
    if(!empty($referral_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Referral',
                                    'type'=>'column',
                                    'data'=>$referral_chart
                                    );
    }
    if(!empty($direct_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Direct',
                                    'type'=>'column',
                                    'data'=>$direct_chart
                                    );
    }
    if(!empty($organic_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Organic',
                                    'type'=>'column',
                                    'data'=>$organic_chart
                                    );
    }
    if(!empty($other_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Other',
                                    'type'=>'column',
                                    'data'=>$other_chart
                                    );
    }

	$avgBounceRateL = $tot_bouncesP/$tot_sessionsP; //previous date
	$avgBounceRate	= $tot_bouncesL/$tot_sessionsL;

$topDataPre = array('unqvisitor'=>number_format($tot_usersP),'avgsession'=>gmdate("H:i:s",($tot_sessionDurationP/$tot_sessionsP)),'avgbounce'=>number_format(($avgBounceRateL*100), 2),'daterangeP'=>$prevtoprevweek);

$topDataLast=array('unqvisitor'=>number_format($tot_usersL),'avgsession'=>gmdate("H:i:s",($tot_sessionDurationL/$tot_sessionsL)),'avgbounce'=>number_format(($avgBounceRate*100), 2),'daterangeL'=>$lastweek);

    
	$requestarray=array('daterangepw'=>$daterangep,'topdatapw'=>array($topDataPre),'chartdatapw'=>$trafficsoursePcomp,'daterangelw'=>$daterangeL,'topdatalw'=>array($topDataLast),'chartdatalw'=>$trafficsoursecomp);

   return $requestarray; 
}/***calculation function end*****/

?>