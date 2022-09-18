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
include_once '../../../config/connection.php';
include_once '../../../objects/Common.php';
include_once '../../../objects/DashboardAuxo.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$dashAuxo = new DashboardAuxo($db);

#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->child_net_code) &&
    !empty($data->strtdate) &&
    !empty($data->enddate) &&
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
				$cmsShare = $rowShare['pub_display_share']/100;
			}else{
				$cmsShare = 15/100;
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
		}else{
			$cmsShare = 15/100;
			$cmsShareApp = 15/100;
			$cmsShareVid = 15/100;
		} 
     
		$dashAuxo->range = $data->range;
		$dashAuxo->strtdate = $data->strtdate;
		$dashAuxo->enddate = $data->enddate;
		$dashAuxo->child_net_code = $data->child_net_code;
		//$result_display = $dashAuxo->getOverview();
		$result_display = $dashAuxo->getAdtype();
	    	
		#set overview property values
		if($data->range == "custom"){
			if($data->strtdate == '' && $data->enddate == ''){
				#set response code - 422 validation error
				http_response_code(422);

				#tell the user
				echo json_encode(array("message" => "Date range invalid!","status_code"=>422));
				exit();
			}
		}
		if(!empty($result_display['Display']) || !empty($result_display['App']) || !empty($result_display['Video'])){
			#calculation
			$dataTable = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_display,$data->strtdate,$data->enddate);

			#set response code - 200 ok
			http_response_code(200);

			#tell the user
			echo json_encode(array("data"=>$dataTable,"status_code"=>200));
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
        echo json_encode(array("message" => "Invalid token","status_code"=>400));
    }
}
#tell the user data is incomplete
else{
	#set response code - 400 bad request
	http_response_code(400);

	#tell the user
	echo json_encode(array("message" => "Unable to get dashboard overview. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_display,$start,$end){

	#Date Range
	$yesterdaydate =date('d M ',strtotime($start));
	$sevendayydate =date('d M, Y',strtotime($end));
	$daterange=$yesterdaydate." - ".$sevendayydate;    
	#Date Array For Zero    
	while (strtotime($end) >= strtotime($start)){
		 $date_array[]=$end;
		 $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
	}

	#Data Calculation
    $sumadr1 = $count1 = $sumadimr1 = $summadr1 = $sumcovg1 =$sumecpmx1=$sumectr1=$sumerevenue1=0;
	

    foreach($result_display as $key => $rowcontent) {
        foreach($rowcontent as $val){
        	
            if($key == 'Display'){
            	if(in_array($val['date'],$date_array)){
					@$revenue[$val['date']] = round(($val['revenue']-($val['revenue']*$cmsShare)),2);
			        @$resArr['adr'][$val['date']] += $val['adr'];
					@$resArr['adimr'][$val['date']] += $val['adimr'];
					@$resArr['madr'][$val['date']] += $val['madr'];
					@$resArr['clicks'][$val['date']] += $val['clicks'];
					@$resArr['revenue'][$val['date']] += round(($val['revenue']-($val['revenue']*$cmsShare)),2);
            	
					
                     
				}else{
					@$revenue[$val['date']] = 0;
					@$resArr['adr'][$val['date']] = 0;
					@$resArr['adimr'][$val['date']] = 0;
					@$resArr['madr'][$val['date']] = 0;
					@$resArr['clicks'][$val['date']] = 0;
					@$resArr['revenue'][$val['date']] = 0;
				}
			}
			if($key == 'App'){
				if(in_array($val['date'],$date_array)){
					@$revenue[$val['date']] += (float)round(($val['revenue']-($val['revenue']*$cmsShareApp)),2);
					
					@$resArr['adr'][$val['date']] += $val['adr'];
					@$resArr['adimr'][$val['date']] += $val['adimr'];
					@$resArr['madr'][$val['date']] += $val['madr'];
					@$resArr['clicks'][$val['date']] += $val['clicks'];
					@$resArr['revenue'][$val['date']] += (float)round(($val['revenue']-($val['revenue']*$cmsShareApp)),2);
				}else{
					@$revenue[$val['date']] += 0;
					@$resArr['adr'][$val['date']] += 0;
					@$resArr['adimr'][$val['date']] += 0;
					@$resArr['madr'][$val['date']] += 0;
					@$resArr['clicks'][$val['date']] += 0;
					@$resArr['revenue'][$val['date']] += 0;
				}
			}  	
			if($key == 'Video'){
			 if(in_array($val['date'],$date_array)){
					@$revenue[$val['date']] += (float)round(($val['revenue']-($val['revenue']*$cmsShareVid)),2);
					@$resArr['adr'][$val['date']] += $val['adr'];
					@$resArr['adimr'][$val['date']] += $val['adimr'];
					@$resArr['madr'][$val['date']] += $val['madr'];
					@$resArr['clicks'][$val['date']] += $val['clicks'];
					@$resArr['revenue'][$val['date']] += (float)round(($val['revenue']-($val['revenue']*$cmsShareVid)),2);
				}else{
					@$revenue[$val['date']] += 0;
					@$resArr['adr'][$val['date']] += 0;
					@$resArr['adimr'][$val['date']] += 0;
					@$resArr['madr'][$val['date']] += 0;
					@$resArr['clicks'][$val['date']] += 0;
					@$resArr['revenue'][$val['date']] += 0;
				}
			} 
        }
    }
   
	#blank date to zero
	foreach($date_array as $date_value){
		if(in_array($date_value, array_keys($revenue))){
			$final_sum_array['graph_data'][$date_value]=$revenue[$date_value];
		}else{
			$final_sum_array['graph_data'][$date_value]=0;
		}
		#blank date to zero for table data
		if(in_array($date_value, array_keys($resArr['adr']))){
			$resArr['adr'][$date_value]=$resArr['adr'][$date_value];
		}else{
			$resArr['adr'][$date_value]=0;
		}
		if(in_array($date_value, array_keys($resArr['adimr']))){
			$resArr['adimr'][$date_value]=$resArr['adimr'][$date_value];
		}else{
			$resArr['adimr'][$date_value]=0;
		}
		if(in_array($date_value, array_keys($resArr['madr']))){
			$resArr['madr'][$date_value]=$resArr['madr'][$date_value];
		}else{
			$resArr['madr'][$date_value]=0;
		}
		if(in_array($date_value, array_keys($resArr['clicks']))){
			$resArr['clicks'][$date_value]=$resArr['clicks'][$date_value];
		}else{
			$resArr['clicks'][$date_value]=0;
		}
		if(in_array($date_value, array_keys($resArr['revenue']))){
			$resArr['revenue'][$date_value]=$resArr['revenue'][$date_value];
		}else{
			$resArr['revenue'][$date_value]=0;
		}
    }
	foreach($resArr as $key1 => $value1){
		$value1 = array_reverse($value1);
		foreach($value1 as $key2 => $val2){
			$tableData[$key2]['date'] = $key2;
			if($key1 == 'adr'){
				$tableData[$key2]['adr'] = $val2;
			}
			if($key1 == 'adimr'){
				$tableData[$key2]['adimr'] = $val2;
			}
			if($key1 == 'madr'){
				$tableData[$key2]['madr'] = $val2;
			}
			if($key1 == 'clicks'){
				$tableData[$key2]['clicks'] = $val2;
			}
			if($key1 == 'revenue'){
				$tableData[$key2]['revenue'] = $val2;
			}
		}
	}
	$totadr = $totadimr = $totmadr = $totclicks = $totrevenue = $totcovg = $totclicks_madr = $totfillrate = $totecpmx = 0;
	$key3 = 0;
	ksort($tableData);
	 
	foreach($tableData as $key4 => $value3){
		$request_array['content_table_data'][$key3]['date'] = date('j M, Y', strtotime($value3['date'])); 
		$request_array['content_table_data'][$key3]['adr'] = number_format($value3['adr']);
		$request_array['content_table_data'][$key3]['adimr'] = number_format($value3['adimr']);
		// $request_array['content_table_data'][$key3]['madr'] = number_format($value3['madr']);
		// $request_array['content_table_data'][$key3]['clicks'] = number_format($value3['clicks']);
		
		if($value3['adr'] > 0 ){
			$request_array['content_table_data'][$key3]['fillrate'] = number_format(($value3['adimr']/$value3['adr'])*100,1, '.', '');
			$request_array['content_table_data'][$key3]['covg'] = number_format(($value3['madr']*100)/$value3['adr'],1, '.', '');
		}else{
			$request_array['content_table_data'][$key3]['covg'] = 0;
			$request_array['content_table_data'][$key3]['fillrate'] = 0;
		}
		if($value3['adimr'] > 0 ){
			$request_array['content_table_data'][$key3]['clicks_madr'] = number_format(($value3['clicks']/$value3['adimr'])*100,1, '.', '');
			// $request_array['content_table_data'][$key3]['ecpmx'] = number_format(($value3['revenue']/$value3['adimr'])*1000,1, '.', '');
			$request_array['content_table_data'][$key3]['ecpmx'] = number_format(floor((($value3['revenue']/$value3['adimr'])*1000)*100)/100, 2);
		}else{
			$request_array['content_table_data'][$key3]['clicks_madr'] = 0;
			$request_array['content_table_data'][$key3]['ecpmx'] = 0;
		}
		$request_array['content_table_data'][$key3]['revenue'] = $value3['revenue'] > 0 ? number_format($value3['revenue'],2, '.', ''): 0;
		$totadr += $value3['adr'];
		$totadimr += $value3['adimr'];
		$totmadr += $value3['madr'];
		$totclicks += $value3['clicks'];
		$totrevenue += $value3['revenue'];
		if($totadr > 0 ){
			$totcovg = number_format(($totmadr*100/$totadr),1, '.', '');
			$totfillrate = number_format(($totadimr/$totadr)*100,1, '.', '');
		}else{
			$totcovg = 0;
			$totfillrate = 0;
		}
		if($totadimr > 0 ){
			$totclicks_madr = number_format(($totclicks/$totadimr)*100,1, '.', '');
			// $totecpmx = number_format(($totrevenue/$totadimr)*1000,2, '.', '');
			$totecpmx = number_format(floor((($totrevenue/$totadimr)*1000)*100)/100, 2);
		}else{
			$totclicks_madr = 0;
			$totecpmx = 0;
		}
		#total row Data
		$key3++;
	}
	// echo "<pre>";
	// print_r($request_array);die;
	$request_array['content_table_data'][] = array("date"=>"Total","adr"=>number_format($totadr),"adimr"=>number_format($totadimr),"revenue"=>(float)number_format($totrevenue,2, '.', ''),"covg"=>$totcovg,"fillrate"=>$totfillrate,"clicks_madr"=>$totclicks_madr,"ecpmx"=>$totecpmx);
	
	
	foreach(array_reverse($final_sum_array['graph_data']) as $keyads=>$valueads){
		$request_array['content_graph_date']['date'][]=date('j M', strtotime($keyads));
		$request_array['content_graph_date']['revenue'][]=(float)number_format($valueads,2, '.', '');
    }

    return $request_array; 
}/***calculation function end*****/

?>