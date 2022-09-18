<?php
#Author BY SS
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
include_once '../../objects/AnalyticsContent.php';

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
$previous_week = strtotime("-1 week +1 day");
$start_week = strtotime("last monday midnight",$previous_week);
$end_week = strtotime("next sunday",$start_week);
$start_week = date("Y-m-d",$start_week);
$end_week = date("Y-m-d",$end_week);
$daterangelastweek=date("j M", strtotime($start_week)).' - '.date("j M, Y", strtotime($end_week));	
	
	
#Start date and end date
if(date('H') > 03) {
    $start_date = date("Y-m-d",strtotime("-7 days"));
    $end_date =  date("Y-m-d",strtotime("-1 days"));
}
else{
    $start_date = date("Y-m-d",strtotime("-8 days"));
    $end_date =  date("Y-m-d",strtotime("-2 days"));
}    

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
     #set content property values
     $analytics->uniq_id = $data->uniq_id;
     $analytics->account_id = $data->account_id;
     $analytics->child_net_code = $data->child_net_code;
     $analytics->strtdate = $start_date;
     $analytics->enddate = $end_date;
     $result_content = $analytics->getContent(); 
     $result_ads = $result_content[0]->toArray(); 
     $result_dis = $result_content[1]; 
     $result_siteUrl = $result_content[2]; 
	 
	 $analytics->start_week = $start_week;
     $analytics->end_week = $end_week;
     $result_HighBounce = $analytics->getHighBounce(); 
     $result_hbounce = $result_HighBounce->toArray();
     // echo "<pre>";
     // print_r($result_siteUrl);die;
    if(!empty($result_ads) || !empty($result_dis)){
           $dataresult = array();
         #calculation
          $dataresult['section1']= prepareData($result_ads,$result_dis,$result_siteUrl,$data->uniq_id,$data->account_id,$dbMongoDb,$start_date,$end_date);
		  if(!empty($result_hbounce)){
			// $dataresult['tabledata_lp_highbounce'] = prepareData1($result_hbounce,$daterangelastweek);
			// $dataresult['daterange_highbounce'] = $daterangelastweek; 
			$dataresult['section2']=prepareData1($result_hbounce,$daterangelastweek); 
		  }else{
			// $dataresult['tabledata_lp_highbounce'] = array();
			// $dataresult['daterange_highbounce'] = $daterangelastweek;
			
			$dataresult['section2']=array('tabledata_lp_highbounce'=>array(),'categories'=>array(),'data'=>array(),'data1'=>array(),'data2'=>array(),'topdata'=>array('Pageviews'=>'','Avgsession'=>'','Avgbounce'=>'','landing_daterange'=>$daterangelastweek),'daterange_highbounce'=>$daterangelastweek);
		  }
			
          #set response code - 200 ok
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("data"=>$dataresult,"status_code"=>200));
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
    echo json_encode(array("message" => "Unable to get key insights. Data is incomplete.","status_code"=>400));
}

function prepareData($cursor_lplist,$result_dis,$siteurl,$uniqid,$accountid,$manager,$start_date,$end_date){

$adx_avg_imp_per_page = round(($result_dis['adimr']*($result_dis['ecpmx']/7))/1000,2);    
$ads_imp_total=0;
$ads_pageview_total=0;
$ads_earn_total=0;
foreach($cursor_lplist as $ads){
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


foreach ($siteurl as $profileids) {
    $profileid = $profileids['profile_id'];
    $siteurl['site_url'] = $profileids['site_url'];
    $csvfile = $uniqid.".consumerinsight_".$profileid;
    $csvbasicfile = $uniqid.".consumerinsight_basic_".$profileid;

    $where          = ['ACCOUNT_ID'=>['$eq'=>(int)$accountid]];
    $query = new MongoDB\Driver\Query($where);
    $resultL = $manager->executeQuery($csvfile, $query);
    $basicresultL = $manager->executeQuery($csvbasicfile, $query);
    $resultL1 = $manager->executeQuery($csvfile, $query);

  
    foreach ($resultL1 as $val) {
        @$total_pageviews += ($val->PAGE_VIEWS >=0 ? $val->PAGE_VIEWS : 0);
    }


    foreach ($resultL as $val) {

        $toplandingpages_agregate_pageview = 0;
        $toplandingpages_agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
       @$toplandingpages['pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($toplandingpages_agregate_pageview*$average_rev)/100,2);
        @$toplandingpages['pageviews'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += $val->PAGE_VIEWS;
        @$toplandingpages['pageload'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += $val->AVG_PAGELOADTIME;

        @$toplandingpages['occurence_count'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] ++;

    }
}
arsort($toplandingpages['pageview_percent']);
$counting = 1; 

foreach($toplandingpages['pageview_percent'] as $topkey=> $topvalue) {
    if($counting<=10) {
        $top_pages_of_site[] =array(
            "siteurl"=>$topkey,
            "earning"=>round($topvalue,2),
            "pageviews"=> number_format($toplandingpages['pageviews'][$topkey]),
            "pageload"=> round(($toplandingpages['pageload'][$topkey])/$toplandingpages['occurence_count'][$topkey],2),
        );
        $counting++; 
    }
}
// $counting1 = 1; 
// foreach($toplandingpages['pageview_percent'] as $topkey=> $topvalue) {
    // if($counting1<=7) {
        // $top_landing_pages_of_site[] =array(
            // "siteurl"=>$topkey,
        // );
        // $counting1++; 
    // }
// }

foreach($visitorname as $v) {
    foreach ($mediumname as $m) {
        $urlcount = 1;
        // $totaluserinmedium[$v][$m] = array('users'=>$userinArray[$v][$m]['user']);
        $countingkey=0;
        $countingkeys=0;
        $countingkey = count($userinArray[$v][$m]['ideal_pageview_percent']);
        if($countingkey >=10) {
            $countingkeys =10;
        }
        else{
            $countingkeys =$countingkey;
        }

    }
}


function CurrencyFormat( $n, $precision = 1 ) {
    if ($n < 900) {
        // 0 - 900
        $n_format = number_format($n, $precision);
        $suffix = '';
    } else if ($n < 900000) {
        // 0.9k-850k
        $n_format = number_format($n / 1000, $precision);
        $suffix = 'K';
    } else if ($n < 900000000) {
        // 0.9m-850m
        $n_format = number_format($n / 1000000, $precision);
        $suffix = 'M';
    } else if ($n < 900000000000) {
        // 0.9b-850b
        $n_format = number_format($n / 1000000000, $precision);
        $suffix = 'B';
    } else {
        // 0.9t+
        $n_format = number_format($n / 1000000000000, $precision);
        $suffix = 'T';
    }
  // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
  // Intentionally does not affect partials, eg "1.50" -> "1.50"
    if ( $precision > 0 ) {
        $dotzero = '.' . str_repeat( '0', $precision );
        $n_format = str_replace( $dotzero, '', $n_format );
    }
    return $n_format . $suffix;
}
// sort($userinArray);

foreach($mediumwise as $mkey=>$mvalue) {
    $final_mediumwise[] = array(
        'visitor'=>str_replace(" ","",$mkey),
        'value'=>array_values($mvalue)
    );
}

$daterange[] = date("j M",strtotime($start_date))." - ".date("j M, Y",strtotime($end_date));
// print_r($daterange);die;
$final_array = array(
    "top_landingpage"=>$top_pages_of_site,
    // "top_7landingpage"=>$top_landing_pages_of_site,
    // "userinarray"=>$userinArray,
    // 'totaluserinmedium'=>$totaluserinmedium,
    // 'mediumwise'=>$final_mediumwise,
    "daterange"=>$daterange
);


   return $final_array; 
}/***calculation function end*****/
function prepareData1($row,$daterangelastweek){
	$inArray = array();
	$exitinArray = array();
	$tot_bounces = $tot_sessions = $tot_sessionDuration = $tot_pageviews = 0;
	$tot_exits = $tot_exit_pageviews = 0;
	foreach($row as $k=>$val) {
		
		$landingPagePath 	= $val->landingPagePath;
		$exitPagePath 		= $val->exitPagePath;
		@$inArray[$landingPagePath][$k]['pageviews'] = $val->pageviews;
		@$inArray[$landingPagePath][$k]['bounces'] = $val->bounces;
		@$inArray[$landingPagePath][$k]['sessions'] = $val->sessions;
		@$inArray[$landingPagePath][$k]['sessionDuration'] 	= $val->sessionDuration;
		@$exitinArray[$exitPagePath][$k]['pageviews'] 	= $val->pageviews;
		@$exitinArray[$exitPagePath][$k]['exits'] 	= $val->exits;
		
		
		if(in_array($landingPagePath,$inArray)){
			
			@$inArray[$landingPagePath][$k]['pageviews'] += $val->pageviews;
			@$inArray[$landingPagePath][$k]['bounces'] += $val->bounces;
			@$inArray[$landingPagePath][$k]['sessions'] += $val->sessions;
			@$inArray[$landingPagePath][$k]['sessionDuration'] 	+= $val->sessionDuration;
		}else{
			@$inArray[$landingPagePath][$k]['landingPagePath'] = $val->landingPagePath;
		}
		if(in_array($exitPagePath,$exitinArray)){
			
			@$exitinArray[$exitPagePath][$k]['pageviews'] 		+= $val->pageviews;
			@$exitinArray[$exitPagePath][$k]['exits'] 			+= $val->exits;
		}else{
			@$exitinArray[$exitPagePath][$k]['exitPagePath'] 	= $val->exitPagePath;
		}
	}				
	foreach($exitinArray as $k=> $arrVal){
	
		$exitdata = array();
		
		foreach ($exitinArray[$k] as $kk=>$v){
			
			$exitdata['exitPagePath'] 		= $v['exitPagePath'];
			@$exitdata['pageviews'] 			+= $v['pageviews'];
			@$exitdata['exits'] 				+= $v['exits'];
		}
		$exitnewArr[$k] = $exitdata; 
	}
	aasort($exitnewArr,"exits");	
	foreach($inArray as $k=> $arrVal){
	
		$data = array();
		
		foreach ($inArray[$k] as $kk=>$v){
			@$data['landingPagePath'] = $v['landingPagePath'];
			@$data['sessionDuration'] 	+= $v['sessionDuration'];
			@$data['pageviews'] += $v['pageviews'];
			@$data['bounces'] += $v['bounces'];
			@$data['sessions'] += $v['sessions']; 
			
		}
		
		$newArr[$k] = $data; 
	}
	function sortByView($a, $b){
	    $a = $a['pageviews'];
	    $b = $b['pageviews'];

	    if ($a == $b) return 0;
	    return ($a > $b) ? -1 : 1;
	}
	usort($newArr, 'sortByView');
		
	$xAxis = array();
	$pageViewsArray = $pageSessionsArray = $pageBouncesArray = array();

	
	$kk = 0;
	foreach($newArr as $k=>$v){
		if($kk==5){
			break;
		}
		if($v['sessions'] >=10) {
			$avgBounceGraph 	= $v['bounces']/$v['sessions'];
		
			$rvaindex = strlen($v['landingPagePath']) > 10 ? mb_substr($v['landingPagePath'],0,10)."..." : $v['landingPagePath'];
			$xAxis[] = $rvaindex;
			$landingpfull[] = $v['landingPagePath'];
			$pageViewsArray[] =  $v['pageviews'];
			$pageBouncesArray[] = round(($avgBounceGraph)*100, 2);
		}
	}
	
	foreach($xAxis as $key => $value){
		$lp_path[]=$value;
	}
	foreach($landingpfull as $key => $value4){
		$lp_path_full[]=$value4;
	}	
	foreach($pageViewsArray as $key => $value1){
		$pageviews[]=$value1;
	}

	foreach($pageBouncesArray as $key => $value3){
		$bounce[]=$value3;
	}

	for($i=0; $i<=4;$i++){
		$tabledata[]=array(
							'pagepath'=>$lp_path_full[$i],
							'pageview'=>number_format($pageviews[$i]),
							'bounce'=>round($bounce[$i],1)
							);
	}

	usort($tabledata, function($a, $b) {
		return $b['bounce'] <=> $a['bounce'];
	});
	
	/*for Landing Page Analysis graph data start*/
	$xAxis1 = array();
	$pageViewsArray1 = $pageSessionsArray1 = $pageBouncesArray1 = array();
	$kk1=0;
	foreach($newArr as $k=>$v){
		if($kk1==10){
			break;
		}
		$avgBounceGraph1 	= $v['bounces']/$v['sessions'];
		$avgSessionGraph1	= $v['sessionDuration']/$v['sessions'];
		
		$rvaindex1 = strlen($v['landingPagePath']) > 10 ? mb_substr($v['landingPagePath'],0,10)."..." : $v['landingPagePath'];
		$xAxis1[] = $rvaindex1;
		$landingpfull1[] = $v['landingPagePath'];
		$pageViewsArray1[] =  $v['pageviews'];
		$pageSessionsArray1[] =  round($avgSessionGraph1,2);
		$pageBouncesArray1[] = round(($avgBounceGraph1)*100, 2); 
		$tot_bounces += $v['bounces'];
		$tot_pageviews += $v['pageviews'];
		$tot_sessions += $v['sessions'];
		$tot_sessionDuration += $v['sessionDuration'];
		$kk1++;
	}
	$avgBounceRate 		= $tot_bounces/$tot_sessions;
	$avgSessionDuration = $tot_sessionDuration/$tot_sessions;
	
	foreach($xAxis1 as $key => $value){
		$lp_path1[]=$value;
	}
	foreach($landingpfull1 as $key => $value4){
		$lp_path_full1[]=$value4;
	}	
	foreach($pageViewsArray1 as $key => $value1){
		$pageviews1[]=$value1;
	}
	foreach($pageSessionsArray1 as $key => $value2){
		$session1[]=$value2;
	}
	foreach($pageBouncesArray1 as $key => $value3){
		$bounce1[]=round($value3,1);
	}
	
	/*for Landing Page Analysis graph data end*/
	/*for Exit Page Analysis graph data start*/
	$exit_xAxis = array();
	$exit_pageViewsArray = $exit_pageexitsviewsvalArray = array();
	$exit_kk = 0;
	
	// print_r($exitnewArr);die;
	foreach($exitnewArr as $k=>$v){
		if($exit_kk==10){
			break;
		}
		$exit_rvaindex = strlen($v['exitPagePath']) > 10 ? mb_substr($v['exitPagePath'],0,10)."..." : $v['exitPagePath'];
		$exit_xAxis[] = $exit_rvaindex;
		$exit_landingpfull[] = $v['exitPagePath'];
		$exit_pageViewsArray[] =  $v['pageviews'];
		$exit_pageexitsArray[] =  $v['exits'];
		$exit_pageexitsviewsvalArray[] = (($v['pageviews'] > 0) ? round($v['exits']/$v['pageviews']*100,2) : 0);
		$tot_exits += $v['exits'];
		$tot_exit_pageviews += $v['pageviews'];
		$exit_kk++;
	}
	foreach($exit_xAxis as $key => $value){
		$exit_lp_path[]=$value;
	}	
	foreach($exit_pageViewsArray as $key => $value1){
		$exit_pageviews[]=$value1;
	}
	foreach($exit_pageexitsArray as $key => $value2){
		$exit_exits[]=$value2;
	}
	foreach($exit_pageexitsviewsvalArray as $key => $value3){
		$exit_exitsperpageview[]=round($value3,1);
	}
	foreach($exit_landingpfull as $key => $value4){
		$exit_lp_path_full[]=$value4;
	}
	$exit_topdata=array('exit_Pageviews'=>number_format($tot_exit_pageviews),'exit_Exits'=>number_format($tot_exits),'exit_AvgExits'=>round(($tot_exits/$tot_exit_pageviews)*100,1),'exit_daterange'=>$daterangelastweek);
	// $request_array=array('exit_categories'=>$exit_lp_path,'exit_pageviews'=>$exit_pageviews,'exit_exits'=>$exit_exits,'exit_exitsperpageview'=>$exit_exitsperpageview,'exit_topdata'=>array($exit_topdata));
	/*for Exit Page Analysis graph data end*/
	
	
	
	
	$topdata=array('Pageviews'=>number_format($tot_pageviews),'Avgsession'=>gmdate("H:i:s",$avgSessionDuration),'Avgbounce'=>round(($avgBounceRate)*100,1),'landing_daterange'=>$daterangelastweek);
	
	$request_array=array('tabledata_lp_highbounce'=>$tabledata,'categories'=>$lp_path1,'pageviews'=>$pageviews1,'session'=>$session1,'bounce'=>$bounce1,'topdata'=>$topdata,'daterange_highbounce'=>$daterangelastweek,'exit_categories'=>$exit_lp_path,'exit_pageviews'=>$exit_pageviews,'exit_exits'=>$exit_exits,'exit_exitsperpageview'=>$exit_exitsperpageview,'exit_topdata'=>$exit_topdata);
	// $daterange[]=array('daterange'=>$daterangelastweek);
	// $request_array=array('tabledata_lp_highbounce'=>$tabledata,'daterange'=>$daterange);
	// $request_array=$tabledata;

   return $request_array; 
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