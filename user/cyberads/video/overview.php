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
include_once '../../../config/connection.php';
include_once '../../../objects/Common.php';
include_once '../../../objects/Video.php';
include_once '../../../objects/Top_box_trend.php';
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$video = new Video($db);
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
     if(!empty($rowShare)){if($rowShare['pub_video_share'] !=0){$cmsShare = $rowShare['pub_video_share']/100;}else{$cmsShare = 15/100;}}else{$cmsShare = 15/100;} 
		
		#For Video Top Data
		$topboxTrend->child_net_code = $data->child_net_code;
		$topboxTrend->uniq_id = $data->uniq_id;
		$result_lastlast10days_video = $topboxTrend->headerTopBox1_video();
		$result_lastlast10days_video = $result_lastlast10days_video->get_result();
        $resultDatalast20_video = $result_lastlast10days_video->fetch_all(MYSQLI_ASSOC);
		$result_last10days_video = $topboxTrend->headerTopBox2_video();
		$result_last10days_video = $result_last10days_video->get_result();
        $resultDatalast10_video = $result_last10days_video->fetch_all(MYSQLI_ASSOC);
		
		#For headerTopBox1 Calculation
		$last_lastData =array();
		if(!empty($resultDatalast20_video)){
			foreach ($resultDatalast20_video as $key3 => $val3) {
				@$last_lastData['ad_request'] += $val3['ad_request'];
				@$last_lastData['ad_exch_impression'] += $val3['ad_exch_impression'];
				@$last_lastData['ad_exch_clicks'] += $val3['ad_exch_clicks'];
				@$last_lastData['ad_exch_revenue'] += round(($val3['ad_exch_revenue'])-($val3['ad_exch_revenue']*$cmsShare),5);
				@$last_lastData['ad_exch_date'] = $val3['ad_exch_date'];
			}
		}
		#For headerTopBox2 Calculation
		$last_Data =array();
		$allData = array();
		$testDataCheck = array();
		if(!empty($resultDatalast10_video)){
			foreach ($resultDatalast10_video as $key9 => $val9) {
				# creating Sum 10 days Data
				@$last_Data['ad_request'] += $val9['ad_request'];
				@$last_Data['ad_exch_impression'] += $val9['ad_exch_impression'];
				@$last_Data['ad_exch_clicks'] += $val9['ad_exch_clicks'];
				@$last_Data['ad_exch_revenue'] += round(($val9['ad_exch_revenue'])-($val9['ad_exch_revenue']*$cmsShare),5);
				@$last_Data['ad_exch_date'] = $val9['ad_exch_date'];
				
				# creating All 10 days Data 
				@$testDataCheck[$val9['ad_exch_date']]['ad_request'] = $val9['ad_request'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_impression'] = $val9['ad_exch_impression'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_clicks'] = $val9['ad_exch_clicks'];
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_revenue'] = round(($val9['ad_exch_revenue'])-($val9['ad_exch_revenue']*$cmsShare),5);
				@$testDataCheck[$val9['ad_exch_date']]['ad_exch_date'] = $val9['ad_exch_date'];
			}
		}
		if(!empty($testDataCheck)){
			$count =0;
			foreach ($testDataCheck as $key12 => $val13) {
				$allData[$count] = $val13;
				$count++;
			}
		}
		$cmsShareTop = 0;
		$dataTop = prepareDataTop($cmsShareTop,$last_lastData,$last_Data,$allData);
		
		
		
		
     #set overview property values
     if($data->range == "custom"){
        if($data->strtdate == '' && $data->enddate == ''){
           #set response code - 422 validation error
           http_response_code(422);
  
           #tell the user
          echo json_encode(array("message" => "Date range invalid!","datatop"=>$dataTop,"status_code"=>422));
          exit();
        }
     }
     $video->range = $data->range;
     $video->strtdate = $data->strtdate;
     $video->enddate = $data->enddate;
     $video->child_net_code = $data->child_net_code;
     //$result_video = $video->getOverview();
      $result_video = $video->getAdtype();
     if(!empty($result_video)){
           
          // $header->child_net_code = $data->child_net_code;   
          // $header->type = "video";   
          // $result_top = $header->headerRevenue();
          // $stmt_top = $result_top->get_result();
          // $rowtopheaderNew = $stmt_top->fetch_array(MYSQLI_ASSOC);
          
          #calculation
          $dataTable = prepareData($cmsShare,$result_video,$data->strtdate,$data->enddate);
          
          
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
    echo json_encode(array("message" => "Unable to get video overview. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_video,$start,$end){
	#Date Array    
	while (strtotime($end) >= strtotime($start)) 
	{
	     $date_array[]=$end;
	     $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
	}
    

    foreach ($result_video as $key => $rowcontent) {
       $date =  $rowcontent['date'];
        @$sumuplevel_array[$date]['date'] = date('j M, Y', strtotime($rowcontent['date']));
        @$sumuplevel_array[$date]['adr']+=$rowcontent['adr'];
        @$sumuplevel_array[$date]['adimr']+=$rowcontent['adimr'];
        @$sumuplevel_array[$date]['madr']+=$rowcontent['madr'];
        @$sumuplevel_array[$date]['fillrate'] = number_format($sumuplevel_array[$date]['adimr']/$sumuplevel_array[$date]['adr']*100,1);
        @$sumuplevel_array_dev[$date]['clicks']+=$rowcontent['clicks'];
        @$sumuplevel_array[$date]['covg'] = $sumuplevel_array[$date]['madr'] > 0 ? number_format(($sumuplevel_array[$date]['madr']*100)/$sumuplevel_array[$date]['adr'],1) :0.0;
        @$sumuplevel_array[$date]['clicks_madr'] = $sumuplevel_array[$date]['adimr'] > 0 ? number_format($sumuplevel_array_dev[$date]['clicks']/$sumuplevel_array[$date]['adimr']*100,1):0.0;
        @$sumuplevel_array[$date]['revenue_cmsShare'] += round($rowcontent['revenue']-($rowcontent['revenue']*$cmsShare),2);
        @$sumuplevel_array[$date]['ecpmx'] = $sumuplevel_array[$date]['adimr'] > 0 ? number_format(floor(($sumuplevel_array[$date]['revenue_cmsShare']/$sumuplevel_array[$date]['adimr']*1000)*100)/100, 2) : 0.00;
        
         #Total row for first slide 
        @$total_array['adr']+=$rowcontent['adr'];
        @$total_array['adimr']+=$rowcontent['adimr'];
        @$total_array['madr']+=$rowcontent['madr'];
        @$total_array['clicks']+=$rowcontent['clicks'];
        @$total_array['covg'] = $total_array['madr'] > 0 ? number_format(($total_array['madr']*100)/$total_array['adr'],1) :0.00;
        @$total_array['clicks_madr'] = $total_array['adimr'] > 0 ? number_format($total_array['clicks']/$total_array['adimr']*100,1):0.00;
        @$total_array['fillrate'] = $total_array['adr'] > 0 ? number_format($total_array['adimr']/$total_array['adr']*100,1):0.00;
        @$total_array['revenue_cmsShare'] += $rowcontent['revenue']-($rowcontent['revenue']*$cmsShare);
        @$total_array['ecpm'] = $total_array['adimr'] > 0 ? number_format(floor(($total_array['revenue_cmsShare']/$total_array['adimr']*1000)*100)/100, 2) : 0.00;

       
        @$revenue[$rowcontent['date']] += (float)number_format(($rowcontent['revenue']-($rowcontent['revenue']*$cmsShare)),2,'.','');
        @$dateavail[]=$rowcontent['date'];
        
    }

 #if any date data is missing so zero insert
$result=array_diff($date_array, $dateavail);

foreach($result as $restarr)  {
        $sumuplevel_array[$restarr]= array(
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
   

ksort($sumuplevel_array);
$request_array['content_table_data'] = array_values($sumuplevel_array);
$request_array['content_table_data'][] = array("date"=>"Total","adr"=>$total_array['adr'],"adimr"=>$total_array['adimr'],"madr"=>$total_array['madr'],"covg"=>$total_array['covg'],"clicks_madr"=>$total_array['clicks_madr'],"fillrate"=>$total_array['fillrate'],"revenue_cmsShare"=>number_format($total_array['revenue_cmsShare'],2),"ecpmx"=>$total_array['ecpm']);

    
    #blank date to zero
foreach($date_array as $date_value)
    {
     if (in_array($date_value, $dateavail))
            {
                $final_sum_array['video'][$date_value]=$revenue[$date_value];
            }
            else
            {
                $final_sum_array['video'][$date_value]=0;
            }
    }
   
foreach(array_reverse($final_sum_array['video']) as $keyads=>$valueads)
    {
                $request_array['content_graph_date']['date'][]=date('j M', strtotime($keyads));
                $request_array['content_graph_date']['revenue'][]=(float)round($valueads,2);
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