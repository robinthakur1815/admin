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
include_once '../../objects/DashboardPub.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$dashAdtype = new DashboardPub($db,$dbMongoDb);
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
     
     $dashAdtype->range = $data->range;
     $dashAdtype->strtdate = $data->strtdate;
     $dashAdtype->enddate = $data->enddate;
     $dashAdtype->child_net_code = $data->child_net_code;
     $result_unfilled = $dashAdtype->getAdtype();
     $dashAdtype->uniq_id = $data->uniq_id;
     //adsense
      $result_adsrequest = $dashAdtype->getAdsAdreq();
      $result_adsunfilled = $result_adsrequest->toArray();
      //pro
      $result_pro = $dashAdtype->getProData();
      $result_auxopro = $result_pro->toArray(); 

     if(!empty($result_unfilled['Display']) || !empty($result_unfilled['App']) || !empty($result_unfilled['Video']) || !empty($result_adsunfilled) || !empty($result_auxopro)){
        #calculation
        $data = prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_unfilled,$result_adsunfilled,$data->strtdate,$data->enddate,$result_auxopro);
         
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
    echo json_encode(array("message" => "Unable to get publisher dashboard. Data is incomplete.","status_code"=>400));
}

function prepareData($cmsShare,$cmsShareApp,$cmsShareVid,$result_adtype,$result_adsunfilled,$start,$end,$result_auxopro){

 #Date Array
        while (strtotime($start) <= strtotime($end))
        {
         
         $datearr[] = date('j M', strtotime($start));
         $date_arr[] = date('Y-m-d', strtotime($start));
         $start = date ("Y-m-d", strtotime("+1 day", strtotime($start)));
        }
  #mcm
   foreach ($result_adtype as $key => $rowadtypes) {

      foreach($rowadtypes as $rowadtype){

        $date = $rowadtype['date'];
        @$sumuplevel_array[$date]['date'] = $date;
        @$sumuplevel_array[$date]['adimr'] += $rowadtype['adimr'];
        if($key == 'Display'){
                 
                @$sumuplevel_array[$date]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShare),2);
             }
            if($key == 'App'){

                @$sumuplevel_array[$date]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShareApp),2);
                                
            }
            if($key == 'Video'){
                @$sumuplevel_array[$date]['revenue_cmsShare']+=round($rowadtype['revenue']-($rowadtype['revenue']*$cmsShareVid),2);
                
            }

      } //inner loop

    
} //loop end
if(!empty($sumuplevel_array)){
$mcmArr = get_sum_index($sumuplevel_array,$date_arr); //zero insert if date data not in database

foreach($mcmArr as $valueMcm)
{
       
 $mcm_arr[] = $valueMcm['revenue']; 
 $mcm_cpm_arr[] = $valueMcm['cpm']; 
   
}
$request_array[]=array(
                'name'=>"Auxo Network",
                'type'=>'column',
                'data'=>$mcm_arr);

}

#Auxo pro

foreach ($result_auxopro as $kypro => $rowdevicespro) { 

          $datepro = $rowdevicespro->_id->date;
        
         @$sumuplevel_array2[$datepro]['date'] = $datepro;
         @$sumuplevel_array2[$datepro]['adimr'] += $rowdevicespro->totalline_lvl_imp;
         @$sumuplevel_array2[$datepro]['revenue_cmsShare'] += round(($rowdevicespro->totalline_lvl_rev)-($rowdevicespro->totalline_lvl_rev*$cmsShare),2);
        
        

} 
if(!empty($sumuplevel_array2)){
$proArr = get_sum_index($sumuplevel_array2,$date_arr); //zero insert if date data not in database 
foreach($proArr as $valuePro)
{
       
 $pro_arr[] = $valuePro['revenue']; 
 $pro_cpm_arr[] = $valuePro['cpm']; 
   
}
$request_array[]=array(
                'name'=>"Auxo Pro",
                'type'=>'column',
                'data'=>$pro_arr);

}
#Adsense

foreach ($result_adsunfilled as $kyads => $rowdevicesAds) { 

          $dateUn = $rowdevicesAds->_id->date;
        
         @$sumuplevel_array1[$dateUn]['date'] = $dateUn;
         @$sumuplevel_array1[$dateUn]['adimr'] += $rowdevicesAds->totalad_imp;
         @$sumuplevel_array1[$dateUn]['revenue_cmsShare'] += $rowdevicesAds->total_earning;
        
        

} 
if(!empty($sumuplevel_array1)){
$adsArr = get_sum_index($sumuplevel_array1,$date_arr); //zero insert if date data not in database 
foreach($adsArr as $valueAds)
{
       
 $ads_arr[] = $valueAds['revenue']; 
 $ads_cpm_arr[] = $valueAds['cpm']; 
   
}
$request_array[]=array(
                'name'=>"Adsense",
                'type'=>'column',
                'data'=>$ads_arr);
}
#CPM for network, pro and adsense
$mar = array(
         "enabled"=> false,
          "symbol"=> "circle");
if(!empty($sumuplevel_array)){
$request_array[]=array(
                'name'=>"Auxo Network CPM",
                'type'=>'area',
                'marker'=>$mar,
                'yAxis'=>1,
                'data'=>$mcm_cpm_arr);}
if(!empty($sumuplevel_array2)){
$request_array[]=array(
                'name'=>"Auxo Pro CPM",
                'type'=>'area',
                'marker'=>$mar,
                'yAxis'=>1,
                'data'=>$pro_cpm_arr);
}
if(!empty($sumuplevel_array1)){
$request_array[]=array(
                'name'=>"Adsense CPM",
                'type'=>'area',
                'marker'=>$mar,
                'yAxis'=>1,
                'data'=>$ads_cpm_arr);
}

    $request_arr = array("trendcpm"=>$request_array,"date"=>$datearr);
  
    return $request_arr;
  } 

function get_sum_index($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'date'=> @$array_data[$date_value]['date'],
        'revenue'=> @$array_data[$date_value]['revenue_cmsShare'],
        //'cpm'=> @$array_data[$date_value]['adimr'] > 0 ? round(@$array_data[$date_value]['revenue_cmsShare']/@$array_data[$date_value]['adimr']*1000,2) : '0.00'
        'cpm'=> @$array_data[$date_value]['adimr'] > 0 ? round(floor((@$array_data[$date_value]['revenue_cmsShare']/@$array_data[$date_value]['adimr']*1000)*100)/100,2) : '0.00'
        );
    }
    else
    {
        $formatedarray[]=array(
        'date'=> date('Y-m-d', strtotime($date_value)),
        'revenue'=>0,
        'cpm'=>0
        );
    }
}

    return $formatedarray;
}
?>