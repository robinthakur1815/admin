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
        $data = prepareData($result_hb,$cmsShare,$data->strtdate,$data->enddate,$data->uniq_id);
        
          
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

function prepareData($result_hb,$cmsShare,$start,$end,$uniqid){
	#Date Array    
	while (strtotime($start) <= strtotime($end)){
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
		@$sumuplevel_array[$geo]['tot_lineitmlvl_imp']+=$val->totalline_lvl_imp;
		@$sumuplevel_array_rev[$geo]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);
		@$sumuplevel_array[$geo]['tot_lineitmlvl_cpm'] = $sumuplevel_array[$geo]['tot_lineitmlvl_imp'] > 0 ? number_format($sumuplevel_array_rev[$geo]['tot_lineitmlvl_rev']/$sumuplevel_array[$geo]['tot_lineitmlvl_imp']*1000,2) : 0.00;
		@$sumuplevel_array[$geo]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);


		#first table page inner
		@$datalevel1inner[$geo][$dateindex]['dateinner']= date('j M, Y', strtotime($val->_id->date));

		@$datalevel1inner[$geo][$dateindex]['adimrinner']+=$val->totalline_lvl_imp;
		@$datalevel1inner_rev[$geo][$dateindex]['revenue_cmsShareinner']+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);  
		@$datalevel1inner[$geo][$dateindex]['ecpmxinner'] =$datalevel1inner[$geo][$dateindex]['adimrinner'] > 0 ? number_format($datalevel1inner_rev[$geo][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$geo][$dateindex]['adimrinner']*1000,2) : 0.00;
		@$datalevel1inner[$geo][$dateindex]['revenue_cmsShareinner']+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);  

		$device_name= str_replace(" ","",$val->_id->category);

		#inner
		@$datalevel2inner[$geo][$device_name][$dateindex]['dateinnerlvl1']= date('d M, Y', strtotime($val->_id->date));

		@$datalevel2inner[$geo][$device_name][$dateindex]['adimrinnerlvl1']+=$val->totalline_lvl_imp;
		@$datalevel2inner[$geo][$device_name][$dateindex]['revenue_cmsShareinnerlvl1']+=number_format($val->total_revenue-($val->total_revenue*$cmsShare),2);  
		@$datalevel2inner[$geo][$device_name][$dateindex]['ecpmxinnerlvl1'] = $datalevel2inner[$geo][$device_name][$dateindex]['adimrinnerlvl1'] > 0 ? number_format($datalevel2inner[$geo][$device_name][$dateindex]['revenue_cmsShareinnerlvl1']/$datalevel2inner[$geo][$device_name][$dateindex]['adimrinnerlvl1']*1000,2) : 0.00; 

		#level 1 outer
		@$sumuplevel2_array[$geo][$device_name]['rep_namelvl1']=$device_name;
		@$sumuplevel2_array[$geo][$device_name]['adimrlvl1']+=$val->totalline_lvl_imp;
		@$sumuplevel2_array[$geo][$device_name]['revenue_cmsSharelvl1']+=number_format($val->total_revenue-($val->total_revenue*$cmsShare),2);
		@$sumuplevel2_array[$geo][$device_name]['ecpmxlvl1'] = $sumuplevel2_array[$geo][$device_name]['adimrlvl1'] > 0 ? number_format($sumuplevel2_array[$geo][$device_name]['revenue_cmsSharelvl1']/$sumuplevel2_array[$geo][$device_name]['adimrlvl1']*1000,2) : 0.00;

	}
	aasort($sumuplevel_array,"tot_lineitmlvl_rev");

	#Merge innerdata of first page
	$count = 0;  
	foreach ($sumuplevel_array as $ky => $value) {
		$sumOtherInner = array();
		if($count < 9){
			$sumuplevel_newarr[$ky] = $value;
			$sumuplevel_newarr[$ky]['innerdata'] = get_sum_index($datalevel1inner[$ky],$date_array);
			$coun_arr[] = $ky;
		}else{

			@$otherAdimp += $value['tot_lineitmlvl_imp'];
            @$otherRev += number_format($value['tot_lineitmlvl_rev'],2);
            @$otherEcpm = $otherAdimp > 0 ? number_format(($otherRev)/$otherAdimp*1000,2) :0.00;
            @$sumOtherInner[]=get_sum_index($datalevel1inner[$ky],$date_array);
             foreach($sumOtherInner as $level1suminner)
                    {
                        foreach($level1suminner as $level2key=>$level2values)
                        {
                            $finalindex=date('Y-m-j',strtotime($level2values['dateinner']));
                            @$finalinnerdatalevel1[$finalindex]['dateinner']=$level2values['dateinner'];
                             @$finalinnerdatalevel1[$finalindex]['adimrinner']+=$level2values['adimrinner'];
                            @$finalinner_rev[$finalindex]['revenue_cmsShareinner']+=number_format($level2values['revenue_cmsShareinner'],2);
                            @$finalinnerdatalevel1[$finalindex]['ecpmxinner'] =$finalinnerdatalevel1[$finalindex]['adimrinner'] > 0 ? number_format(($finalinner_rev[$finalindex]['revenue_cmsShareinner'])/$finalinnerdatalevel1[$finalindex]['adimrinner']*1000,2) :0.00;
                            @$finalinnerdatalevel1[$finalindex]['revenue_cmsShareinner']+=number_format($level2values['revenue_cmsShareinner'],2);


                            
                        }
                    }

                   
                  $other_arr = array(
                    'country'=>"Other",
                    'tot_lineitmlvl_imp'=>$otherAdimp,
                    'tot_lineitmlvl_cpm'=>$otherEcpm,
                    'tot_lineitmlvl_rev'=>$otherRev,
                    'expanded'=>false,
                    'innerdata'=>array_values($finalinnerdatalevel1));
		}
		$count++;
	}
if(!empty($other_arr)){
              $finalinnerdatalevel1 = array();
              $sumuplevel_newarr['Other'] = $other_arr;
              $other_arr = array();
            }
	#sorted revenue wise
	foreach($sumuplevel2_array as $keyfinal2=>$valuelvl1){
       if(in_array($keyfinal2,$coun_arr)){
		aasort($valuelvl1,"revenue_cmsSharelvl1");
		foreach($valuelvl1 as $value2){
			$dataarray_array['geo_table_data_lvl1'][]=array(
				'level1value'=>$keyfinal2,
				'rep_namelvl1'=>$value2['rep_namelvl1'],
				'adimrlvl1'=>number_format($value2['adimrlvl1']),
				'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
				'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2)
			);
		}
		 }else{

		 	foreach($valuelvl1 as $valOther)
                 {
                   
                 
                  @$otherArr[$valOther['rep_namelvl1']]['imp'] += $valOther['adimrlvl1'];
                  @$otherArr[$valOther['rep_namelvl1']]['rev'] += number_format($valOther['revenue_cmsSharelvl1'],2);
                  @$otherArr[$valOther['rep_namelvl1']]['ecpm'] = $otherArr[$valOther['rep_namelvl1']]['imp'] > 0 ? number_format(($otherArr[$valOther['rep_namelvl1']]['rev'])/$otherArr[$valOther['rep_namelvl1']]['imp']*1000,2) :0.00;
              }
		 }



	}

	if(!empty($otherArr)){
        arsort($otherArr);
         foreach($otherArr as $dvKey => $otherDeviceValue){
                  $dataarray_array['geo_table_data_lvl1'][] = array(
                    'level1value'=>"Other",
                    'rep_namelvl1'=>$dvKey,
                    'adimrlvl1'=>$otherDeviceValue['imp'],
                    'ecpmxlvl1'=>$otherDeviceValue['ecpm'],
                    'revenue_cmsSharelvl1'=>$otherDeviceValue['rev']
                   );
               
               }
              
              
              
            }

	$filename = "AuxoPro_Report_Geo_".$uniqid.".csv";

	$filepath = "./upload/".$filename;


	$fp = fopen($filepath,"w");
	$blank=array("\n","\n");


	foreach($sumuplevel_newarr as $row=>$value)   {
		array_pop($value);

		$header=array("--Country--","--Ad Impressions--","--Estimated eCPM--","--Estimated Earnings--");
		fputcsv($fp,$header);

		fputcsv($fp, $value);

		fputcsv($fp,$blank);
		$header=array("--Date--","--Ad Impressions--","--Estimated eCPM--","--Estimated Earnings--");
		fputcsv($fp,$header);

		foreach($sumuplevel_newarr[$row]['innerdata'] as $row2)   {
			fputcsv($fp, $row2);
		}

		fputcsv($fp,$blank);
		if($dataarray_array['geo_table_data_lvl1']['level1value'] != 'Other' || $dataarray_array['geo_table_data_lvl1']['level1value'] != '') {
			$header=array("--Country--","--Device--","--Ad Impressions--","--Estimated eCPM--","--Estimated Earnings--");
			fputcsv($fp,$header);

			foreach($dataarray_array['geo_table_data_lvl1'] as $row3){
				if($value['country']==$row3['level1value']){
					fputcsv($fp, $row3);
				}
			}
			fputcsv($fp,$blank);
			fputcsv($fp,$blank);

		}   
	}	  
	//Close the file handle.
	fclose($fp);
	return $filename; 
}
function get_sum_index($array_data,$array_fulldate)
{
   
krsort($array_data);

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinner'=> @$array_data[$date_value]['dateinner'],
        'adimrinner'=> @$array_data[$date_value]['adimrinner'],
        'ecpmxinner'=> number_format(@$array_data[$date_value]['ecpmxinner'],2),
        'revenue_cmsShareinner'=> number_format(@$array_data[$date_value]['revenue_cmsShareinner'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinner'=> date('j M, Y', strtotime($date_value)),
        'adimrinner'=>0,
        'ecpmxinner'=> 0,
        'revenue_cmsShareinner'=> 0
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