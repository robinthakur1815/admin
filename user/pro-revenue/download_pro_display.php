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
     $result_h = $pro->getOverview();
     
     $result_hb = $result_h->toArray(); 
     #$result_deal = $result_h[1]->toArray(); 
     
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
	while (strtotime($end) >= strtotime($start)){
     $date_array[]=$end;
     $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
	}    
	foreach ($result_hb as $val) {
		$res_array['hbd_table_data'][$val->_id->date] =array('date'=>date('d, M Y', strtotime($val->_id->date)),
                'tot_lineitmlvl_imp'=>number_format($val->totalline_lvl_imp),
                'tot_lineitmlvl_cpm'=>$val->totalline_lvl_imp > 0 ? number_format((($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare))/$val->totalline_lvl_imp*1000,2):0.00,
				'tot_lineitmlvl_rev'=>round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2)
                );
        #Total row for first slide 
        @$total_array['tot_lineitmlvl_imp']+=$val->totalline_lvl_imp;
        @$total_array['tot_lineitmlvl_rev']+=round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2);
        @$total_array['tot_lineitmlvl_cpm'] = $total_array['tot_lineitmlvl_imp'] > 0 ? round($total_array['tot_lineitmlvl_rev']/$total_array['tot_lineitmlvl_imp']*1000,2) : 0.00;

        
        $revarrayhbd[date('d, M', strtotime($val->_id->date))] =round(($val->totalline_lvl_rev)-($val->totalline_lvl_rev*$cmsShare),2);
		$dateavail[]=$val->_id->date;
        
    }
	$result=array_diff($date_array, $dateavail);
	foreach($result as $restarr)  {
        $res_array['hbd_table_data'][$restarr]= array(
            'date'=>date('d M ,Y', strtotime($restarr)),
            'tot_lineitmlvl_imp'=>0,
            'tot_lineitmlvl_cpm'=>0,
            'tot_lineitmlvl_rev'=>0
        );
	}
	krsort($res_array['hbd_table_data']);
	$request_array['hbd_table_data'] = array_values(array_reverse($res_array['hbd_table_data']));



	$request_array['hbd_table_data'][] = array("date"=>"Total","tot_lineitmlvl_imp"=>number_format($total_array['tot_lineitmlvl_imp']),"tot_lineitmlvl_cpm"=>$total_array['tot_lineitmlvl_cpm'],"tot_lineitmlvl_rev"=>number_format($total_array['tot_lineitmlvl_rev']));

	$filename = "CyberAds_Pro_Report_Overview_".$uniqid.".csv";
	$filepath = "./upload/".$filename;
	$fp = fopen($filepath,"w");
	$blank=array("\n","\n");
	
	$header=array("--Date--","--Total Impressions--","--Estimated CPM--","--Total Earnings--");
	fputcsv($fp,$header);
	foreach ($request_array['hbd_table_data'] as $row) {
		fputcsv($fp, $row);
	}
	
	fclose($fp);
	return $filename;
	
}/***calculation function end*****/

?>