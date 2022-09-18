<?php
#Author BY SY
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
include_once '../../objects/ProRevenue.php';
include_once '../../objects/Top_box_trend.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$pro = new ProRevenue($db,$dbMongoDb);
$topboxTrend = new TopBoxTrend($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->strtdate) &&
    !empty($data->enddate)
    // !empty($data->acc_name) &&
    // !empty($data->new_acc_name)
    
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
     if(!empty($rowShare)){if($rowShare['pub_display_share'] !=0){$cmsShare = $rowShare['pub_display_share']/100;}else{$cmsShare = 15/100;}}else{$cmsShare = 15/100;} 

     #set overview property values
     $pro->uniq_id = $data->uniq_id;
     $pro->strtdate = $data->strtdate;
     $pro->enddate = $data->enddate;
	 
		
		#For Header Bidder Top Data
		$topboxTrend->uniq_id = $data->uniq_id;
		$result_lastlast10days_HB = $topboxTrend->headerTopBox1_HB();
		$resultDatalast20_HB = $result_lastlast10days_HB->toArray();
		$result_last10days_HB = $topboxTrend->headerTopBox2_HB();
		$resultDatalast10_HB = $result_last10days_HB->toArray();
		#For Direct Deal Top Data
		$result_lastlast10days_DD = $topboxTrend->headerTopBox1_DD();
		$resultDatalast20_DD = $result_lastlast10days_DD->toArray();
		$result_last10days_DD = $topboxTrend->headerTopBox2_DD();
		$resultDatalast10_DD = $result_last10days_DD->toArray();
		#For headerTopBox1 Calculation
		$last_lastData =array();
		if(!empty($resultDatalast20_HB)){
			foreach ($resultDatalast20_HB as $key4 => $val4) {
				@$last_lastData['ad_request'] += $val4->totalad_requests;
				@$last_lastData['ad_exch_impression'] += $val4->totalad_imp;
				@$last_lastData['ad_exch_clicks'] += $val4->total_click;
				@$last_lastData['ad_exch_revenue'] += round(($val4->total_earning)-($val4->total_earning*$cmsShare),2);
				@$last_lastData['ad_exch_date'] = $val4->_id;
			}
		}
		if(!empty($resultDatalast20_DD)){
			foreach ($resultDatalast20_DD as $key5 => $val5) {
				@$last_lastData['ad_request'] += $val5->totalad_requests;
				@$last_lastData['ad_exch_impression'] += $val5->totalad_imp;
				@$last_lastData['ad_exch_clicks'] += $val5->total_click;
				@$last_lastData['ad_exch_revenue'] += round(($val5->total_earning)-($val5->total_earning*$cmsShare),2);
				@$last_lastData['ad_exch_date'] = $val5->_id;
			}
		}
		
		#For headerTopBox2 Calculation
		$last_Data =array();
		$allData = array();
		$testDataCheck = array();
		if(!empty($resultDatalast10_HB)){
			foreach ($resultDatalast10_HB as $key10 => $val10) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val10->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val10->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val10->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val10->total_earning)-($val10->total_earning*$cmsShare),2);
				$last_Data['ad_exch_date'] = $val10->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val10->_id]['ad_request'] += $val10->totalad_requests;
				@$testDataCheck[$val10->_id]['ad_exch_impression'] += $val10->totalad_imp;
				@$testDataCheck[$val10->_id]['ad_exch_clicks'] += $val10->total_click;
				@$testDataCheck[$val10->_id]['ad_exch_revenue'] += round(($val10->total_earning)-($val10->total_earning*$cmsShare),2);
				@$testDataCheck[$val10->_id]['ad_exch_date'] = $val10->_id;
			}
		}
		if(!empty($resultDatalast10_DD)){
			foreach ($resultDatalast10_DD as $key11 => $val11) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val11->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val11->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val11->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val11->total_earning)-($val11->total_earning*$cmsShare),2);
				$last_Data['ad_exch_date'] = $val11->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val11->_id]['ad_request'] += $val11->totalad_requests;
				@$testDataCheck[$val11->_id]['ad_exch_impression'] += $val11->totalad_imp;
				@$testDataCheck[$val11->_id]['ad_exch_clicks'] += $val11->total_click;
				@$testDataCheck[$val11->_id]['ad_exch_revenue'] += round(($val11->total_earning)-($val11->total_earning*$cmsShare),2);
				@$testDataCheck[$val11->_id]['ad_exch_date'] = $val11->_id;
			}
		}
		if(!empty($testDataCheck)){
			$count =0;
			foreach ($testDataCheck as $key33 => $val33) {
				$allData[$count] = $val33;
				$count++;
			}
		}
		
		$result_h = $pro->getHBWithDD();
		$result_hb = $result_h['HB_data']->toArray(); 
		$result_dd = $result_h['DD_data']->toArray(); 
		$cmsShareTop=0;
		$dataTop = prepareDataTop($cmsShareTop,$last_lastData,$last_Data,$allData); 
		if(!empty($result_hb) || !empty($result_dd)){
           
			#calculation
			$data = prepareData($result_hb,$result_dd,$cmsShare,$data->strtdate,$data->enddate);
			  
			#set response code - 200 ok
			http_response_code(200);
	  
			#tell the user
			echo json_encode(array("data"=>$data,"datatop"=>$dataTop,"status_code"=>200));
		}else{
			#set response code - 422 validation error
			http_response_code(422);
	  
			#tell the user
			echo json_encode(array("message" => "No Data Found!","datatop"=>$dataTop,"status_code"=>422));
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
    echo json_encode(array("message" => "Unable to get display overview. Data is incomplete.","status_code"=>400));
}

function prepareData($result_hb,$result_dd,$cmsShare,$start,$end){
	#Date Array    
	while (strtotime($end) >= strtotime($start)){
  
     $date_array[]=$start;
     $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
	}
	foreach ($result_hb as $val) {
	
		$request_array1['hbd_graph_data']['date'][]=date('j M', strtotime($val->_id->date)); 
        $revarrayhbd[date('j M', strtotime($val->_id->date))] =round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2);
        
		/*Table Data start*/
		//$tablehbData[date('j M', strtotime($val->_id->date))]['hd_date'] = $val->_id->date; BY AD
		$tablehbData[date('j M', strtotime($val->_id->date))]['hd_date'] = date('j M, Y', strtotime($val->_id->date));
		$tablehbData[date('j M', strtotime($val->_id->date))]['hd_request'] = number_format($val->total_served_count);
		
		// $tablehbData[date('j M', strtotime($val->_id->date))]['hd_cpm'] = round(((($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare))/$val->totalline_lvl_imp)*1000,2);
		$tablehbData[date('j M', strtotime($val->_id->date))]['hd_cpm'] = number_format(floor(((($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare))/$val->totalline_lvl_imp*1000)*100)/100, 2);
		$tablehbData[date('j M', strtotime($val->_id->date))]['hd_impression'] = number_format($val->totalline_lvl_imp);
		$tablehbData[date('j M', strtotime($val->_id->date))]['hd_reveune'] = round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2);
    }
	foreach ($result_dd as $val1) {
	
		$request_array1['dd_graph_data']['date'][]=date('j M', strtotime($val1->_id->date)); 
        $revarraydd[date('j M', strtotime($val1->_id->date))] =round(($val1->direct_revenue)-($val1->direct_revenue*$cmsShare),2);
        
		/*Table Data start*/
		$tableddData[date('j M', strtotime($val1->_id->date))]['dd_date'] = $val1->_id->date;
		$tableddData[date('j M', strtotime($val1->_id->date))]['dd_request'] = number_format($val1->direct_request);
		
		// $tableddData[date('j M', strtotime($val1->_id->date))]['dd_cpm'] = round(((($val1->direct_revenue)-($val1->direct_revenue*$cmsShare))/$val1->direct_impression)*1000,2);
		$tableddData[date('j M', strtotime($val1->_id->date))]['dd_cpm'] = number_format(floor(((($val1->direct_revenue)-($val1->direct_revenue*$cmsShare))/$val1->direct_impression*1000)*100)/100, 2);
		$tableddData[date('j M', strtotime($val1->_id->date))]['dd_impression'] = number_format($val1->direct_impression);
		$tableddData[date('j M', strtotime($val1->_id->date))]['dd_reveune'] = round(($val1->direct_revenue)-($val1->direct_revenue*$cmsShare),2);
    }
		
	
	foreach($date_array as $date_value){
        $date_value=date('j M', strtotime($date_value));
        if (in_array($date_value, $request_array1['hbd_graph_data']['date'])){
			$final_sum_array['hbd_graph_data'][$date_value]=$revarrayhbd[$date_value];
			$table_array['table_data'][$date_value][] = $tablehbData[$date_value];
		}else{
			$final_sum_array['hbd_graph_data'][$date_value]=0;
			$table_array['table_data'][$date_value][] = array("hd_date"=>$date_value,"hd_request"=>0,"hd_cpm"=>0,"hd_impression"=>0,"hd_reveune"=>0);
		}
		if (in_array($date_value, $request_array1['dd_graph_data']['date'])){
			$final_sum_array['dd_graph_data'][$date_value]=$revarraydd[$date_value];
			$table_array['table_data'][$date_value][] = $tableddData[$date_value];
		}else{
			$final_sum_array['dd_graph_data'][$date_value]=0;
			$table_array['table_data'][$date_value][] = array("dd_date"=>$date_value,"dd_request"=>0,"dd_cpm"=>0,"dd_impression"=>0,"dd_reveune"=>0);
		}
    }

	foreach($table_array['table_data'] as $key =>$valye1){
		$request_array22['all_table_data'][$key] = call_user_func_array('array_merge', $valye1);
	}
	$total_hd_request = $total_hd_impression = $total_hd_reveune = $total_dd_request = $total_dd_impression = $total_dd_reveune = 0;
	foreach($request_array22['all_table_data'] as $key =>$valye11){
		$request_array['all_table_data'][] = $valye11;
		
		$total_hd_request += (float)str_replace( ',', '', $valye11['hd_request']);
		$total_hd_impression += (float)str_replace( ',', '', $valye11['hd_impression']);
		$total_hd_reveune += $valye11['hd_reveune'];
		$total_dd_request += (float)str_replace( ',', '', $valye11['dd_request']);
		$total_dd_impression += (float)str_replace( ',', '', $valye11['dd_impression']);
		$total_dd_reveune += $valye11['dd_reveune'];
		
	}
	
	
	$request_array['all_table_data'][] = array("hd_date"=>"Total","hd_request"=>number_format($total_hd_request),"hd_cpm"=>number_format(floor(($total_hd_reveune/$total_hd_impression*1000)*100)/100, 2),"hd_impression"=>number_format($total_hd_impression),"hd_reveune"=>$total_hd_reveune,"dd_date"=>"Total","dd_request"=>number_format($total_dd_request),"dd_cpm"=>number_format(floor(($total_dd_reveune/$total_dd_impression*1000)*100)/100, 2),"dd_impression"=>number_format($total_dd_impression),"dd_reveune"=>$total_dd_reveune);
    // print_r($request_array['all_table_data']);die;
	foreach($final_sum_array['hbd_graph_data'] as $keyhbd=>$valueahbd){
		$request_array['hbd_graph_data']['date'][]=$keyhbd;
		$request_array['hbd_graph_data']['tot_revenue'][]=$valueahbd;
    }
	foreach($final_sum_array['dd_graph_data'] as $keydd=>$valueadd){
		$request_array['direct_graph_data']['date'][]=$keydd;
		$request_array['direct_graph_data']['tot_revenue'][]=$valueadd;
    }
	return $request_array; 
}/***calculation function end*****/

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