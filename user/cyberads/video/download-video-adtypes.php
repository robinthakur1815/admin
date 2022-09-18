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
include_once '../../../objects/Video.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$header = new Common($db);
$video = new Video($db);
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
     $video->range = $data->range;
     $video->strtdate = $data->strtdate;
     $video->enddate = $data->enddate;
     $video->child_net_code = $data->child_net_code;
     $result_adtype = $video->getAdtype();

     if(!empty($result_adtype)){
        #calculation
        $data = prepareData($cmsShare,$result_adtype,$data->uniq_id);
         
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
    echo json_encode(array("message" => "Unable to get display ad type. Data is incomplete.","status_code"=>400));
}
function prepareData($cmsShare,$result_adtype,$uniqid){
    
    
    foreach ($result_adtype as $key => $rowadtype) {
        $AdType= str_replace(")","",str_replace("(","",str_replace(" ","",$rowadtype['ad_type'])));
        #Data merge for first slide by adtype wise
        @$sumuplevel_array[$AdType]['adtype']=$AdType;
        @$sumuplevel_array[$AdType]['adreq']+=$rowadtype['adr'];
        @$sumuplevel_array[$AdType]['adimpr']+=$rowadtype['adimr'];
        @$sumuplevel_arraymad[$AdType]['madreq']+=$rowadtype['madr'];
        @$sumuplevel_array[$AdType]['fillrate'] = number_format($sumuplevel_array[$AdType]['adimpr']/$sumuplevel_array[$AdType]['adreq']*100,1);
        @$click_array[$AdType]['clicks']+=$rowadtype['clicks'];
        @$sumuplevel_array[$AdType]['covg'] = $sumuplevel_arraymad[$AdType]['madreq'] > 0 ? number_format(($sumuplevel_arraymad[$AdType]['madreq']*100)/$sumuplevel_array[$AdType]['adreq'],1) :0.00;
        @$sumuplevel_array[$AdType]['ctr'] = $sumuplevel_array[$AdType]['adimpr'] > 0 ? number_format($click_array[$AdType]['clicks']/$sumuplevel_array[$AdType]['adimpr']*100,1):0.00;
        @$revenue_array[$AdType]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
        @$sumuplevel_array[$AdType]['ecpm'] = $sumuplevel_array[$AdType]['adimpr'] > 0 ? number_format(floor(($revenue_array[$AdType]['revenue_cmsShare']/$sumuplevel_array[$AdType]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$AdType]['revenue_cmsShare']=$revenue_array[$AdType]['revenue_cmsShare'];
        
    #First slide datewise data   
    $dateindex = $rowadtype['date'];
     @$datalevel1inner[$AdType][$dateindex]['dateinner']= date('d M, Y', strtotime($rowadtype['date']));
     @$datalevel1inner[$AdType][$dateindex]['adreq']+=$rowadtype['adr'];
     @$datalevel1inner[$AdType][$dateindex]['adimpr']+=$rowadtype['adimr'];
     @$datalevel1innermad[$AdType][$dateindex]['madreq']+=$rowadtype['madr'];
     @$datalevel1inner[$AdType][$dateindex]['fillrate'] = number_format($datalevel1inner[$AdType][$dateindex]['adimpr']/$datalevel1inner[$AdType][$dateindex]['adreq']*100,1);
     @$clicklevel1inner[$AdType][$dateindex]['clicks']+=$rowadtype['clicks'];
     @$datalevel1inner[$AdType][$dateindex]['covg'] = $datalevel1innermad[$AdType][$dateindex]['madreq'] > 0 ? number_format(($datalevel1innermad[$AdType][$dateindex]['madreq']*100)/$datalevel1inner[$AdType][$dateindex]['adreq'],1) :0.00;
     @$datalevel1inner[$AdType][$dateindex]['ctr'] = $datalevel1inner[$AdType][$dateindex]['adimpr'] > 0 ? number_format($clicklevel1inner[$AdType][$dateindex]['clicks']/$datalevel1inner[$AdType][$dateindex]['adimpr']*100,1):0.00;
     @$revenuelevel1inner[$AdType][$dateindex]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
     @$datalevel1inner[$AdType][$dateindex]['ecpmx'] = $datalevel1inner[$AdType][$dateindex]['adimpr'] > 0 ? number_format(floor(($revenuelevel1inner[$AdType][$dateindex]['revenue_cmsShare']/$datalevel1inner[$AdType][$dateindex]['adimpr']*1000)*100)/100, 2) : 0.00;
     @$datalevel1inner[$AdType][$dateindex]['revenue_cmsShare']=$revenuelevel1inner[$AdType][$dateindex]['revenue_cmsShare'];

    #Level 1 table data
    #repname means dfp ad unit
    $repname= str_replace(")","",str_replace("(","",str_replace(" ","",$rowadtype['rep_name'])));
     
      #level 1 outer
    @$sumuplevel2_array[$AdType][$repname]['rep_namelvl1']=$repname;
    @$sumuplevel2_array[$AdType][$repname]['adrlvl1']+=$rowadtype['adr'];
    @$sumuplevel2_array[$AdType][$repname]['adimrlvl1']+=$rowadtype['adimr'];
    @$sumuplevel2_array[$AdType][$repname]['madrlvl1']+=$rowadtype['madr'];
    @$sumuplevel2_array[$AdType][$repname]['fillrate'] = number_format($sumuplevel2_array[$AdType][$repname]['adimrlvl1']/$sumuplevel2_array[$AdType][$repname]['adrlvl1']*100,1);
    @$click2_array[$AdType][$repname]['clickslvl1']+=$rowadtype['clicks'];
    @$sumuplevel2_array[$AdType][$repname]['covglvl1'] = $sumuplevel2_array[$AdType][$repname]['madrlvl1'] > 0 ? number_format(($sumuplevel2_array[$AdType][$repname]['madrlvl1']*100)/$sumuplevel2_array[$AdType][$repname]['adrlvl1'],1) :0.00;    
    @$sumuplevel2_array[$AdType][$repname]['ctr'] = $sumuplevel2_array[$AdType][$repname]['adimrlvl1'] > 0 ? number_format($click2_array[$AdType][$repname]['clickslvl1']/$sumuplevel2_array[$AdType][$repname]['adimrlvl1']*100,1):0.00;
    @$rev2_array[$AdType][$repname]['revenue_cmsSharelvl1']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
    @$sumuplevel2_array[$AdType][$repname]['ecpmxlvl1'] = $sumuplevel2_array[$AdType][$repname]['adimrlvl1'] > 0 ? number_format(floor(($rev2_array[$AdType][$repname]['revenue_cmsSharelvl1']/$sumuplevel2_array[$AdType][$repname]['adimrlvl1']*1000)*100)/100, 2) : 0.00;
    @$sumuplevel2_array[$AdType][$repname]['revenue_cmsSharelvl1']=$rev2_array[$AdType][$repname]['revenue_cmsSharelvl1'];

    }
    
    $request_array['sum_table_data'][] = $total_array;
    #Date reverse date wise
    foreach($datalevel1inner as $key=>$value1)
        {
            foreach($value1 as $innervalue1)
            {
            $req_array['adtype_innertable_data'][$key][]= $innervalue1;    
        }
      }

     
      #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = $req_array['adtype_innertable_data'][$ky];
      } 
       #sorted revenue wise
    foreach($sumuplevel2_array as $k=>$val)
        {
            aasort($val,'revenue_cmsSharelvl1');
            foreach(array_reverse($val) as $value2)
            {
        $dataarray_array['adtype_table_data_lvl1'][$k][]=array(
            'level1value'=>$k,
            'rep_namelvl1'=>$value2['rep_namelvl1'],
            'adrlvl1'=>$value2['adrlvl1'],
            'adimrlvl1'=>$value2['adimrlvl1'],
            //'madrlvl1'=>$value2['madrlvl1'],
            'fillratelvl1'=>number_format($value2['fillrate'],2),
            'covglvl1'=>number_format($value2['covglvl1'],2),
            'ctrlvl1'=>number_format($value2['ctrlvl1'],2),
            'ecpmxlvl1'=>number_format($value2['ecpmxlvl1'],2),
            'revenue_cmsSharelvl1'=>number_format($value2['revenue_cmsSharelvl1'],2)
                                            );
            }
         
       }
        

     
    
    $filename = "Auxo_Network_Report_Video_Adtypes_".$uniqid.".csv";

$filepath = "./upload/".$filename;

$fp = fopen($filepath,"w");
$blank=array("\n","\n");

foreach($sumuplevel_array as $row=>$value)  {
    array_pop($value);
    
    $header=array("--Ad Types--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    fputcsv($fp, $value);
    
    fputcsv($fp,$blank);
    $header=array("--Date--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);
        
    foreach($sumuplevel_array[$row]['innerdata'] as $row2)  {

        fputcsv($fp, $row2);
    }
    
    fputcsv($fp,$blank);
    $header=array("--Ad Types--","--Ad Units--","--Ad Requests--","--Ad Impressions--","--Fill Rate--","--Coverage--","--CTR--","--Estimated eCPM--","--Estimated Earnings--");
    fputcsv($fp,$header);

    foreach($dataarray_array['adtype_table_data_lvl1'][$row] as $row3){
        
    fputcsv($fp, $row3);
    }
        fputcsv($fp,$blank);
        fputcsv($fp,$blank);
        
}

//Close the file handle.
fclose($fp);
 return $filename;
         
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