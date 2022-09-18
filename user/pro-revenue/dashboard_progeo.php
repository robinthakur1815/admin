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
include_once '../../objects/ProRevenue.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$pro = new ProRevenue($db,$dbMongoDb);
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
     $result_h = $pro->getGeo();
     
     $result_hb = $result_h->toArray(); 
     
    if(!empty($result_hb)){
           
        #calculation
        $data = prepareData($result_hb,$cmsShare,$data->strtdate,$data->enddate);
        
          
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
    echo json_encode(array("message" => "Unable to get display overview. Data is incomplete.","status_code"=>400));
}

function prepareData($result_hb,$cmsShare,$start,$end){
	#Date Array    
	while (strtotime($start) <= strtotime($end))
    {
     $date[] = date('j M', strtotime($start));
     $date_array[] = date('Y-m-j', strtotime($start));
     $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
    }   
	foreach ($result_hb as $val) {

        if($val->_id->category=="Feature phone" || $val->_id->category=="Smartphone"){
            $val->_id->category="Mobile";
            $geo_name="Mobile";
        }else{
			$geo_name=$val->_id->category;
		}
        $geo=str_replace(" ","_",$val->_id->country);
        
        $dateindex = date('Y-m-j', strtotime($val->_id->date));
        @$sumuplevel_array[$geo]['country']=$geo;
        @$sumuplevel_array[$geo]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);
        @$arraylevel2[$geo][$geo_name]+=number_format(($val->total_revenue)-($val->total_revenue*$cmsShare),2);
		
        $device_name= str_replace(" ","",$val->_id->category);
        #level 1 outer
        @$sumuplevel2_array[$geo][$device_name]['rep_namelvl1']=$device_name;
        @$sumuplevel2_array[$geo][$device_name]['revenue_cmsSharelvl1']+=number_format($val->total_revenue-($val->total_revenue*$cmsShare),2);
      
         @$datalevel2inner[$geo][$device_name][$dateindex]['dateinnerlvl1']= date('j M, Y', strtotime($val->_id->date));
         @$datalevel2inner[$geo][$device_name][$dateindex]['revenue_cmsShareinnerlvl1']+=number_format($val->total_revenue-($val->total_revenue*$cmsShare),2);

        
    }
	aasort($sumuplevel_array,"tot_lineitmlvl_rev");

    #Merge innerdata of first page
	$count = 0;  
    foreach ($sumuplevel_array as $ky => $value) {
        $sumOtherInner = array();
		if($count < 9){
			$sumuplevel_newarr[$ky] = $value;
				
		}else{
            
            @$otherRev += number_format($value['tot_lineitmlvl_rev'],2);
              
                  $other_arr = array(
                    'country'=>"Other",
                    'tot_lineitmlvl_rev'=>$otherRev,
                    );
        }
		$count++;
	}

     if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $sumuplevel_newarr['Other'] = $other_arr;
              $other_arr = array();
            }
	/***level 1***/
    foreach ($sumuplevel_newarr as $k => $value) {
         
         $request_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['tot_lineitmlvl_rev'],2),
                                        'drilldown'=>$k,
                                    );
      $coun_arr[] = $k;
      }
    /***level 1***/ 
	
	/***level 2***/ 
   $sumRevDrillMobile =$sumRevDrillDesktop =$sumRevDrillTablets =$sumRevDrillTv = 0;  
    foreach($arraylevel2 as $key2=>$value2){
        $datalevel2 = array();
        arsort($arraylevel2[$key2]);
        foreach($arraylevel2[$key2] as $keyinner=>$valueinner){
            /***condition for top 9 country wise***/

            if(in_array($key2,$coun_arr)){
                $datalevel2[]=array(
                                'name'=>$keyinner,
                                'y'=>round($valueinner,2),
                                'drilldown'=>$keyinner.$key2
                            );
            }
        }
        if(in_array($key2,$coun_arr)){
            $request_array1['level2'][]=array(
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
     
    if(in_array('Other',$coun_arr)){
        $request_array1['level2'][]=array(
                                        'name'=>"Other",
                                        'id'=>"Other",
                                        'type'=>'pie',
                                        'data'=>$dataOthDev);
       
        
    }
        
        /***level 2***/
	
		#sorted revenue wise

		foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1)
        {
            if(in_array($keyfinal2,$coun_arr)){
               aasort($valuelvl1,"revenue_cmsSharelvl1");
                foreach($valuelvl1 as $value2)
                {							
                    $dataarray_array['geo_table_data_lvl1'][]=array(
                    'level1value'=>$keyfinal2,
                    'rep_namelvl1'=>$value2['rep_namelvl1'],
                    'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2),
                    'innerdatalvl1'=>get_sum_indexinner($datalevel2inner[$keyfinal2][$value2['rep_namelvl1']],$date_array),
                    );

                }
          }else{
            
           foreach($valuelvl1 as $valOther)
                 {
                   
                  @$otherArr[$valOther['rep_namelvl1']]['rev'] += number_format($valOther['revenue_cmsSharelvl1'],2);
                  @$sumOtherInner1[$valOther['rep_namelvl1']] = get_sum_indexinner($datalevel2inner[$keyfinal2][$valOther['rep_namelvl1']],$date_array);
                  foreach($sumOtherInner1[$valOther['rep_namelvl1']] as $othervalues)
                    {
                        
                            $finalindexOth = date('Y-m-j',strtotime($othervalues['dateinnerlvl1']));
                            
                            @$otherArr[$valOther['rep_namelvl1']]['innerdatalvl1'][$finalindexOth]['revenue_cmsShareinnerlvl1']+=number_format($othervalues['revenue_cmsShareinnerlvl1'],2);
                           

                            
                        
                    }
                }  //loop end

            }
        

        }
     
         
      if(!empty($otherArr)){
        arsort($otherArr);
         foreach($otherArr as $dvKey => $otherDeviceValue){
                  $dataarray_array['geo_table_data_lvl1'][] = array(
                    'level1value'=>"Other",
                    'rep_namelvl1'=>$dvKey,
                    'revenue_cmsSharelvl1'=>$otherDeviceValue['rev'],
                    'innerdatalvl1'=>array_values($otherDeviceValue['innerdatalvl1']),
                  );
               
               }  
            }
            
      $request_array1['geo_table_data_lvl1']=array_values($dataarray_array['geo_table_data_lvl1']);  
	 
	  
	  /***level 3***/
        $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
     
	foreach($request_array1['geo_table_data_lvl1'] as $lvl3){
     $datalevel3inner = array();
    foreach($lvl3['innerdatalvl1'] as $lvl3data){
                  $datalevel3inner[] = (float)$lvl3data['revenue_cmsShareinnerlvl1'];
          }
          $request_array1['level3'][]=array(
                                                'name'=>$lvl3['level1value']."By".$lvl3['rep_namelvl1'],
                                                'id'=>$lvl3['rep_namelvl1'].$lvl3['level1value'],
                                                'type'=>'area',
                                                'data'=>$datalevel3inner,
                                                 'marker'=>$mar, 
                                            );
    }
      
      /***level 3***/
	  
	  
	  
	$request_array['finaldata']=array_merge($request_array1['level2'],$request_array1['level3']);
 
	$request_array['level3_dates']['dates'] = $date;
   return $request_array; 
}/***calculation function end*****/

function get_sum_indexinner($array_data,$array_fulldate){
	foreach($array_fulldate as $date_value){
		if (in_array($date_value, array_keys($array_data))){ 
			$formatedarray[]=array(
			'dateinnerlvl1'=> @$array_data[$date_value]['dateinnerlvl1'],
			'revenue_cmsShareinnerlvl1'=> number_format(@$array_data[$date_value]['revenue_cmsShareinnerlvl1'],2)
			);
		}else{
			$formatedarray[]=array(
			'dateinnerlvl1'=> date('j M, Y', strtotime($date_value)),
			'revenue_cmsShareinnerlvl1'=> 0
			);
		}
	}
    return $formatedarray;
}
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