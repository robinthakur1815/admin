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
#date_default_timezone_set('Asia/Kolkata');
date_default_timezone_set('Pacific/Honolulu');
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/DashboardPub.php';
include_once '../../objects/Top_box_trend.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$dashAdtype = new DashboardPub($db,$dbMongoDb);
$topboxTrend = new TopBoxTrend($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];

#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->child_net_code) &&
    !empty($data->range)
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
		if(!empty($rowShare)){
			if($rowShare['pub_display_share'] !=0){
				$cmsShareDis = $rowShare['pub_display_share']/100;
			}else{
				$cmsShareDis = 15/100;
			}
			if($rowShare['pub_app_share'] !=0){
				$cmsShareApp = $rowShare['pub_app_share']/100;
			}else{
				$cmsShareApp = 15/100;
			}
			if($rowShare['pub_video_share'] !=0){
				$cmsShareVid = $rowShare['pub_video_share']/100;
			}else{
				$cmsShareVid = 15/100;
			}
			if($rowShare['pub_adsense_share'] !=0){
				$cmsShareAD = $rowShare['pub_adsense_share']/100;
			}else{
				$cmsShareAD = 0;
			}
			$cmsSharePro = $cmsShareDis;
		}else{
            $cmsShareDis = 15/100;
            $cmsShareApp = 15/100;
            $cmsShareVid = 15/100;
			$cmsShareAD = 0;
			$cmsSharePro = 15/100;
		}
		#check date range validation
		if($data->range == "custom"){
			if($data->strtdate == '' && $data->enddate == ''){
				#set response code - 422 validation error
				http_response_code(422);
				#tell the user
				echo json_encode(array("message" => "Date range invalid!","status_code"=>422));
				exit();
			}
		}
		$dashAdtype->range = $data->range;
		$dashAdtype->strtdate = $data->strtdate;
		$dashAdtype->enddate = $data->enddate;
		$dashAdtype->child_net_code = $data->child_net_code;
		$dashAdtype->uniq_id = $data->uniq_id;
		#For Display Top Data
		$topboxTrend->child_net_code = $data->child_net_code;
		$topboxTrend->uniq_id = $data->uniq_id;
		
		$result_lastlast10days_display = $topboxTrend->headerTopBox1_display();
		$result_lastlast10days_display = $result_lastlast10days_display->get_result();
        $resultDatalast20_display = $result_lastlast10days_display->fetch_all(MYSQLI_ASSOC);
		$result_last10days_display = $topboxTrend->headerTopBox2_display();
		$result_last10days_display = $result_last10days_display->get_result();
        $resultDatalast10_display = $result_last10days_display->fetch_all(MYSQLI_ASSOC);
		
		// print_r($resultDatalast10_display);die;
		#For App Top Data
		
		$result_lastlast10days_app = $topboxTrend->headerTopBox1_app();
		$result_lastlast10days_app = $result_lastlast10days_app->get_result();
        $resultDatalast20_app = $result_lastlast10days_app->fetch_all(MYSQLI_ASSOC);
		$result_last10days_app = $topboxTrend->headerTopBox2_app();
		$result_last10days_app = $result_last10days_app->get_result();
        $resultDatalast10_app = $result_last10days_app->fetch_all(MYSQLI_ASSOC);
		#For Video Top Data
		
		$result_lastlast10days_video = $topboxTrend->headerTopBox1_video();
		$result_lastlast10days_video = $result_lastlast10days_video->get_result();
        $resultDatalast20_video = $result_lastlast10days_video->fetch_all(MYSQLI_ASSOC);
		$result_last10days_video = $topboxTrend->headerTopBox2_video();
		$result_last10days_video = $result_last10days_video->get_result();
        $resultDatalast10_video = $result_last10days_video->fetch_all(MYSQLI_ASSOC);
		
		#For Header Bidder Top Data
		$result_lastlast10days_HB = $topboxTrend->headerTopBox1_HB();
		$resultDatalast20_HB = $result_lastlast10days_HB->toArray();
		$result_last10days_HB = $topboxTrend->headerTopBox2_HB();
		$resultDatalast10_HB = $result_last10days_HB->toArray();
		
		#For Direct Deal Top Data
		$result_lastlast10days_DD = $topboxTrend->headerTopBox1_DD();
		$resultDatalast20_DD = $result_lastlast10days_DD->toArray();
		$result_last10days_DD = $topboxTrend->headerTopBox2_DD();
		$resultDatalast10_DD = $result_last10days_DD->toArray();
		
		#For Adsense Top Data
		$result_lastlast10days_AD = $topboxTrend->headerTopBox1_AD();
		$resultDatalast20_AD = $result_lastlast10days_AD->toArray();
		$result_last10days_AD = $topboxTrend->headerTopBox2_AD();
		$resultDatalast10_AD = $result_last10days_AD->toArray();
		
		#For headerTopBox1 Calculation
		$last_lastData =array();
		if(!empty($resultDatalast20_display)){
			foreach ($resultDatalast20_display as $key1 => $val1) {
				@$last_lastData['ad_request'] += $val1['ad_request'];
				@$last_lastData['ad_exch_impression'] += $val1['ad_exch_impression'];
				@$last_lastData['ad_exch_clicks'] += $val1['ad_exch_clicks'];
				@$last_lastData['ad_exch_revenue'] += round(($val1['ad_exch_revenue'])-($val1['ad_exch_revenue']*$cmsShareDis),5);
				@$last_lastData['ad_exch_date'] = $val1['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast20_app)){
			foreach ($resultDatalast20_app as $key2 => $val2) {
				@$last_lastData['ad_request'] += $val2['ad_request'];
				@$last_lastData['ad_exch_impression'] += $val2['ad_exch_impression'];
				@$last_lastData['ad_exch_clicks'] += $val2['ad_exch_clicks'];
				@$last_lastData['ad_exch_revenue'] += round(($val2['ad_exch_revenue'])-($val2['ad_exch_revenue']*$cmsShareApp),5);
				@$last_lastData['ad_exch_date'] = $val2['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast20_video)){
			foreach ($resultDatalast20_video as $key3 => $val3) {
				@$last_lastData['ad_request'] += $val3['ad_request'];
				@$last_lastData['ad_exch_impression'] += $val3['ad_exch_impression'];
				@$last_lastData['ad_exch_clicks'] += $val3['ad_exch_clicks'];
				@$last_lastData['ad_exch_revenue'] += round(($val3['ad_exch_revenue'])-($val3['ad_exch_revenue']*$cmsShareVid),5);
				@$last_lastData['ad_exch_date'] = $val3['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast20_HB)){
			foreach ($resultDatalast20_HB as $key4 => $val4) {
				@$last_lastData['ad_request'] += $val4->totalad_requests;
				@$last_lastData['ad_exch_impression'] += $val4->totalad_imp;
				@$last_lastData['ad_exch_clicks'] += $val4->total_click;
				@$last_lastData['ad_exch_revenue'] += round(($val4->total_earning)-($val4->total_earning*$cmsSharePro),5);
				@$last_lastData['ad_exch_date'] = $val4->_id;
			}
		}
		if(!empty($resultDatalast20_DD)){
			foreach ($resultDatalast20_DD as $key5 => $val5) {
				@$last_lastData['ad_request'] += $val5->totalad_requests;
				@$last_lastData['ad_exch_impression'] += $val5->totalad_imp;
				@$last_lastData['ad_exch_clicks'] += $val5->total_click;
				@$last_lastData['ad_exch_revenue'] += round(($val5->total_earning)-($val5->total_earning*$cmsSharePro),5);
				@$last_lastData['ad_exch_date'] = $val5->_id;
			}
		}
		if(!empty($resultDatalast20_AD)){
			foreach ($resultDatalast20_AD as $key6 => $val6) {
				@$last_lastData['ad_request'] += $val6->totalad_requests;
				@$last_lastData['ad_exch_impression'] += $val6->totalad_imp;
				@$last_lastData['ad_exch_clicks'] += $val6->total_click;
				@$last_lastData['ad_exch_revenue'] += round(($val6->total_earning)-($val6->total_earning*$cmsShareAD),5);
				@$last_lastData['ad_exch_date'] = $val6->_id;
			}
		}
		#For headerTopBox2 Calculation
		$last_Data =array();
		$allData = array();
		$testDataCheck = array();
		if(!empty($resultDatalast10_display)){
			foreach ($resultDatalast10_display as $key7 => $val7) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val7['ad_request'];
				@$last_Data['ad_exch_impression'] += $val7['ad_exch_impression'];
				@$last_Data['ad_exch_clicks'] += $val7['ad_exch_clicks'];
				@$last_Data['ad_exch_revenue'] += round(($val7['ad_exch_revenue'])-($val7['ad_exch_revenue']*$cmsShareDis),5);
				@$last_Data['ad_exch_date'] = $val7['ad_exch_date'];
				
				# creating All 10 days Data 
				@$testDataCheck[$val7['ad_exch_date']]['ad_request'] = $val7['ad_request'];
				@$testDataCheck[$val7['ad_exch_date']]['ad_exch_impression'] = $val7['ad_exch_impression'];
				@$testDataCheck[$val7['ad_exch_date']]['ad_exch_clicks'] = $val7['ad_exch_clicks'];
				@$testDataCheck[$val7['ad_exch_date']]['ad_exch_revenue'] = round(($val7['ad_exch_revenue'])-($val7['ad_exch_revenue']*$cmsShareDis),5);
				@$testDataCheck[$val7['ad_exch_date']]['ad_exch_date'] = $val7['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast10_app)){
			foreach ($resultDatalast10_app as $key8 => $val8) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val8['ad_request'];
				@$last_Data['ad_exch_impression'] += $val8['ad_exch_impression'];
				@$last_Data['ad_exch_clicks'] += $val8['ad_exch_clicks'];
				@$last_Data['ad_exch_revenue'] += round(($val8['ad_exch_revenue'])-($val8['ad_exch_revenue']*$cmsShareApp),5);
				@$last_Data['ad_exch_date'] = $val8['ad_exch_date'];
				
				# creating All 10 days Data 
				@$testDataCheck[$val8['ad_exch_date']]['ad_request'] += $val8['ad_request'];
				@$testDataCheck[$val8['ad_exch_date']]['ad_exch_impression'] += $val8['ad_exch_impression'];
				@$testDataCheck[$val8['ad_exch_date']]['ad_exch_clicks'] += $val8['ad_exch_clicks'];
				@$testDataCheck[$val8['ad_exch_date']]['ad_exch_revenue'] += round(($val8['ad_exch_revenue'])-($val8['ad_exch_revenue']*$cmsShareApp),5);
				@$testDataCheck[$val8['ad_exch_date']]['ad_exch_date'] = $val8['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast10_video)){
			foreach ($resultDatalast10_video as $key9 => $val9) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val9['ad_request'];
				@$last_Data['ad_exch_impression'] += $val9['ad_exch_impression'];
				@$last_Data['ad_exch_clicks'] += $val9['ad_exch_clicks'];
				@$last_Data['ad_exch_revenue'] += round(($val9['ad_exch_revenue'])-($val9['ad_exch_revenue']*$cmsShareVid),5);
				@$last_Data['ad_exch_date'] = $val9['ad_exch_date'];
				
				# creating All 10 days Data 
				@$testDataCheck[$val9['ad_exch_date']]['ad_request'] += $val9['ad_request'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_impression'] += $val9['ad_exch_impression'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_clicks'] += $val9['ad_exch_clicks'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_revenue'] += round(($val9['ad_exch_revenue'])-($val9['ad_exch_revenue']*$cmsShareVid),5);
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_date'] = $val9['ad_exch_date'];
			}
		}
		if(!empty($resultDatalast10_HB)){
			foreach ($resultDatalast10_HB as $key10 => $val10) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val10->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val10->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val10->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val10->total_earning)-($val10->total_earning*$cmsSharePro),5);
				$last_Data['ad_exch_date'] = $val10->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val10->_id]['ad_request'] += $val10->totalad_requests;
				@$testDataCheck[$val10->_id]['ad_exch_impression'] += $val10->totalad_imp;
				@$testDataCheck[$val10->_id]['ad_exch_clicks'] += $val10->total_click;
				@$testDataCheck[$val10->_id]['ad_exch_revenue'] += round(($val10->total_earning)-($val10->total_earning*$cmsSharePro),5);
				@$testDataCheck[$val10->_id]['ad_exch_date'] = $val10->_id;
			}
		}
		if(!empty($resultDatalast10_DD)){
			foreach ($resultDatalast10_DD as $key11 => $val11) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val11->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val11->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val11->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val11->total_earning)-($val11->total_earning*$cmsSharePro),5);
				$last_Data['ad_exch_date'] = $val11->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val11->_id]['ad_request'] += $val11->totalad_requests;
				@$testDataCheck[$val11->_id]['ad_exch_impression'] += $val11->totalad_imp;
				@$testDataCheck[$val11->_id]['ad_exch_clicks'] += $val11->total_click;
				@$testDataCheck[$val11->_id]['ad_exch_revenue'] += round(($val11->total_earning)-($val11->total_earning*$cmsSharePro),5);
				@$testDataCheck[$val11->_id]['ad_exch_date'] = $val11->_id;
			}
		}
		if(!empty($resultDatalast10_AD)){
			foreach ($resultDatalast10_AD as $key12 => $val12) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val12->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val12->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val12->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val12->total_earning)-($val12->total_earning*$cmsShareAD),5);
				$last_Data['ad_exch_date'] = $val12->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val12->_id]['ad_request'] += $val12->totalad_requests;
				@$testDataCheck[$val12->_id]['ad_exch_impression'] += $val12->totalad_imp;
				@$testDataCheck[$val12->_id]['ad_exch_clicks'] += $val12->total_click;
				@$testDataCheck[$val12->_id]['ad_exch_revenue'] += round(($val12->total_earning)-($val12->total_earning*$cmsShareAD),5);
				@$testDataCheck[$val12->_id]['ad_exch_date'] = $val12->_id;
			}
		}
		if(!empty($testDataCheck)){
			$count =0;
			foreach ($testDataCheck as $key12 => $val13) {
				$allData[$count] = $val13;
				$count++;
			}
		}
		$cmsShare = 0; #because cmsShare already deduct
		// echo print_r($last_lastData);
		// echo print_r($last_Data);die;
		$dataTop = prepareDataTop($cmsShare,$last_lastData,$last_Data,$allData); 
		
		if(!empty($dataTop)){
			#set response code - 200 ok
			http_response_code(200);
	  
			#tell the user
			echo json_encode(array("datatop"=>$dataTop,"status_code"=>200));
		}else{
			#set response code - 422 validation error
			http_response_code(422);
	  
			#tell the user
			echo json_encode(array("message" => "No Data Found!","status_code"=>422));
		}
	}else{
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
    echo json_encode(array("message" => "Unable to get publisher dashboard. Data is incomplete.","status_code"=>400));
}

function prepareDataTop($cmsShare,$last20days,$last10days,$allData){
	
	$todayDate = date('Y-m-d');
	$last_10days = date('Y-m-d', strtotime("-9 days"));
	$last_20daystart = date('Y-m-d', strtotime("-19 days"));
	$last_20daysend = date('Y-m-d', strtotime("-10 days"));
	$request_array=array();
	$request_array_test=array();
	
	
	
	if(!empty($last10days)){
		if($last10days['ad_exch_revenue'] > 0){
			$request_array['earning10day']=number_format(($last10days['ad_exch_revenue']-($last10days['ad_exch_revenue']*$cmsShare)),2);
			$request_array_test['earning10day']=($last10days['ad_exch_revenue']-($last10days['ad_exch_revenue']*$cmsShare));
			$request_array['avgEarning10day']=number_format(($request_array['earning10day']/10),2);
		}else{
			$request_array['earning10day']=0.00;
			$request_array['avgEarning10day']=0.00;
			$request_array_test['earning10day']=0.00;
		}
		if($last10days['ad_exch_impression'] > 0){
			// $request_array['CPM10day']=number_format(($request_array_test['earning10day']/$last10days['ad_exch_impression']*1000),2);
			// $request_array['CTR10day']=number_format(($last10days['ad_exch_clicks']/$last10days['ad_exch_impression']*100),2);
			$request_array['CPM10day']=number_format(floor(($request_array_test['earning10day']/$last10days['ad_exch_impression']*1000)*100)/100, 2);
			$request_array['CTR10day']=number_format(floor(($last10days['ad_exch_clicks']/$last10days['ad_exch_impression']*100)*100)/100, 2);
		}else{
			$request_array['CPM10day']=0.00;
			$request_array['CTR10day']=0.00;
		}
		if($last10days['ad_request'] > 0){
			$request_array['Filled10day']=number_format(($last10days['ad_exch_impression']/$last10days['ad_request'])*100,2);
		}else{
			$request_array['Filled10day']=0.00;
		}
		
	}else{
		$request_array['earning10day']=0.00;
		$request_array['avgEarning10day']=0.00;
		$request_array['CPM10day']=0.00;
		$request_array['CTR10day']=0.00;
		$request_array['Filled10day']=0.00;
		$request_array_test['earning10day']=0.00;
	}
	if(!empty($last20days)){
		
		if($last20days['ad_exch_revenue'] > 0){
			$lastearning10day =($last20days['ad_exch_revenue']-($last20days['ad_exch_revenue']*$cmsShare));
		}else{
			$lastearning10day = 0;
		}
		
		if($last20days['ad_exch_impression'] > 0){
			// $lastCPM10day = number_format(($lastearning10day/$last20days['ad_exch_impression']*1000),2);
			// $lastCTR10day = number_format(($last20days['ad_exch_clicks']/$last20days['ad_exch_impression']*100),2);
			// 
			$lastCPM10day = number_format(floor(($lastearning10day/$last20days['ad_exch_impression']*1000)*100)/100, 2);
			$lastCTR10day = number_format(floor(($last20days['ad_exch_clicks']/$last20days['ad_exch_impression']*100)*100)/100, 2);
			
		}else{
			$lastCPM10day = 0;
			$lastCTR10day =0;
		}
		if($last20days['ad_request'] > 0){
			$lastFilled10day = number_format(($last20days['ad_exch_impression']/$last20days['ad_request'])*100,2);
		}else{
			$lastFilled10day = 0;
		}
		
	}else{
		$lastearning10day =0;
		$lastCPM10day =0;
		$lastCTR10day =0;
		$lastFilled10day =0;
	}
	if($lastearning10day > 0){
		$calEarning = $request_array_test['earning10day'] - $lastearning10day;
		
		if($calEarning > 0){
			$request_array['Earning10dayUpDown'] = "Up";
			$request_array['Earning10dayUpDownPer'] = number_format($calEarning/$lastearning10day*100,2, '.', '');
		}
		if($calEarning < 0){
			$request_array['Earning10dayUpDown'] = "Down";
			
			$request_array['Earning10dayUpDownPer'] = number_format(($lastearning10day-$request_array_test['earning10day'])/$lastearning10day*100,2, '.', '');
			
		}
		if($calEarning == 0){
			$request_array['Earning10dayUpDown'] = "None";
			$request_array['Earning10dayUpDownPer'] = 0;
		}
	}else{
		$request_array['Earning10dayUpDown'] = "Up";
		if($request_array['CPM10day'] > 0){
			$request_array['Earning10dayUpDownPer'] = 100;
		}else{
			$request_array['Earning10dayUpDownPer'] = 0;
		}
	}
	if($lastCPM10day > 0){
		$calCPM = $request_array['CPM10day'] - $lastCPM10day;
		if($calCPM > 0){
			$request_array['CPM10dayUpDown'] = "Up";
			// $request_array['CPM10dayUpDownPer'] = number_format($calCPM/$lastCPM10day*100,2);
			
			$request_array['CPM10dayUpDownPer'] = number_format(floor(($calCPM/$lastCPM10day*100)*100)/100, 2);
		}
		if($calCPM < 0){
			$request_array['CPM10dayUpDown'] = "Down";
			// $request_array['CPM10dayUpDownPer'] = number_format(($lastCPM10day-$request_array['CPM10day'])/$lastCPM10day*100,2);
			
			$request_array['CPM10dayUpDownPer'] = number_format(floor((($lastCPM10day-$request_array['CPM10day'])/$lastCPM10day*100)*100)/100, 2);
		}
		if($calCPM == 0){
			$request_array['CPM10dayUpDown'] = "None";
			$request_array['CPM10dayUpDownPer'] = 0;
		}
	}else{
		$request_array['CPM10dayUpDown'] = "Up";
		if($request_array['CPM10day'] > 0){
			$request_array['CPM10dayUpDownPer'] = 100;
		}else{
			$request_array['CPM10dayUpDownPer'] = 0;
		}
	}
	
	if($lastCTR10day > 0){
		$calCTR = $request_array['CTR10day'] - $lastCTR10day;
		if($calCTR > 0){
			$request_array['CTR10dayUpDown'] = "Up";
			// $request_array['CTR10dayUpDownPer'] = number_format($calCTR/$lastCTR10day*100,2);
			$request_array['CTR10dayUpDownPer'] = number_format(floor(($calCTR/$lastCTR10day*100)*100)/100, 2);
		}
		if($calCTR < 0){
			$request_array['CTR10dayUpDown'] = "Down";
			// $request_array['CTR10dayUpDownPer'] = number_format(($lastCTR10day-$request_array['CTR10day'])/$lastCTR10day*100,2);
			$request_array['CTR10dayUpDownPer'] = number_format(floor((($lastCTR10day-$request_array['CTR10day'])/$lastCTR10day*100)*100)/100, 2);
		}
		if($calCTR == 0){
			$request_array['CTR10dayUpDown'] = "None";
			$request_array['CTR10dayUpDownPer'] = 0;
		}
	}else{
		$request_array['CTR10dayUpDown'] = "Up";
		if($request_array['CTR10day'] > 0){
			$request_array['CTR10dayUpDownPer'] = 100;
		}else{
			$request_array['CTR10dayUpDownPer'] = 0;
		}
		
	}
	
	
	if($lastFilled10day > 0){
		$calFilled = $request_array['Filled10day'] - $lastFilled10day;
		
		if($calFilled > 0){
			$request_array['Filled10dayUpDown'] = "Up";
			$request_array['Filled10dayUpDownPer'] = number_format($calFilled/$lastFilled10day*100,2);
		}
		if($calFilled < 0){
			$request_array['Filled10dayUpDown'] = "Down";
			$request_array['Filled10dayUpDownPer'] = number_format(($lastFilled10day-$request_array['Filled10day'])/$lastFilled10day*100,2);
		}
		if($calFilled == 0){
			$request_array['Filled10dayUpDown'] = "None";
			$request_array['Filled10dayUpDownPer'] = 0;
		}
	}else{
		$request_array['Filled10dayUpDown'] = "Up";
		// $request_array['Filled10dayUpDownPer'] = 100;
		if($request_array['Filled10day'] > 0){
			$request_array['Filled10dayUpDownPer'] = 100;
		}else{
			$request_array['Filled10dayUpDownPer'] = 0;
		}
	}
	
	while (strtotime($todayDate) >= strtotime($last_10days)){
		
		$date_array[]=$todayDate;
		$todayDate = date ("Y-m-d", strtotime("-1 day", strtotime($todayDate)));
		
	}
	
	if(!empty($allData)){
		foreach($allData as $key =>$value){
			$revenue[$value['ad_exch_date']] = round(($value['ad_exch_revenue']-($value['ad_exch_revenue']*$cmsShare)),2);
			$dateavail[]=$value['ad_exch_date'];
			// $cpmcal[$value['ad_exch_date']] = number_format((($value['ad_exch_revenue']-($value['ad_exch_revenue']*$cmsShare))/$value['ad_exch_impression'])*1000,2);
			$cpmcal[$value['ad_exch_date']] = number_format(floor(((($value['ad_exch_revenue']-($value['ad_exch_revenue']*$cmsShare))/$value['ad_exch_impression'])*1000)*100)/100, 2);
			// $ctrcal[$value['ad_exch_date']] = number_format(($value['ad_exch_clicks']/$value['ad_exch_impression'])*100,2);
			$ctrcal[$value['ad_exch_date']] = number_format(floor((($value['ad_exch_clicks']/$value['ad_exch_impression'])*100)*100)/100, 2);
			$filledcal[$value['ad_exch_date']] = number_format(($value['ad_exch_impression']/$value['ad_request'])*100,2);
		}
	}
	// print_r($revenue);die;
	foreach($date_array as $date_value){
		if (in_array($date_value, $dateavail)){
			$final_sum_array['earningGraph_data'][$date_value]=$revenue[$date_value];
			$final_sum_array['cpmGraph_data'][$date_value]=$cpmcal[$date_value];
			$final_sum_array['ctrGraph_data'][$date_value]=$ctrcal[$date_value];
			$final_sum_array['filledGraph_data'][$date_value]=$filledcal[$date_value];
		}else{
			$final_sum_array['earningGraph_data'][$date_value]=0;
			$final_sum_array['cpmGraph_data'][$date_value]=0;
			$final_sum_array['ctrGraph_data'][$date_value]=0;
			$final_sum_array['filledGraph_data'][$date_value]=0;
		}
    }
	
	foreach(array_reverse($final_sum_array['earningGraph_data']) as $key=>$valueads){
		$request_array['earningGraph_data']['date'][]=date('j M', strtotime($key));
		$request_array['earningGraph_data']['revenue'][]=$valueads;
	}
	foreach(array_reverse($final_sum_array['cpmGraph_data']) as $key1=>$value1){
		$request_array['cpmGraph_data']['date'][]=date('j M', strtotime($key1));
		$request_array['cpmGraph_data']['revenue'][]=(float)number_format($value1,2); 
	}
	foreach(array_reverse($final_sum_array['ctrGraph_data']) as $key2=>$value2){
		$request_array['ctrGraph_data']['date'][]=date('j M', strtotime($key2));
		$request_array['ctrGraph_data']['revenue'][]=(float)number_format($value2,1);
	}
	foreach(array_reverse($final_sum_array['filledGraph_data']) as $key3=>$value3){
		$request_array['filledGraph_data']['date'][]=date('j M', strtotime($key3));
		$request_array['filledGraph_data']['revenue'][]=(float)number_format($value3,1);
	}
	
	return $request_array;
}
?>