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
     $result_h = $pro->getDevice();
     
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
// while (strtotime($end) >= strtotime($start)) 
// {
//      $date[] = date('d M', strtotime($start));
//      $date_array[]=$end;
//      $end = date ("Y-m-d", strtotime("-1 day", strtotime($end)));
// }
while (strtotime($start) <= strtotime($end))
{
 // $date[] = date('d M', strtotime($start));
 $date[] = date('j M', strtotime($start));
 $date_array[] = date('Y-m-d', strtotime($start));
 $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
}    
foreach ($result_hb as $val) {

         if(strtolower($val->_id->category) == 'smartphone' || strtolower($val->_id->category) =='feature phone' || strtolower($val->_id->category) =='High-end mobile devices'){
           $device = "Mobile";
          }
          if(strtolower($val->_id->category)=='desktop') {
            $device = "Desktop";
            }
          if(strtolower($val->_id->category) == 'tablet'){
            $device = 'Tablets';
           }
           if(strtolower($val->_id->category) == 'connected tv'){
                $device='Connected_TV';}
        @$sumuplevel_array[$device]['device']=$device;
        @$sumuplevel_array[$device]['tot_lineitmlvl_imp']+=$val->totalline_lvl_imp;
        @$sumuplevel_array[$device]['tot_lineitmlvl_rev'] += round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);

        @$sumuplevel_array[$device]['tot_lineitmlvl_cpm'] = $sumuplevel_array[$device]['tot_lineitmlvl_imp'] > 0 ? number_format($sumuplevel_array[$device]['tot_lineitmlvl_rev']/$sumuplevel_array[$device]['tot_lineitmlvl_imp']*1000,2) : 0.00;

         
         @$arraylevel2[$device][date('j M', strtotime($val->_id->date))]+=round(($val->total_revenue)-($val->total_revenue*$cmsShare),2);

        
        
    }
     #sorting revenue wise 
    aasort($sumuplevel_array,"tot_lineitmlvl_rev");
    
/***level 1***/
    
     foreach ($sumuplevel_array as $k => $value) {
         
         $request_array['level1'][]=array(
                                        'name'=>$k,
                                        'y'=>round($value['tot_lineitmlvl_rev'],2),
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
                                                'data'=>get_sum_inner($value2,$date),
                                                 'marker'=>$mar,
                                            );
           
        }

      /***level 2***/ 

$request_array['level3_dates']['dates'] = $date;


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

function get_sum_index($array_data,$array_fulldate)
{
   
krsort($array_data);

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'dateinnerlvl1'=> @$array_data[$date_value]['dateinner'],
        'adimrinnerlvl1'=> @$array_data[$date_value]['adimrinner'],
        'ecpmxinnerlvl1'=> number_format(@$array_data[$date_value]['ecpmxinner'],2),
        'revenue_cmsShareinnerlvl1'=> number_format(@$array_data[$date_value]['revenue_cmsShareinner'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'dateinnerlvl1'=> date('j M, Y', strtotime($date_value)),
        'adimrinnerlvl1'=>0,
        'ecpmxinnerlvl1'=> 0,
        'revenue_cmsShareinnerlvl1'=> 0
        );
    }
}

    return $formatedarray;
}
?>