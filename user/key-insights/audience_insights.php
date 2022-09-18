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
//date_default_timezone_set('Asia/Kolkata');
date_default_timezone_set("Pacific/Honolulu");
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Analytics.php';

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
     
    if(!empty($result_ads) || !empty($result_dis)){
           
         #calculation
          $data = prepareData($result_ads,$result_dis,$result_siteUrl,$data->uniq_id,$data->account_id,$dbMongoDb,$start_date,$end_date);
           
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

$userinArray = array();
foreach ($siteurl as $profileids) {

    $profileid = $profileids['profile_id'];
    $siteurl['site_url'] = $profileids['site_url'];
    $csvfile = $uniqid.".consumerinsight_".$profileid;
    $csvbasicfile = $uniqid.".consumerinsight_basic_".$profileid;

    $where = ['ACCOUNT_ID'=>['$eq'=>(int)$accountid]];
    $query = new MongoDB\Driver\Query($where);
    $resultL = $manager->executeQuery($csvfile, $query);
    $basicresultL = $manager->executeQuery($csvbasicfile, $query);
    #$resultL1 = $manager->executeQuery($csvfile, $query);
    
    foreach ($basicresultL as $basicval) {

        if(strtolower($basicval->USERTYPE) == 'new visitor') {
            #usertype data
            $usertype = 'New Visitor';
            $userinArray[$usertype]['type'] = "Casual";
            @$userinArray[$usertype]['user'] += $basicval->USERS;
            #pageviews data
            @$userinArray[$usertype]['pageviews'] += $basicval->PAGE_VIEWS;
            
            @$userinArray[$usertype]['countuser'] += count($basicval->USERTYPE);
            if(is_numeric($basicval->USERS)) {
                #session_per_user
                @$userinArray[$usertype]['session'] +=(float)($basicval->SESSIONS_PER_USER);
                @$userinArray[$usertype]['session_per_user'] =round((($userinArray[$usertype]['session'])/($userinArray[$usertype]['countuser'])),2);
                #pages_per_session
                @$userinArray[$usertype]['pageview_per_session'] +=(float)($basicval->PAGE_VIEWS_PER_SESSION);
                // $userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageview_per_session']/$userinArray[$usertype]['countuser']),2);
                if( $userinArray[$usertype]['user'] > 0 ){
                @$userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageviews']/($userinArray[$usertype]['session_per_user']*$userinArray[$usertype]['user'])),2);
                  }else{
                       @$userinArray[$usertype]['pages_per_session'] = 0;
                  }
                #Bounce_Rate
                @$userinArray[$usertype]['bounce'] += (float)($basicval->BOUNCE_RATE);
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),1);
            }
        
            #time_onpage
            if($basicval->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($basicval->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                //@$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
                @$userinArray[$usertype]['time_onpage'] =$userinArray[$usertype]['raw_time'];
            }
        } #new visitor if end

        if(strtolower($basicval->USERTYPE) == 'returning visitor' ) {
            #usertype data
            $usertype = 'Returning Visitor';
            @$userinArray[$usertype]['type'] = "Returning";

            @$userinArray[$usertype]['user'] += $basicval->USERS;
            #pageviews data
            @$userinArray[$usertype]['pageviews'] += $basicval->PAGE_VIEWS;
            
        //$userinArray[$usertype]['countuser'] +=count(($basicval->USERTYPE)); By AD
            @$userinArray[$usertype]['countuser'] +=count($basicval->USERTYPE);
            if(is_numeric($basicval->USERS)) {
                #session_per_user
                @$userinArray[$usertype]['session'] +=(float)($basicval->SESSIONS_PER_USER);
                @$userinArray[$usertype]['session_per_user'] =round((($userinArray[$usertype]['session'])/($userinArray[$usertype]['countuser'])),2);
                #pages_per_session
                @$userinArray[$usertype]['pageview_per_session'] +=(float)($basicval->PAGE_VIEWS_PER_SESSION);
                // $userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageview_per_session']/$userinArray[$usertype]['countuser']),2);
                if( $userinArray[$usertype]['user'] > 0 ){
                $userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageviews']/($userinArray[$usertype]['session_per_user']*$userinArray[$usertype]['user'])),2);
                  }else{
                    $userinArray[$usertype]['pages_per_session'] = 0;
                  }
                #Bounce_Rate
                @$userinArray[$usertype]['bounce'] += (float)($basicval->BOUNCE_RATE);
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),1);
            }
            
            #time_onpage
            if($basicval->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($basicval->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                //@$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
                @$userinArray[$usertype]['time_onpage'] =$userinArray[$usertype]['raw_time'];
            }
        }#returning visitor end

    } #loop end


   

    foreach ($resultL as $val) {

        if(strtolower($val->USERTYPE) == 'returning visitor' && ($val->SESSIONS_PER_USER) >= 5) {
            //usertype data
            $usertype = 'Loyal Visitor';
            $userinArray[$usertype]['type'] = "Loyal";

            @$userinArray[$usertype]['user'] += ($val->USERS);
            //pageviews data
            @$userinArray[$usertype]['pageviews'] += ($val->PAGE_VIEWS);
            
            @$userinArray[$usertype]['countuser'] +=count($val->USERTYPE);
            if(is_numeric($val->USERS)) {
                //session_per_user
                @$userinArray[$usertype]['session'] +=(float)($val->SESSIONS_PER_USER);
                @$userinArray[$usertype]['session_per_user'] =round((($userinArray[$usertype]['session'])/($userinArray[$usertype]['countuser'])),2);
                //pages_per_session
                @$userinArray[$usertype]['pageview_per_session'] +=(float)($val->PAGE_VIEWS_PER_SESSION);
                // $userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageview_per_session']/$userinArray[$usertype]['countuser']),2);
                if( $userinArray[$usertype]['user'] > 0 ){
                @$userinArray[$usertype]['pages_per_session'] =round(($userinArray[$usertype]['pageviews']/($userinArray[$usertype]['session_per_user']*$userinArray[$usertype]['user'])),2);
                }
                else{
                    @$userinArray[$usertype]['pages_per_session'] = 0;
                }
                //Bounce_Rate
                @$userinArray[$usertype]['bounce'] += (float)($val->BOUNCE_RATE);
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),1);
            }
            
            //time_onpage
            if($val->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($val->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                //@$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
                @$userinArray[$usertype]['time_onpage'] =$userinArray[$usertype]['raw_time'];
            }
        
        
        }
    }#$resultL loop end


    /***Users and PageViews Overview****/

$command_lplist = new MongoDB\Driver\Command([
        'aggregate' => "consumerinsight_daywise_".$profileid,
        'pipeline' => [
         ['$group' => ['_id' =>['date'=> '$date'], 'USERS' => ['$sum' => '$USERS'], 'PAGE_VIEWS' => ['$sum' => '$PAGE_VIEWS']]],
            ['$sort'=>['_id'=>1]]
        ],
        'cursor' => new stdClass,
    ]);
    $result_userpage = $manager->executeCommand($uniqid,$command_lplist);
    $result_userpages = $result_userpage->toArray();

foreach($result_userpages as $valuserpage){
         
     @$userpageinArray[$valuserpage->_id->date]['date'] = date("d M",strtotime($valuserpage->_id->date));
     @$userpageinArray[$valuserpage->_id->date]['user'] += $valuserpage->USERS;
     @$userpageinArray[$valuserpage->_id->date]['pageviews'] += $valuserpage->PAGE_VIEWS;
    $date_arr_userviews[] = $valuserpage->_id->date;
}

/***Users and PageViews Overview****/

}#$siteusrl loop end

if(!empty($date_arr_userviews)){
 $start_date_userpageviews = $date_arr_userviews[0];
 $end_date_userpageviews = end($date_arr_userviews);    
}else{

 $start_date_userpageviews = date('Y-m-d',strtotime("-10 days"));
 $end_date_userpageviews = date('Y-m-d',strtotime("-1 days"));
}

$request_array['date_range_userpageviews'] = date("d M",strtotime($start_date_userpageviews))." - ".date("d M, Y",strtotime($end_date_userpageviews));

$request_array['date_range'] = date("d M",strtotime($start_date))." - ".date("d M, Y",strtotime($end_date));


if(!empty($userinArray)){
$request_array['audience_user_profile'][]=array("New"=>$userinArray['New Visitor']['user'],"Returning"=>$userinArray['Returning Visitor']['user'],"Loyal"=>$userinArray['Loyal Visitor']['user']);
$request_array['pageviews'][]=array("New"=>$userinArray['New Visitor']['pageviews'],"Returning"=>$userinArray['Returning Visitor']['pageviews'],"Loyal"=>$userinArray['Loyal Visitor']['pageviews']);
$request_array['pageviews_tooltip']=array(number_format($userinArray['New Visitor']['pageviews']),number_format($userinArray['Returning Visitor']['pageviews']),number_format($userinArray['Loyal Visitor']['pageviews']));
}else{
    $request_array['audience_user_profile']=array();
    $request_array['pageviews']=array();
    $request_array['pageviews_tooltip']=array(0,0,0);
}
if(!empty($userinArray)){
foreach($userinArray as $sessVal){
  $request_array['session_user_duration']['users'][] = $sessVal['session_per_user'];
  $request_array['session_duration'][] = $sessVal['time_onpage']*1000;//seconds to milliseconds
  $request_array['pages_per_session'][] = $sessVal['pages_per_session'];
  $request_array['bounce_rate'][] = $sessVal['bounce_rate'];
  $request_array['session'][] = number_format($sessVal['session'],2);
}
}else{

  $request_array['session_user_duration']['users'] = array();
  $request_array['session_duration'] = array(); 
  $request_array['pages_per_session'] = array();
  $request_array['bounce_rate'] = array();
  $request_array['session'] = array();
}

/*** Page Load Time ***/
$startingdate = date("Y-m-d", strtotime('-8 day'));
$endingdate = date("Y-m-d", strtotime('-2 day'));
if(date('H') > 03) {
    $startingdate = date("Y-m-d",strtotime("-7 days"));
    $endingdate =  date("Y-m-d",strtotime("-1 days"));
}
else{
    $startingdate = date("Y-m-d",strtotime("-8 days"));
    $endingdate =  date("Y-m-d",strtotime("-2 days"));
} 
    $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => 'analy_pageloadtime',
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$startingdate,'$lte' =>$endingdate],'account_id'=>['$eq'=>(int)$accountid]]],
            ['$group' => ['_id' =>['date'=> '$date'], 'pageloadtime' => ['$sum' => '$pageloadtime']]],
            ['$sort'=>['_id'=>-1]]
        ],
        'cursor' => new stdClass,
    ]);
    $result_pageloadtime = $manager->executeCommand('analytics_db',$command_lplist);

    #$dateavail[] = array();//by ad
    foreach($result_pageloadtime as $pageval){

        $dateavail[]=$pageval->_id->date;
       
        $pageload_graph_data['time'][$pageval->_id->date]=+(float)round($pageval->pageloadtime,2);
    }
     
    while (strtotime($endingdate) >= strtotime($startingdate)) 
    {
    $date_array[]=$endingdate;
    $endingdate = date ("Y-m-d", strtotime("-1 day", strtotime($endingdate)));
    }

    foreach($date_array as $date_value)
    {
     if (in_array($date_value, $dateavail))
            {
                $final_sum_array['graph_data'][$date_value]=number_format($pageload_graph_data['time'][$date_value],1);
            }
            else
            {
                $final_sum_array['graph_data'][$date_value] = 0;
            }
    }
   
    foreach(array_reverse($final_sum_array['graph_data']) as $keyads=>$valueads)
        {
                    $request_array['page_load_data']['date'][]=date('d M', strtotime($keyads));
                    $request_array['page_load_data']['time'][]=floatval($valueads);
        }
    
    /*** End of Page Load Time ***/
    /*** Donut Audience Device ***/
    #previous week Start  date and end date
    $previous_weekD = strtotime("-1 week +1 day");
    $start_weekD = strtotime("last monday midnight",$previous_weekD);
    $end_weekD = strtotime("next sunday",$start_weekD);
    $start_weekD = date("Y-m-d",$start_weekD);
    $end_weekD = date("Y-m-d",$end_weekD);

    $request_array['date_range_donut'] = date("d M",strtotime($start_weekD))." - ".date("d M, Y",strtotime($end_weekD));
    #query
      $whereD = ['date'=>['$gte' => $start_weekD, '$lte' => $end_weekD], 'account_id'=>['$eq'=>$accountid]];
        $select_fields  = [];
        $optionsD        = [
            'projection'    => $select_fields,
            'sort'          => ['date' => -1]
        ];


        $queryD = new MongoDB\Driver\Query($whereD, $optionsD);
        $resultDonut = $manager->executeQuery('analytics_db.analy_ret_new', $queryD);
        $result_donutchart = $resultDonut->toArray();
          
    foreach ($result_donutchart as $val) 
         {
        if(strtolower($val->deviceCategory) == 'desktop'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType]+= $val->pageviews;
            }else if(strtolower($val->deviceCategory) == 'mobile'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType]+= $val->pageviews;
            }else if(strtolower($val->deviceCategory) == 'tablet'){
                $deviceType = $val->deviceCategory;
                @$deviceinArray[$deviceType] += $val->pageviews;
            }
      }
     $request_array['donut_chart_data']['Mobile']=$deviceinArray['mobile'];
     $request_array['donut_chart_data']['Desktop']=$deviceinArray['desktop'];
     $request_array['donut_chart_data']['Tablet']=$deviceinArray['tablet'];
      
      /*** Donut Audience Device End***/

    /***users and page views****/
    foreach($userpageinArray as $valUser){
       $request_array['user_pageviews_data']['date'][] = $valUser['date'];
       $request_array['user_pageviews_data']['users'][] = $valUser['user'];
       $request_array['user_pageviews_data']['pageviews'][] = $valUser['pageviews'];
    }
 
   return $request_array; 
}/***calculation function end*****/

?>