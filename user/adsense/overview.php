<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '-1');
set_time_limit(0);
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
include_once '../../objects/Adsense.php';
include_once '../../objects/Top_box_trend.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$adsense = new Adsense($db,$dbMongoDb);
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
    !empty($data->enddate) &&
    !empty($data->adsense_id)
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    
     #set overview property values
     $adsense->uniq_id = $data->uniq_id;
     //$adsense->uniq_id = "CHEE_120618_174513";
     $adsense->strtdate = $data->strtdate;
     $adsense->enddate = $data->enddate;
     $result_adsen = $adsense->getOverview();
     
     $result_adsense = $result_adsen->toArray(); 
		#For Adsense Top Data
		$topboxTrend->uniq_id = $data->uniq_id;
		$result_lastlast10days_AD = $topboxTrend->headerTopBox1_AD();
		$resultDatalast20_AD = $result_lastlast10days_AD->toArray();
		$result_last10days_AD = $topboxTrend->headerTopBox2_AD();
		$resultDatalast10_AD = $result_last10days_AD->toArray();
		
		#For headerTopBox1 Calculation
		$last_lastData =array();
		$cmsShareAD =0;
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
		if(!empty($resultDatalast10_AD)){
			foreach ($resultDatalast10_AD as $key12 => $val12) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val12->totalad_requests;
				@$last_Data['ad_exch_impression'] += $val12->totalad_imp;
				@$last_Data['ad_exch_clicks'] += $val12->total_click;
				@$last_Data['ad_exch_revenue'] += round(($val12->total_earning)-($val12->total_earning*$cmsShareAD),5);
				$last_Data['ad_exch_date'] = $val12->_id;
				
				# creating All 10 days Data 
				@$testDataCheck[$val12->_id]['ad_request'] = $val12->totalad_requests;
				@$testDataCheck[$val12->_id]['ad_exch_impression'] = $val12->totalad_imp;
				@$testDataCheck[$val12->_id]['ad_exch_clicks'] = $val12->total_click;
				@$testDataCheck[$val12->_id]['ad_exch_revenue'] = round(($val12->total_earning)-($val12->total_earning*$cmsShareAD),5);
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
		$dataTop = prepareDataTop($cmsShare,$last_lastData,$last_Data,$allData); 
     if(!empty($result_adsense)){
           
          #calculation
          $dataTable = prepareData($result_adsense,$data->strtdate,$data->enddate);
          
          #set response code - 200 ok
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("data"=>$dataTable,"datatop"=>$dataTop,"status_code"=>200));
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
        echo json_encode(array("message" => "Invalid token","status_code"=>400));
      }
}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to get adsense overview. Data is incomplete.","status_code"=>400));
}
function prepareData($result_adsense,$start,$end){
#Date Array    
while (strtotime($end) >= strtotime($start)) 
{
	// $date_array[]=$end;
	// $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
	$date_array[]=$start;
	$start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
}    
foreach ($result_adsense as $val) {

        $dateavail[]=$val->_id;

        $res_array['ads_table_data'][$val->_id] = array(
            'date'=>date('j M, Y', strtotime($val->_id)),
            'adr'=>$val->totalad_requests,
            'adimr'=>$val->totalad_imp,
            'madr'=>$val->totalmatchad_requests,
            'fillrate'=>number_format($val->totalad_imp/$val->totalad_requests*100,1),
            'covg'=>$val->totalmatchad_requests > 0 ? number_format(($val->totalmatchad_requests*100)/$val->totalad_requests,1) :0,
            'ctr'=>($val->totalad_imp > 0 ? number_format(($val->total_click)/($val->totalad_imp)*100,1) : '0'),
            'revenue_cmsShare'=>number_format($val->total_earning,2),
            'ecpmx'=>$val->totalad_imp > 0 ? number_format(floor(($val->total_earning/$val->totalad_imp*1000)*100)/100, 2) :0);
            
        
         #Total row for first slide 
        @$total_array['adr']+=$val->totalad_requests;
        @$total_array['adimr']+=$val->totalad_imp;
        @$total_array['madr']+=$val->totalmatchad_requests;
        @$total_array['fillrate']=number_format($total_array['adimr']/$total_array['adr']*100,1);
        @$total_array['clicks']+=$val->total_click;
        @$total_array['covg'] = $total_array['madr'] > 0 ? number_format(($total_array['madr']*100)/$total_array['adr'],1) :0;
        @$total_array['ctr'] = $total_array['adimr'] > 0 ? number_format($total_array['clicks']/$total_array['adimr']*100,1):0;
        @$total_array_full['revenue_cmsShare'] += round($val->total_earning,2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimr']*1000)*100)/100, 2) : 0.00;

        $date[] =date('Y-m-d', strtotime($val->_id));  
        $revarray[date('Y-m-d', strtotime($val->_id))] = (float)round($val->total_earning,2);
        
    }

#if any date data is missing in MongoDB
$result=array_diff($date_array, $dateavail);

foreach($result as $restarr)  {
        $res_array['ads_table_data'][$restarr]= array(
            'date'=>date('j M, Y', strtotime($restarr)),
            'adr'=>0,
            'adimr'=>0,
            'madr'=>0,
            'fillrate'=>0,
            'covg'=>0,
            'ctr'=>0,
            'ecpmx'=>0,
            'revenue_cmsShare'=>0
        );
}
// krsort($res_array['ads_table_data']);
ksort($res_array['ads_table_data']);
$request_array['ads_table_data'] = array_values($res_array['ads_table_data']);
$request_array['ads_table_data'][] = array("date"=>"Total","adr"=>$total_array['adr'],"adimr"=>$total_array['adimr'],"madr"=>$total_array['madr'],"fillrate"=>$total_array['fillrate'],"covg"=>$total_array['covg'],"ctr"=>$total_array['ctr'],"revenue_cmsShare"=>$total_array['revenue_cmsShare'],"ecpmx"=>$total_array['ecpm']);


#blank date to zero
foreach($date_array as $date_value)
    {
     if (in_array($date_value, $date))
            {
                $final_sum_array['adsense'][$date_value]=$revarray[$date_value];
            }
            else
            {
                $final_sum_array['adsense'][$date_value]=0;
            }
    }
   
foreach($final_sum_array['adsense'] as $keyads=>$valueads)
    {
                $request_array['ads_graph_data']['date'][]=date('j M', strtotime($keyads));
                $request_array['ads_graph_data']['revenue'][]=(float)round($valueads,2);
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