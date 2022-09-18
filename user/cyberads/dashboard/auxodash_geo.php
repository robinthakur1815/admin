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
ini_set('memory_limit', '-1');
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../../../config/connection.php';
include_once '../../../objects/Common.php';
include_once '../../../objects/DashboardAuxo.php';
// include_once '../../../objects/DashboardAuxo_sandy.php'; 

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$dashGeo = new DashboardAuxo($db);
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
                   $cmsShare = $rowShare['pub_display_share']/100;}else{$cmsShare = 15/100;}
                   if($rowShare['pub_app_share'] !=0){
                   $cmsShareApp = $rowShare['pub_app_share']/100;}else{$cmsShareApp = 15/100;}
                   if($rowShare['pub_video_share'] !=0){
                   $cmsShareVid = $rowShare['pub_video_share']/100;}else{$cmsShareVid = 15/100;}

         }else{
            $cmsShare = 15/100;
            $cmsShareApp = 15/100;
            $cmsShareVid = 15/100;
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
     #set Ad type property values
     $dashGeo->range = $data->range;
     $dashGeo->strtdate = $data->strtdate;
     $dashGeo->enddate = $data->enddate;
     $dashGeo->child_net_code = $data->child_net_code;
     $result_adtype = $dashGeo->getGeodash();
   
	if(!empty($result_adtype['Display']) || !empty($result_adtype['App']) || !empty($result_adtype['Video'])){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adtype,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get dashboard ad type. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_geo,$start,$end){
     #Date Array
    while (strtotime($start) <= strtotime($end)){
		$date[] = date('j M', strtotime($start));
		$date_level3[] = date('Y-m-d', strtotime($start));
		$date_arr[] = date('Y-m-j', strtotime($start));
		$start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
	}
    
    foreach ($result_geo as $key => $rowgeotypes) {

      foreach($rowgeotypes as $rowgeo){
		if(strtolower($rowgeo['rep_name'])=='high-end mobile devices') {
			$rowgeo['rep_name'] = str_replace($rowgeo['rep_name'],"Mobile",$rowgeo['rep_name']);
			$repname = str_replace($rowgeo['rep_name'],"Mobile",$rowgeo['rep_name']);
		}else{
			$repname = $rowgeo['rep_name'];
		}
		$geo = str_replace(".","_",$rowgeo['country_name']);
        $dateindex = date('Y-m-d', strtotime($rowgeo['date']));
		@$sumuplevel_array[$geo]['geo']=$geo;
        @$sumuplevel2_array[$geo][$repname]['rep_namelvl1']=$repname;
		if($key == 'Display'){
			@$sumuplevel_array[$geo]['revenue_cmsShare']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);

			@$arraylevel2[$geo][$repname]+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);

			
			@$sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2);
			#inner
			@$datalevel2inner[$geo][$repname][$dateindex]['dateinnerlvl1']= date('Y-m-d', strtotime($rowgeo['date']));
			@$datalevel2inner[$geo][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShare),2); 
				
		}
		if($key == 'App'){
			@$sumuplevel_array[$geo]['revenue_cmsShare']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareApp),2);
			@$arraylevel2[$geo][$repname]+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareApp),2);
			@$sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareApp),2);
			#inner
			@$datalevel2inner[$geo][$repname][$dateindex]['dateinnerlvl1']= date('Y-m-d', strtotime($rowgeo['date']));
			@$datalevel2inner[$geo][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareApp),2); 
			
		}
		if($key == 'Video'){
			@$sumuplevel_array[$geo]['revenue_cmsShare']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareVid),2);
			@$arraylevel2[$geo][$repname]+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareVid),2);

			@$sumuplevel2_array[$geo][$repname]['revenue_cmsSharelvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareVid),2);
			#inner
			@$datalevel2inner[$geo][$repname][$dateindex]['dateinnerlvl1']= date('Y-m-d', strtotime($rowgeo['date']));
			@$datalevel2inner[$geo][$repname][$dateindex]['revenue_cmsShareinnerlvl1']+=round($rowgeo['revenue']-($rowgeo['revenue']*$cmsShareVid),2); 
		}
		#end
        
      } //inner loop
	} //loop end
	#sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    
	/***level 1***/
    $countgeo = 1;
	$othergeolevl1 =0;
	foreach ($sumuplevel_array as $k => $value) {
		if($countgeo < 10){
			$request_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['revenue_cmsShare'],2),
                                        'drilldown'=>$k,
                                    );
		}else{
			$othergeolevl1 +=round($value['revenue_cmsShare'],2);
			
		}
		$countgeo++;
	}
	if(!empty($request_array['level1'])){
		$request_array['level1'][]=array(
                                        'name'=>'Other',
                                        'y'=>$othergeolevl1,
                                        'drilldown'=>'Other',
                                    );
	}
	
	
	$firstLevel = array();
	if(!empty($request_array['level1'])){
		foreach($request_array['level1'] as $key321 => $value321){
			$firstLevel[] = $value321['name'];
		}
	}
	/***level 1***/ 
	
	// echo"<pre>";
	// print_r($firstLevel);die;
    
  
    /***level 2***/ 
	
	$sumRevDrillMobile =$sumRevDrillDesktop =$sumRevDrillTablets =$sumRevDrillTv = 0;   
    foreach($arraylevel2 as $key2=>$value2){
		$datalevel2 = array();
        foreach($arraylevel2[$key2] as $keyinner=>$valueinner){
            /***condition for top 9 country wise***/ 
			if(in_array($key2,$firstLevel)){
				$datalevel2[]=array(
								'name'=>$keyinner,
								'y'=>round($valueinner,2),
								'drilldown'=>$keyinner.$key2
							);
			}
        }
		if(in_array($key2,$firstLevel)){
			$response_array['level2'][]=array(
                                        'name'=>$key2,
                                        'id'=>$key2,
                                        'type'=>'pie',
                                        'data'=>$datalevel2);
		}else{
			@$sumRevDrillMobile += $value2['Mobile'];
			@$sumRevDrillDesktop += $value2['Desktop'];
			@$sumRevDrillTablets += $value2['Tablets'];
			@$sumRevDrillTv += $value2['Connected TV'];
		}
	}
	$deviceArray = array("Mobile"=>$sumRevDrillMobile,"Desktop"=>$sumRevDrillDesktop,"Tablets"=>$sumRevDrillTablets,"Connected TV"=>$sumRevDrillTv);
      arsort($deviceArray);
    
      foreach($deviceArray as $devKey => $valDev){
              $dataOthDev[] = array(
                                'name'=>$devKey,
                                'y'=>round($valDev,2),
                                'drilldown'=>$devKey."Other"
                            );
      }  
     
    if(in_array('Other',$firstLevel)){
        $response_array['level2'][]=array(
                                        'name'=>"Other",
                                        'id'=>"Other",
                                        'type'=>'pie',
                                        'data'=>$dataOthDev);
       
        
    } 
	
      /***level 2***/
    
	
	#put empty row to zero start Array
	foreach($datalevel2inner as $key241 => $value241){
		foreach($value241 as $key2341 => $value2341){
			foreach($date_level3 as $date_value){
				if(in_array($date_value, array_keys($value2341))){
					
				}else{
					#put empty row to zero 
					$datalevel2inner[$key241][$key2341][$date_value] = array("dateinnerlvl1"=>$date_value,"revenue_cmsShareinnerlvl1"=>0);
				}
			}
			
		}
	}
	#put empty row to zero end Array
	#/***level 3***/
	
	$mar = array("enabled"=> false,"symbol"=> "circle");
	foreach($datalevel2inner as $key241 => $value241){
		if(in_array($key241,$firstLevel)){
			foreach($value241 as $key2341 => $value2341){
				aasort($value2341,'dateinnerlvl1');
				$datalevel3inner = array();
				foreach(array_reverse($value2341) as $key2441 => $value2441){
					$datalevel3inner[] = (float)$value2441['revenue_cmsShareinnerlvl1'];
				}
				$response_array['level3'][]=array(
                                        'name'=>$key241."By".$key2341,
                                        'id'=>$key2341.$key241,
                                        'type'=>'area',
                                        'data'=>$datalevel3inner,
										'marker'=>$mar);
			}
		}else{
			#/***level 3 other array combine data***/
			foreach($value241 as $key2341 => $value2341){
				aasort($value2341,'dateinnerlvl1');
				
				foreach(array_reverse($value2341) as $key2441 => $value2441){
					@$revenue[$key2341][$value2441['dateinnerlvl1']] += (float)$value2441['revenue_cmsShareinnerlvl1'];
				}
				
				
				// if($key2341 == 'Mobile'){
					// foreach(array_reverse($value2341) as $key2441 => $value2441){
						// @$revenueMobile[$value2441['dateinnerlvl1']] += (float)$value2441['revenue_cmsShareinnerlvl1'];
					// }
				// }
				// if($key2341 == 'Desktop'){
					// foreach(array_reverse($value2341) as $key2441 => $value2441){
						// @$revenueDesktop[$value2441['dateinnerlvl1']] += (float)$value2441['revenue_cmsShareinnerlvl1'];
					// }
				// }
				// if($key2341 == 'Tablets'){
					// foreach(array_reverse($value2341) as $key2441 => $value2441){
						// @$revenueTablets[$value2441['dateinnerlvl1']] += (float)$value2441['revenue_cmsShareinnerlvl1'];
					// }
				// }
				// if($key2341 == 'Connected TV'){
					// foreach(array_reverse($value2341) as $key2441 => $value2441){
						// @$revenueTv[$value2441['dateinnerlvl1']] += (float)$value2441['revenue_cmsShareinnerlvl1'];
					// }
				// }
				
			}
		}
	}
	
	// print_r($revenue);die;
	
	if(!empty($revenue)){
		$level3dateData = array();
		foreach($revenue as $key => $value){
			foreach($value as $vlaue2){
				$level3dateData[]=$vlaue2;
			}
			$response_array['level3'][]=array(
                                        'name'=>"OtherBy".$key,
                                        'id'=>$key."Other",
                                        'type'=>'area',
                                        'data'=>$level3dateData,
										'marker'=>$mar);
		}
		
	}
	
	#/***level 3 other Array***/
	// if(!empty($revenueMobile)){
		// $level3Mobilearr = array();
		// foreach($revenueMobile as $key => $value){
			// $level3Mobilearr[]=$value;
		// }
		// $response_array['level3'][]=array(
                                        // 'name'=>"OtherByMobile",
                                        // 'id'=>"MobileOther",
                                        // 'type'=>'area',
                                        // 'data'=>$level3Mobilearr,
										// 'marker'=>$mar);
	// }
	// if(!empty($revenueDesktop)){
		// $level3Desktoparr = array();
		// foreach($revenueDesktop as $key => $value){
			// $level3Desktoparr[]=$value;
		// }
		// $response_array['level3'][]=array(
                                        // 'name'=>"OtherByDesktop",
                                        // 'id'=>"DesktopOther",
                                        // 'type'=>'area',
                                        // 'data'=>$level3Desktoparr,
										// 'marker'=>$mar);
	// }
	// if(!empty($revenueTablets)){
		// $level3Tabletsarr = array();
		// foreach($revenueTablets as $key => $value){
			// $level3Tabletsarr[]=$value;
		// }
		// $response_array['level3'][]=array(
                                        // 'name'=>"OtherByTablets",
                                        // 'id'=>"TabletsOther",
                                        // 'type'=>'area',
                                        // 'data'=>$level3Tabletsarr,
										// 'marker'=>$mar);
	// }
	// if(!empty($revenueTv)){
		// $level3Tvarr = array();
		// foreach($revenueTv as $key => $value){
			// $level3Tvarr[]=$value;
		// }
		// $response_array['level3'][]=array(
                                        // 'name'=>"OtherByConnected TV",
                                        // 'id'=>"Connected TVOther",
                                        // 'type'=>'area',
                                        // 'data'=>$level3Tvarr,
										// 'marker'=>$mar);
	// }
	
	
     /****Level 3*****/
	
	$request_array['finaldata']=array_merge($response_array['level2'],$response_array['level3']);
 

	$request_array['level3_dates']['dates'] = $date;     
    
    return $request_array;
         
}/***calculation function end*****/


function aasort(&$array, $key) {
    
    $sorter=array(); $ret=array(); reset($array);
    
    foreach ($array as $ii => $va) {
        @$sorter[$ii]=$va[$key];
    }
    
    arsort($sorter); 
    
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    
    $array=$ret;
}

?>