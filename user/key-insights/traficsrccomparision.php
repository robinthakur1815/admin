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
$prevtoprevweek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($pend_week));

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
    //======================= LAST WEEK=========================//
    $previous_week = strtotime("-1 week +1 day");
    $start_week = strtotime("last monday midnight",$previous_week);
    $end_week = strtotime("next sunday",$start_week);
    $start_week = date("Y-m-d",$start_week);
    $end_week = date("Y-m-d",$end_week);
    $lastweek = date("d M", strtotime($start_week)).' - '.date("d M, Y", strtotime($end_week));
    $lastTwoWeek = date("d M", strtotime($pstart_week)).' - '.date("d M, Y", strtotime($end_week));
    $analytics->pstart_week = $start_week;
    $analytics->pend_week = $end_week;
    $result_lweek = $analytics->getTrafficSource(); 
    $result_lweekchart = $result_lweek->toArray();
    
    if(!empty($result_traffic) || !empty($result_lweekchart)){
           
         #calculation
          $data = prepareData($result_traffic,$result_lweekchart,$pstart_week,$pend_week,$start_week,$end_week,$prevtoprevweek,$lastweek,$lastTwoWeek);
           
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

function prepareData($resultL,$resultp,$pstart_week,$pend_week,$start_week,$end_week,$prevtoprevweek,$lastweek,$lastTwoWeek){
$inArrayL = array(); $newArrL = array(); $inArrayP = array(); $newArrP = array(); $tableP = array();  $tableL = array(); $mergeArray = array(); $tot_bounces = $tot_sessions = $tot_sessionDuration = $tot_users =  $tot_bouncesL = $tot_sessionsL = $tot_sessionDurationL = $tot_usersL = 0;
	
  $rowP =  $resultL; 
	foreach($resultL as $k=>$val) {
	   
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
		// $medium										= $val->medium;
		@$inArrayP[$medium][$k]['users']				= $val->users;
		@$inArrayP[$medium][$k]['sessionDuration']	= $val->sessionDuration;
		@$inArrayP[$medium][$k]['pageviews']			= $val->pageviews;
		@$inArrayP[$medium][$k]['bounces']			= $val->bounces;
		@$inArrayP[$medium][$k]['sessions']			= $val->sessions;

		if(in_array($medium,$inArrayP)){

			@$inArrayP[$medium][$k]['users']				+= $val->users;
			@$inArrayP[$medium][$k]['sessionDuration']	+= $val->sessionDuration;
			@$inArrayP[$medium][$k]['pageviews']			+= $val->pageviews;
			@$inArrayP[$medium][$k]['bounces']			+= $val->bounces;
			@$inArrayP[$medium][$k]['sessions']			+= $val->sessions;
		}else{
		
			@$inArrayP[$medium][$k]['medium']			= $medium;
			// $inArrayP[$medium][$k]['medium']			= $val->medium;
		}
		
		
		$tot_bounces 			+= $val->bounces;
		$tot_sessions			+= $val->sessions;
		$tot_users 				+= $val->users;
		$tot_sessionDuration	+= $val->sessionDuration; 
        
        /***************************Daily Data************************************/
        $dailymedium										= $medium;
        // $dailymedium										= $val->medium;
        $pDate                                      = $val->date;
		@$dailyinArrayP[$dailymedium][$pDate][$k]['users']				= $val->users;
		@$dailyinArrayP[$dailymedium][$pDate][$k]['sessionDuration']	= $val->sessionDuration;
		@$dailyinArrayP[$dailymedium][$pDate][$k]['pageviews']			= $val->pageviews;
		@$dailyinArrayP[$dailymedium][$pDate][$k]['bounces']			= $val->bounces;
		@$dailyinArrayP[$dailymedium][$pDate][$k]['sessions']			= $val->sessions;

		if(in_array($dailymedium,$dailyinArrayP)){

			@$dailyinArrayP[$dailymedium][$pDate][$k]['users']				+= $val->users;
			@$dailyinArrayP[$dailymedium][$pDate][$k]['sessionDuration']	+= $val->sessionDuration;
			@$dailyinArrayP[$dailymedium][$pDate][$k]['pageviews']			+= $val->pageviews;
			@$dailyinArrayP[$dailymedium][$pDate][$k]['bounces']			+= $val->bounces;
			@$dailyinArrayP[$dailymedium][$pDate][$k]['sessions']			+= $val->sessions;
		}else{
		
			@$dailyinArrayP[$dailymedium][$pDate][$k]['medium']			= $medium;
			// $dailyinArrayP[$dailymedium][$pDate][$k]['medium']			= $val->medium;
		}
		
		@$dailytot_bounces 			+= $val->bounces;
		@$dailytot_sessions			+= $val->sessions;
		@$dailytot_users 			+= $val->users;
		@$dailytot_sessionDuration	+= $val->sessionDuration;
        
	}
   
   
	foreach($inArrayP as $k=> $arrVal){
		$data = array();
        
		foreach ($inArrayP[$k] as $kk=>$v){
		    $data['medium']				= $v['medium'];
			@$data['users']				+= $v['users'];
			@$data['sessionDuration']	+= $v['sessionDuration'];
			@$data['pageviews']			+= $v['pageviews'];
			@$data['bounces']			+= $v['bounces'];
			@$data['sessions']			+= $v['sessions'];  
		}
		$newArrP[$k] = $data;
	}
	foreach($newArrP as $key=>$nval){

		$tableP['medium'][$key] 			= $nval['medium'];
		$tableP['users'][$key] 				= $nval['users'];
		$tableP['sessionDuration'][$key] 	= $nval['sessionDuration'];
		$tableP['pageviews'][$key]			= $nval['pageviews'];
		$tableP['bounces'][$key]			= $nval['bounces'];
		$tableP['sessions'][$key]			= $nval['sessions'];
	}
	
	/***************************************  sum up data for table  ***********************************/
	
	
    /*********************************Datewise Display Data*******************************************************************/
    
	foreach($dailyinArrayP as $dailyk=> $dailyarrVal){
		$data = array();
        
		foreach ($dailyinArrayP[$dailyk] as $dailykk=>$dailyv){
            foreach($dailyinArrayP[$dailyk][$dailykk] AS $tccList){		      
                //echo "<li>++".$v[1]['users'];
    			$dailydata[$dailykk]['medium']				= $tccList['medium'];
    			@$dailydata[$dailykk]['users']				+= $tccList['users'];
    			@$dailydata[$dailykk]['sessionDuration']	+= $tccList['sessionDuration'];
    			@$dailydata[$dailykk]['pageviews']			+= $tccList['pageviews'];
    			@$dailydata[$dailykk]['bounces']			+= $tccList['bounces'];
    			@$dailydata[$dailykk]['sessions']			+= $tccList['sessions'];
                
            } 
		}
        $dailynewArrP[$dailyk] = $dailydata;
	}

	foreach($dailynewArrP as $dailykey=>$dailynval){
        foreach($dailynewArrP[$dailykey] AS $dailykey1=>$dailynval1){
            //echo "<li>++".$dailynval1['medium'][$dailykey1][$dailykey];
    		@$dailytableP['medium'][$dailykey1][$dailykey] 			= $dailynval1['medium'];
    		@$dailytableP['users'][$dailykey1][$dailykey] 				= $dailynval1['users'];
    		@$dailytableP['sessionDuration'][$dailykey1][$dailykey] 	= $dailynval1['sessionDuration'];
    		@$dailytableP['pageviews'][$dailykey1][$dailykey]			= $dailynval1['pageviews'];
    		@$dailytableP['bounces'][$dailykey1][$dailykey]			= $dailynval1['bounces'];
    		@$dailytableP['sessions'][$dailykey1][$dailykey]			= $dailynval1['sessions'];
        }
	}
    
    foreach($rowP as $k=>$val) {
	
		// $medium										= $val->medium;
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

		@$inArrayP[$medium][$k]['users']				= $val->users;
		@$inArrayP[$medium][$k]['sessionDuration']	= $val->sessionDuration;
		@$inArrayP[$medium][$k]['pageviews']			= $val->pageviews;
		@$inArrayP[$medium][$k]['bounces']			= $val->bounces;
		@$inArrayP[$medium][$k]['sessions']			= $val->sessions;

		if(in_array($medium,$inArrayP)){

			@$inArrayP[$medium][$k]['users']				+= $val->users;
			@$inArrayP[$medium][$k]['sessionDuration']	+= $val->sessionDuration;
			@$inArrayP[$medium][$k]['pageviews']			+= $val->pageviews;
			@$inArrayP[$medium][$k]['bounces']			+= $val->bounces;
			@$inArrayP[$medium][$k]['sessions']			+= $val->sessions;
		}else{
			
			@$inArrayP[$medium][$k]['medium']			= $medium;
			// $inArrayP[$medium][$k]['medium']			= $val->medium;
		}
		
		@$tot_bouncesP += $val->bounces;
		@$tot_sessionsP += $val->sessions;
		@$tot_usersP += $val->users;
		@$tot_sessionDurationP += $val->sessionDuration; 
        
        /***********************DailyData********************************************/
        $dailyPmedium										= $medium;
        // $dailyPmedium										= $val->medium;
        $dailyPpdate                                        = $val->date;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['users']				= $val->users;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['sessionDuration']	= $val->sessionDuration;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['pageviews']			= $val->pageviews;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['bounces']			= $val->bounces;
		$dailyPinArrayP[$medium][$dailyPpdate][$k]['sessions']			= $val->sessions;

		if(in_array($dailyPmedium,$dailyPinArrayP)){

			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['users']				+= $val->users;
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['sessionDuration']	+= $val->sessionDuration;
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['pageviews']			+= $val->pageviews;
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['bounces']			+= $val->bounces;
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['sessions']			+= $val->sessions;
		}else{
			
			$dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['medium']			= $medium;
			// $dailyPinArrayP[$dailyPmedium][$dailyPpdate][$k]['medium']			= $val->medium;
		}
		
		@$dailyPtot_bouncesP += $val->bounces;
		@$dailyPtot_sessionsP += $val->sessions;
		@$dailyPtot_usersP += $val->users;
		@$dailyPtot_sessionDurationP += $val->sessionDuration;      
        
              
	}                                                        
    
	foreach($inArrayP as $k=> $arrVal){

		$data = array();

		foreach ($inArrayP[$k] as $kk=>$v){

			@$data['medium']				= $v['medium'];
			@$data['users']				+= $v['users'];
			@$data['sessionDuration']	+= $v['sessionDuration'];
			@$data['pageviews']			+= $v['pageviews'];
			@$data['bounces']			+= $v['bounces'];
			@$data['sessions']			+= $v['sessions']; 
		}
		$newArrP[$k] = $data; 
	}

	foreach($newArrP as $key=>$nval){

		$tableP['medium'][$key]				= $nval['medium'];
		$tableP['users'][$key]				= $nval['users'];
		$tableP['sessionDuration'][$key]	= $nval['sessionDuration'];
		$tableP['pageviews'][$key]			= $nval['pageviews'];
		$tableP['bounces'][$key]			= $nval['bounces'];
		$tableP['sessions'][$key]			= $nval['sessions'];
	}
    
    /********************Datewise Data*************************************************/
    foreach($dailyPinArrayP as $dailyPk=> $dailyParrVal){

		$dailyPdata = array();

		foreach ($dailyPinArrayP[$dailyPk] as $dailyPkk=>$dailyPv){
            foreach($dailyPinArrayP[$dailyPk][$dailyPkk] AS $dailyPtcc){
    			$dailyPdata[$dailyPkk]['medium']				= $dailyPtcc['medium'];
    			@$dailyPdata[$dailyPkk]['users']				+= $dailyPtcc['users'];
    			@$dailyPdata[$dailyPkk]['sessionDuration']	+= $dailyPtcc['sessionDuration'];
    			@$dailyPdata[$dailyPkk]['pageviews']			+= $dailyPtcc['pageviews'];
    			@$dailyPdata[$dailyPkk]['bounces']			+= $dailyPtcc['bounces'];
    			@$dailyPdata[$dailyPkk]['sessions']			+= $dailyPtcc['sessions']; 
            }
		}
		@$dailyPnewArrP[$dailyPk] = $dailyPdata; 
	}
	
	
	$cpc_chartP = array();
	$organic_chartP = array();
	$direct_chartP = array();
	$referral_chartP = array();
	if($dailyPnewArrP['Social']){
		foreach($dailyPnewArrP['Social'] as $key => $value){ $cpc_chartP[] = $value['users']; }
	}
	if($dailyPnewArrP['Organic']){
		foreach($dailyPnewArrP['Organic'] as $key => $value){ $organic_chartP[] = $value['users']; }
	}
	if($dailyPnewArrP['Direct']){
		foreach($dailyPnewArrP['Direct'] as $key => $value){ $direct_chartP[] = $value['users']; }
	}
	if($dailyPnewArrP['Referral']){
		foreach($dailyPnewArrP['Referral'] as $key => $value){ $referral_chartP[] = $value['users']; }
	}
	if($dailyPnewArrP['Other']){
		foreach($dailyPnewArrP['Other'] as $key => $value){ $other_chartP[] = $value['users']; }
	}
	
	
    
	/****************************************** chart data array ************************************/
	
    if(!empty($cpc_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Social',
                                    'data'=>$cpc_chartP
                                    );
    }
    if(!empty($referral_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Referral',
                                    'data'=>$referral_chartP
                                    );
    }
    if(!empty($direct_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Direct',
                                    'data'=>$direct_chartP
                                    );
    }
    if(!empty($organic_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Organic',
                                    'data'=>$organic_chartP
                                    );
	}
	if(!empty($other_chartP)){
        $trafficsoursePcomp[] = array(
                                    'name'=>'Other',
                                    'data'=>$other_chartP
                                    );
    }
    
    $trafficsoursePcompJson = json_encode($trafficsoursePcomp);
		
	foreach($dailyPnewArrP as $dailyPkey=>$dailyPnval){
        foreach($dailyPnewArrP[$dailyPkey] AS $dailyPkey1=>$dailyPnval1){
    		$dailyPtableP['medium'][$dailyPkey1][$dailyPkey]				= $dailyPnval1['medium'];
    		$dailyPtableP['users'][$dailyPkey1][$dailyPkey]				= $dailyPnval1['users'];
    		$dailyPtableP['sessionDuration'][$dailyPkey1][$dailyPkey]	= $dailyPnval1['sessionDuration'];
    		$dailyPtableP['pageviews'][$dailyPkey1][$dailyPkey]			= $dailyPnval1['pageviews'];
    		$dailyPtableP['bounces'][$dailyPkey1][$dailyPkey]			= $dailyPnval1['bounces'];
    		$dailyPtableP['sessions'][$dailyPkey1][$dailyPkey]			= $dailyPnval1['sessions'];
			
        }
	}
//========================= Last Week Query ========================//
   $inArrayL = array();
	foreach($resultp as $k=>$val) {
	
		// $medium										= $val->medium;
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

		$inArrayL[$medium][$k]['users']				= $val->users;
		$inArrayL[$medium][$k]['sessionDuration']	= $val->sessionDuration;
		$inArrayL[$medium][$k]['pageviews']			= $val->pageviews;
		$inArrayL[$medium][$k]['bounces']			= $val->bounces;
		$inArrayL[$medium][$k]['sessions']			= $val->sessions;

		if(in_array($medium,$inArrayL)){

			@$inArrayL[$medium][$k]['users']				+= $val->users;
			@$inArrayL[$medium][$k]['sessionDuration']	+= $val->sessionDuration;
			@$inArrayL[$medium][$k]['pageviews']			+= $val->pageviews;
			@$inArrayL[$medium][$k]['bounces']			+= $val->bounces;
			@$inArrayL[$medium][$k]['sessions']			+= $val->sessions;
		}else{
			
			$inArrayL[$medium][$k]['medium']			= $medium;
			// $inArrayL[$medium][$k]['medium']			= $val->medium;
		}
		
		@$tot_bouncesL += $val->bounces;
		@$tot_sessionsL += $val->sessions;
		@$tot_usersL += $val->users;
		@$tot_sessionDurationL += $val->sessionDuration; 
        
        /***********************DailyData********************************************/
        $dailyLmedium										= $medium;
        // $dailyLmedium										= $val->medium;
        $dailyLpdate                                        = $val->date;
		$dailyLinArrayL[$medium][$dailyLpdate][$k]['users']				= $val->users;
		$dailyLinArrayL[$medium][$dailyLpdate][$k]['sessionDuration']	= $val->sessionDuration;
		$dailyLinArrayL[$medium][$dailyLpdate][$k]['pageviews']			= $val->pageviews;
		$dailyLinArrayL[$medium][$dailyLpdate][$k]['bounces']			= $val->bounces;
		$dailyLinArrayL[$medium][$dailyLpdate][$k]['sessions']			= $val->sessions;

		if(in_array($dailyLmedium,$dailyLinArrayL)){

			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['users']				+= $val->users;
			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['sessionDuration']	+= $val->sessionDuration;
			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['pageviews']			+= $val->pageviews;
			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['bounces']			+= $val->bounces;
			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['sessions']			+= $val->sessions;
		}else{
			
			@$dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['medium']			= $medium;
			// $dailyLinArrayL[$dailyLmedium][$dailyLpdate][$k]['medium']			= $val->medium;
		}
		
		@$dailyLtot_bouncesL += $val->bounces;
		@$dailyLtot_sessionsL += $val->sessions;
		@$dailyLtot_usersL += $val->users;
		@$dailyLtot_sessionDurationL += $val->sessionDuration;      
        
              
	}                                                        
   
	
	foreach($inArrayL as $k=> $arrVal){

		$data = array();

		foreach ($inArrayL[$k] as $kk=>$v){

			$data['medium']				= $v['medium'];
			@$data['users']				+= $v['users'];
			@$data['sessionDuration']	+= $v['sessionDuration'];
			@$data['pageviews']			+= $v['pageviews'];
			@$data['bounces']			+= $v['bounces'];
			@$data['sessions']			+= $v['sessions']; 
		}
		$newArrL[$k] = $data; 
	}

	foreach($newArrL as $key=>$nval){

		$tableL['medium'][$key]				= $nval['medium'];
		$tableL['usersL'][$key]				= $nval['users'];
		$tableL['sessionDurationL'][$key]	= $nval['sessionDuration'];
		$tableL['pageviewsL'][$key]			= $nval['pageviews'];
		$tableL['bouncesL'][$key]			= $nval['bounces'];
		$tableL['sessionsL'][$key]			= $nval['sessions'];
	}
    
    /********************Datewise Data*************************************************/
    foreach($dailyLinArrayL as $dailyLk=> $dailyLarrVal){

		$dailyLdata = array();

		foreach ($dailyLinArrayL[$dailyLk] as $dailyLkk=>$dailyLv){
            foreach($dailyLinArrayL[$dailyLk][$dailyLkk] AS $dailyLtcc){
    			$dailyLdata[$dailyLkk]['medium']				= $dailyLtcc['medium'];
    			@$dailyLdata[$dailyLkk]['users']				+= $dailyLtcc['users'];
    			@$dailyLdata[$dailyLkk]['sessionDuration']	+= $dailyLtcc['sessionDuration'];
    			@$dailyLdata[$dailyLkk]['pageviews']			+= $dailyLtcc['pageviews'];
    			@$dailyLdata[$dailyLkk]['bounces']			+= $dailyLtcc['bounces'];
    			@$dailyLdata[$dailyLkk]['sessions']			+= $dailyLtcc['sessions']; 
            }
		}
		@$dailyLnewArrL[$dailyLk] = $dailyLdata; 
	}
	
	foreach($dailyLnewArrL as $dailyLkey=>$dailyLnval){
        foreach($dailyLnewArrL[$dailyLkey] AS $dailyLkey1=>$dailyLnval1){
    		$dailyLtableL['medium'][$dailyLkey1][$dailyLkey]				= $dailyLnval1['medium'];
    		$dailyLtableL['usersL'][$dailyLkey1][$dailyLkey]				= $dailyLnval1['users'];
    		$dailyLtableL['sessionDurationL'][$dailyLkey1][$dailyLkey]	= $dailyLnval1['sessionDuration'];
    		$dailyLtableL['pageviewsL'][$dailyLkey1][$dailyLkey]			= $dailyLnval1['pageviews'];
    		$dailyLtableL['bouncesL'][$dailyLkey1][$dailyLkey]			= $dailyLnval1['bounces'];
    		$dailyLtableL['sessionsL'][$dailyLkey1][$dailyLkey]			= $dailyLnval1['sessions'];
        }
	}

	
	
	
	$cpc_chart = array();
	$organic_chart = array();
	$direct_chart = array();
	$referral_chart = array();
	if($dailyLnewArrL['Social']){
		foreach($dailyLnewArrL['Social'] as $key => $value){ $cpc_chart[] = $value['users']; }
	}
	if($dailyLnewArrL['Organic']){
		foreach($dailyLnewArrL['Organic'] as $key => $value){ $organic_chart[] = $value['users']; }
	}
	if($dailyLnewArrL['Direct']){
		foreach($dailyLnewArrL['Direct'] as $key => $value){ $direct_chart[] = $value['users']; }
	}
	if($dailyLnewArrL['Referral']){
		foreach($dailyLnewArrL['Referral'] as $key => $value){ $referral_chart[] = $value['users']; }
	}
	if($dailyLnewArrL['Other']){
		foreach($dailyLnewArrL['Other'] as $key => $value){ $other_chart[] = $value['users']; }
	}
	
	
    if(!empty($cpc_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Social',
                                    'data'=>$cpc_chart
                                    );
    }
    if(!empty($referral_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Referral',
                                    'data'=>$referral_chart
                                    );
    }
    if(!empty($direct_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Direct',
                                    'data'=>$direct_chart
                                    );
    }
    if(!empty($organic_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Organic',
                                    'data'=>$organic_chart
                                    );
	}
	if(!empty($other_chart)){
        $trafficsoursecomp[] = array(
                                    'name'=>'Other',
                                    'data'=>$other_chart
                                    );
    }
    
    $trafficsoursecompJson = json_encode($trafficsoursecomp);
	

	
	
	$counter=0;
	foreach($tableP['medium'] AS $k=>$v){

		if(in_array($v, $tableL['medium'])){
		
			$index 										= array_search($v, $tableL['medium']);
			$mergeArray[$counter]['medium']				= $v;
			$mergeArray[$counter]['users']				= $tableP['users'][$k];
			$mergeArray[$counter]['usersL']				= $tableL['usersL'][$index];
			$mergeArray[$counter]['sessionDuration']	= $tableP['sessionDuration'][$k];
			$mergeArray[$counter]['sessionDurationL']	= $tableL['sessionDurationL'][$index];
			$mergeArray[$counter]['pageviews']			= $tableP['pageviews'][$k];
			$mergeArray[$counter]['pageviewsL']			= $tableL['pageviewsL'][$index];
			$mergeArray[$counter]['bounces']			= $tableP['bounces'][$k];
			$mergeArray[$counter]['bouncesL']			= $tableL['bouncesL'][$index];
			$mergeArray[$counter]['sessions']			= $tableP['sessions'][$k];
			$mergeArray[$counter]['sessionsL']			= $tableL['sessionsL'][$index];
			$counter++;
		}else{
		
			$mergeArray[$counter]['medium']				= $v;
			$mergeArray[$counter]['users']				= $tableP['users'][$k];
			$mergeArray[$counter]['usersL']				= 0;
			$mergeArray[$counter]['sessionDuration']	= $tableP['sessionDuration'][$k];
			$mergeArray[$counter]['sessionDurationL']	= 0;
			$mergeArray[$counter]['pageviews']			= $tableP['pageviews'][$k];
			$mergeArray[$counter]['pageviewsL']			= 0;
			$mergeArray[$counter]['bounces']			= $tableP['bounces'][$k];
			$mergeArray[$counter]['bouncesL']			= 0;
			$mergeArray[$counter]['sessions']			= $tableP['sessions'][$k];
			$mergeArray[$counter]['sessionsL']			= 0;
			$counter++;
		}
	}
	aasort($mergeArray,"usersL"); $i=1;
	
	$avgBounceRateL			= $tot_bouncesL/$tot_sessionsL;
	$avgBounceRate			= $tot_bounces/$tot_sessions;
	$avgSessionDurationL	= $tot_sessionDurationL/$tot_sessionsL;
	$avgSessionDuration		= $tot_sessionDuration/$tot_sessions;
	
	/************************************** 	table sum data				******************************/
	
	
	$medium_counter =0; $outercount=0;
	foreach($mergeArray as $key=>$value){
	if($medium_counter == 5){break;}
	$avgBounceL 	= $value['bouncesL']/$value['sessionsL'];
    $avgBounce 		= $value['bounces']/$value['sessions'];
    $avgSessionL	= $value['sessionDurationL']/$value['sessionsL'];
    $avgSession		= $value['sessionDuration']/$value['sessions'];
    
	$channel = $value['medium'];
								
	$tabledata[$outercount][]=array(
		'channel'=>ucwords($channel),
		// 'uniqueviit'=>number_format($value['usersL']),
		'uniqueviit'=>round(($value['usersL']/$tot_usersL)*100,2),
		'avgsession'=>gmdate("H:i:s",$avgSessionL),
		// 'avgsession'=>number_format((($avgSessionL/$avgSessionDurationL)-1)*100, 2),
		'avgbounce'=>number_format($avgBounceL*100, 2),
		// 'avgbounce'=>number_format(((($avgBounceL/$avgBounceRateL)-1)*100), 2),
		'channel1'=>ucwords($channel),
		// 'uniquevisit1'=>number_format($value['users']),
		'uniquevisit1'=>round(($value['users']/$tot_users)*100,2),
		'avgsession1'=>gmdate("H:i:s",$avgSession),
		// 'avgsession1'=>number_format((($avgSession/$avgSessionDuration)-1)*100, 2),
		'avgbounce1'=>number_format($avgBounce*100, 2),
		// 'avgbounce1'=>number_format(((($avgBounce/$avgBounceRate)-1)*100), 2),
		);
		$medium_counter++;
		$outercount++;
	}
	
		
	
	/************************************** 	Inner table data		******************************/

	$arrcnt=0;$medium_counter_value =0;
	foreach($mergeArray as $key=>$value){
	if($medium_counter_value == 5){ continue;}
	
	
	$total_temp_users = 0;
	$total_temp_usersL = 0;
	
	$channel = $value['medium'];
	$total_temp_users = $value['users'];
	$total_temp_usersL = $value['usersL'];
	

	$dateVal = array();	
	
	for($l=0; $l<count($dailyPtableP['medium']); $l++)
	{
	$dateVal = array_keys($dailyPtableP['medium']);
	
	
	$channelVal = $channel;

	$dateavgSession = $dailyPtableP['sessionDuration'][$dateVal[$l]][$value['medium']]/$dailyPtableP['sessions'][$dateVal[$l]][$value['medium']];
	$dateavgBounce = $dailyPtableP['bounces'][$dateVal[$l]][$value['medium']]/$dailyPtableP['sessions'][$dateVal[$l]][$value['medium']];
	
	$innertabledata[$arrcnt][] = array(
								'medium'=>$value['medium'],
								'date'=>date("d M,Y",strtotime($dateVal[$l])),
								'unqvis'=>round(($dailyPtableP['users'][$dateVal[$l]][$value['medium']]/$total_temp_users)*100,2),
								'avgs'=>gmdate("H:i:s",$dateavgSession),
								'avgb'=>round($dateavgBounce*100,2)
								);
	
	
	}
	
	for($l=0; $l<count($dailyLtableL['medium']); $l++)
	{
	$dateVal = array_keys($dailyLtableL['medium']);
	
	$channelVal = $channel;

	$dateavgSession = $dailyLtableL['sessionDurationL'][$dateVal[$l]][$value['medium']]/$dailyLtableL['sessionsL'][$dateVal[$l]][$value['medium']];
	$dateavgBounce = $dailyLtableL['bouncesL'][$dateVal[$l]][$value['medium']]/$dailyLtableL['sessionsL'][$dateVal[$l]][$value['medium']];
	
	$innertabledataL[$arrcnt][] = array(
								'medium1'=>$value['medium'],
								'date1'=>date("d M,Y",strtotime($dateVal[$l])),
								'unqvis1'=>round(($dailyLtableL['usersL'][$dateVal[$l]][$value['medium']]/$total_temp_usersL)*100,2),
								'avgs1'=>gmdate("H:i:s",$dateavgSession),
								'avgb1'=>round($dateavgBounce*100,2)
								);
	
	
	}
	
	 $medium_counter_value++;$arrcnt++;
	}


	
	$avgBounceRateL 	= $tot_bounces/$tot_sessions;
	$avgBounceRate	= $tot_bouncesL/$tot_sessionsL;
	$tapdataarray=array('unqvisitor'=>number_format($tot_users),'avgsession'=>gmdate("H:i:s",($tot_sessionDuration/$tot_sessions)),'avgbounce'=>number_format(($avgBounceRateL*100), 2),'daterange'=>$prevtoprevweek);

$tapdataarray1=array('unqvisitor'=>number_format($tot_usersL),'avgsession'=>gmdate("H:i:s",($tot_sessionDurationL/$tot_sessionsL)),'avgbounce'=>number_format(($avgBounceRate*100), 2),'daterange'=>$lastweek);	
    while ($pstart_week <= $pend_week) {
                @$daterangep[] .= date("d M", strtotime($pstart_week));
                $pstart_week = date ("Y-m-d", strtotime("+1 day", strtotime($pstart_week)));
	}
	while ($start_week <= $end_week) {
                @$daterangeL[] .= date("d M", strtotime($start_week));
                $start_week = date ("Y-m-d", strtotime("+1 day", strtotime($start_week)));
	}
	$requestarray=array('daterange'=>array_reverse($daterangep),'topdata'=>array($tapdataarray),'chartdata'=>$trafficsoursePcomp,'daterange1'=>array_reverse($daterangeL),'topdata1'=>array($tapdataarray1),'chartdata1'=>$trafficsoursecomp,'tabledata'=>$tabledata,'topdate'=>array($lastTwoWeek),'innertabledata'=>$innertabledata,'innertabledataL'=>$innertabledataL);

   return $requestarray; 
}/***calculation function end*****/
function aasort(&$array, $key) {
	
	$sorter=array(); $ret=array(); reset($array);
    
	foreach ($array as $ii => $va) {
		$sorter[$ii]=$va[$key];
	}
    
	arsort($sorter); 
    
	foreach ($sorter as $ii => $va) {
		$ret[$ii]=$array[$ii];
	}
    
	$array=$ret;
}
?>