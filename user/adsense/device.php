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
include_once '../../objects/Adsense.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$adsense = new Adsense($db,$dbMongoDb);
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
     $result_dev = $adsense->getDevice();
     $result_device = $result_dev->toArray();
     if(!empty($result_device)){
        #calculation
        $data = prepareData($result_device,$data->strtdate,$data->enddate);
         
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
    echo json_encode(array("message" => "Unable to get adsense device. Data is incomplete.","status_code"=>400));
}
function prepareData($result_device,$start,$end){
 #Date Array
while (strtotime($start) <= strtotime($end))
{
 $date[] = date('j M', strtotime($start));
 $date_arr[] = date('Y-m-d', strtotime($start));
 $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
}
   
    foreach ($result_device as $key => $val) {
        if(strtolower($val->_id->device)=='high-end mobile devices') {
        $val->_id->device = str_replace($val->_id->device,"Mobile",$val->_id->device);
    }
        $device = str_replace(".","_",$val->_id->device);
        $dateindex = $val->_id->date;
        #Data merge for first slide by site wise
        @$sumuplevel_array[$device]['device']=$device;
        @$sumuplevel_array[$device]['adreq']+=$val->totalad_requests;
        @$sumuplevel_array[$device]['adimpr']+=$val->totalad_imp;
        @$sumuplevel_array[$device]['madreq']+=$val->totalmatchad_requests;
        @$sumuplevel_array[$device]['fillrate'] = $sumuplevel_array[$device]['adreq'] > 0 ? number_format($sumuplevel_array[$device]['adimpr']/$sumuplevel_array[$device]['adreq']*100,1):0.00;
        @$sumuplevel_array[$device]['clicks']+=$val->total_click;

        @$sumuplevel_array[$device]['covg'] = $sumuplevel_array[$device]['madreq'] > 0 ? number_format(($sumuplevel_array[$device]['madreq']*100)/$sumuplevel_array[$device]['adreq'],1) :0.00;

        @$sumuplevel_array[$device]['ctr'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format($sumuplevel_array[$device]['clicks']/$sumuplevel_array[$device]['adimpr']*100,1):0.00;

        @$sumuplevel_array[$device]['revenue_cmsShare'] += round($val->total_earning,2);

        @$sumuplevel_array[$device]['ecpm'] = $sumuplevel_array[$device]['adimpr'] > 0 ? number_format(floor(($sumuplevel_array[$device]['revenue_cmsShare']/$sumuplevel_array[$device]['adimpr']*1000)*100)/100, 2) : 0.00;
        @$sumuplevel_array[$device]['expanded']=false;

        @$arraylevel2[$device][date('j M', strtotime($val->_id->date))]+=round($val->total_earning,2);
        #Total row for first slide 
        @$total_array['adreq']+=$val->totalad_requests;
        @$total_array['adimpr']+=$val->totalad_imp;
        @$total_array['madreq']+=$val->totalmatchad_requests;
        @$total_array['fillrate']=number_format($total_array['adimpr']/$total_array['adreq']*100,1);
        @$total_array['clicks']+=$val->total_click;
        @$total_array['covg'] = $total_array['madreq'] > 0 ? number_format(($total_array['madreq']*100)/$total_array['adreq'],1) :0.00;
        @$total_array['ctr'] = $total_array['adimpr'] > 0 ? number_format($total_array['clicks']/$total_array['adimpr']*100,1):0.00;
        @$total_array_full['revenue_cmsShare'] += round($val->total_earning,2);
        @$total_array['revenue_cmsShare'] = number_format($total_array_full['revenue_cmsShare'],2);
        @$total_array['ecpm'] = $total_array['adimpr'] > 0 ? number_format(floor(($total_array_full['revenue_cmsShare']/$total_array['adimpr']*1000)*100)/100, 2) : 0.00;

    #first table page inner
    @$datalevel1inner[$device][$dateindex]['dateinner']= date('j M, Y', strtotime($val->_id->date));
    @$datalevel1inner[$device][$dateindex]['adrinner']+=$val->totalad_requests;
    @$datalevel1inner[$device][$dateindex]['adimrinner']+=$val->totalad_imp;
    @$datalevel1inner[$device][$dateindex]['madrinner']+=$val->totalmatchad_requests;
    @$datalevel1inner[$device][$dateindex]['clicksinner']+=$val->total_click;
    @$datalevel1inner[$device][$dateindex]['covginner'] = $datalevel1inner[$device][$dateindex]['madrinner'] > 0 ? number_format(($datalevel1inner[$device][$dateindex]['madrinner']*100)/$datalevel1inner[$device][$dateindex]['adrinner'],1) :0.00;
    @$datalevel1inner[$device][$dateindex]['ctrinner']+=$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format($datalevel1inner[$device][$dateindex]['clicksinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*100,1):0.00;
    @$datalevel1inner[$device][$dateindex]['revenue_cmsShareinner']+=round($val->total_earning,2);  
    @$datalevel1inner[$device][$dateindex]['ecpmxinner'] =$datalevel1inner[$device][$dateindex]['adimrinner'] > 0 ? number_format(floor(($datalevel1inner[$device][$dateindex]['revenue_cmsShareinner']/$datalevel1inner[$device][$dateindex]['adimrinner']*1000)*100)/100, 2) : 0.00;

   
    
   }
  
   #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
      /***level 1***/
    
     foreach ($sumuplevel_array as $k => $value) {
         
         $request_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['revenue_cmsShare'],2),
                                        'drilldown'=>$k,
                                    );

      }
    /***level 1***/ 

    /***level 2***/
    
      $mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
      foreach($arraylevel2 as $key2=>$value2)
        {
               
           $request_array['level2'][]=array(
                                            'name'=>$key2,
                                                'id'=>$key2,
                                                'type'=>'area',
                                                // 'data'=>array_reverse(get_sum_inner($value2,$date)),
                                                'data'=>get_sum_inner($value2,$date),
                                                 'marker'=>$mar,
                                            );
           
        }

      /***level 2***/ 
   #sorting revenue wise 
    aasort($sumuplevel_array,"revenue_cmsShare");
    #Merge innerdata of first page
    foreach ($sumuplevel_array as $ky => $value) {
         $sumuplevel_array[$ky]['innerdata'] = get_sum_index($datalevel1inner[$ky],$date_arr);
         //$sumuplevel_array[$ky]['innerdata'] = $req_array['sites_innertable_data'][$ky];
      }   
    foreach ($sumuplevel_array as $ky => $value) {
         $request_array['sites_table_data'][] = $value;
      }
   
   $request_array['sum_table_data'][] = $total_array;

   // $request_array['level3_dates']['dates'] = array_reverse($date); 
   $request_array['level3_dates']['dates'] = $date; 
    return $request_array;
         
}/***calculation function end*****/
function get_sum_inner($array_data_inner,$array_innerfulldate)
{

krsort($array_data_inner);


    foreach($array_innerfulldate as $date_value)
        {
        if (in_array($date_value, array_keys($array_data_inner)))
        { 
            $formatedinnerarray[]=round($array_data_inner[$date_value],2);
        }
        else
        {
            $formatedinnerarray[]=0;
        }
    }

     return $formatedinnerarray;
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

function get_sum_index($array_data,$array_fulldate)
{
   
//krsort($array_data);

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinner'=> @$array_data[$date_value]['dateinner'],
        'adrinner'=> @$array_data[$date_value]['adrinner'],
        'adimrinner'=> @$array_data[$date_value]['adimrinner'],
        'madrinner'=> @$array_data[$date_value]['madrinner'],
        'fillrate'=> number_format(@$array_data[$date_value]['adimrinner']/@$array_data[$date_value]['adrinner']*100,1),
        'covginner'=> number_format(@$array_data[$date_value]['covginner'],1),
        'ctrinner'=> number_format(@$array_data[$date_value]['ctrinner'],1),
        'ecpmxinner'=> number_format(@$array_data[$date_value]['ecpmxinner'],2),
        'revenue_cmsShareinner'=> number_format(@$array_data[$date_value]['revenue_cmsShareinner'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinner'=> date('j M, Y', strtotime($date_value)),
        'adrinner'=> 0,
        'adimrinner'=>0,
        'madrinner'=> 0,
        'fillrate'=> 0,
        'covginner'=> 0,
        'ctrinner'=> 0,
        'ecpmxinner'=> 0,
        'revenue_cmsShareinner'=> 0
        );
    }
}

    return $formatedarray;
}

?>