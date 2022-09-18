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
     // echo "<pre>";
     // print_r($result_siteUrl);die;
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
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),2)."%";
            }
        
            #time_onpage
            if($basicval->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($basicval->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                @$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
            }
        }

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
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),2)."%";
            }
            
            #time_onpage
            if($basicval->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($basicval->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                @$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
            }
        }
    }

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

        if(strtolower($val->USERTYPE) == 'new visitor' ||  strtolower($val->USERTYPE) == 'returning visitor' && ($val->SESSIONS_PER_USER) <=1) {
            #usertype data
            $usertype = 'New Visitor';
            @$userinArray[$usertype]['type'] = "Casual";
         
            #Direct User
            if(strtolower($val->MEDIUM) == '(none)') {
                $medium = 'Direct';
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);
            }
            
            #Social User
            elseif(strtolower($val->MEDIUM) == 'social' || strstr(strtolower($val->MEDIUM),"facebook") || strstr(strtolower($val->MEDIUM),"whatsapp") || strstr(strtolower($val->MEDIUM),"twitter") || strstr(strtolower($val->MEDIUM),"pinterest") || strstr(strtolower($val->MEDIUM),"linkedin") || strstr(strtolower($val->MEDIUM),"instagram")) {
                $medium = "Social";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);
            }
            #Referral User
            elseif(strtolower($val->MEDIUM) == 'referral') {
                $medium = "Referral";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);
            }
            #Organic User
            elseif(strtolower($val->MEDIUM) == 'organic') {
                $medium = "Organic";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Other User
            else {
                $medium = 'Other';
                $userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
        }
        if(strtolower($val->USERTYPE) == 'returning visitor' && ($val->SESSIONS_PER_USER) > 1 && ($val->SESSIONS_PER_USER) < 5) {
            #usertype data
            $usertype = 'Returning Visitor';
            $userinArray[$usertype]['type'] = "Returning";

            #Direct User
            if(strtolower($val->MEDIUM) == '(none)') {
                $medium = 'Direct';
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            #Social User
            elseif(strtolower($val->MEDIUM) == 'social' || strstr(strtolower($val->MEDIUM),"facebook") || strstr(strtolower($val->MEDIUM),"whatsapp") || strstr(strtolower($val->MEDIUM),"twitter") || strstr(strtolower($val->MEDIUM),"pinterest") || strstr(strtolower($val->MEDIUM),"linkedin") || strstr(strtolower($val->MEDIUM),"instagram")) {
                $medium = "Social";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Referral User
            elseif(strtolower($val->MEDIUM) == 'referral') {
                $medium = "Referral";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                #idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Organic User
            elseif(strtolower($val->MEDIUM) == 'organic') {
                $medium = "Organic";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages
                //$userinArray[$usertype][$medium]['ideal_user'][] = $val->USERS;
                @$userinArray[$usertype][$medium]['ideal_landingpage'][] = $val->LANDING_PAGE_PATH;
                @$userinArray[$usertype][$medium]['ideal_bounce'][] = round($val->BOUNCE_RATE,2);
                @$userinArray[$usertype][$medium]['ideal_pageview'][] = $val->PAGE_VIEWS;
                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);
                //$userinArray[$usertype][$medium]['ideal_session'][] = $val->SESSIONS;
            }
            //Other User
            else {
                $medium = 'Other';
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
        }
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
                @$userinArray[$usertype]['bounce_rate'] = round(($userinArray[$usertype]['bounce']/$userinArray[$usertype]['countuser']),2)."%";
            }
            
            //time_onpage
            if($val->AVG_SESSION_DURATION > 0) {
                @$userinArray[$usertype]['time'] += (float)($val->AVG_SESSION_DURATION);
                @$userinArray[$usertype]['raw_time'] =round(($userinArray[$usertype]['time']/$userinArray[$usertype]['countuser']));
                @$userinArray[$usertype]['time_onpage'] =gmdate("H:i:s",$userinArray[$usertype]['raw_time']);
            }
        
            //Direct User
            if(strtolower($val->MEDIUM) == 'Direct') {
                $medium = 'Direct';
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Social User
            elseif(strtolower($val->MEDIUM) == 'social' || strstr(strtolower($val->MEDIUM),"facebook") || strstr(strtolower($val->MEDIUM),"whatsapp") || strstr(strtolower($val->MEDIUM),"twitter") || strstr(strtolower($val->MEDIUM),"pinterest") || strstr(strtolower($val->MEDIUM),"linkedin") || strstr(strtolower($val->MEDIUM),"instagram")) {
                $medium = "Social";
                $userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Referral User
            elseif(strtolower($val->MEDIUM) == 'referral') {
                $medium = "Referral";
                @$userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                @$userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Organic User
            elseif(strtolower($val->MEDIUM) == 'organic') {
                $medium = "Organic";
                $userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                $userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
            //Other User
            else {
                $medium = 'Other';
                $userinArray[$usertype][$medium]['user'] += $val->USERS;
                //idealpages

                $agregate_pageview = 0;
                $agregate_pageview = (($val->PAGE_VIEWS/$total_pageviews)*100);
                $userinArray[$usertype][$medium]['ideal_pageview_percent'][$siteurl['site_url'].''.$val->LANDING_PAGE_PATH] += round(($agregate_pageview*$average_rev)/100,2);

            }
        
        }
        
        
    }
}
arsort($toplandingpages['pageview_percent']);
$counting = 1; 

foreach($toplandingpages['pageview_percent'] as $topkey=> $topvalue) {
    if($counting<=5) {
        $top_pages_of_site[] =array(
            "siteurl"=>$topkey,
            "earning"=>"$".round($topvalue,2),
            "pageviews"=> number_format($toplandingpages['pageviews'][$topkey]),
            "pageload"=> round(($toplandingpages['pageload'][$topkey])/$toplandingpages['occurence_count'][$topkey],2),
        );
        $counting++; 
    }
}

// print_r($toplandingpages['occurence_count']);die;
arsort($userinArray['New Visitor']['Direct']['ideal_pageview_percent']);
arsort($userinArray['Returning Visitor']['Direct']['ideal_pageview_percent']);
arsort($userinArray['Loyal Visitor']['Direct']['ideal_pageview_percent']);

arsort($userinArray['New Visitor']['Social']['ideal_pageview_percent']);
arsort($userinArray['Returning Visitor']['Social']['ideal_pageview_percent']);
arsort($userinArray['Loyal Visitor']['Social']['ideal_pageview_percent']);

arsort($userinArray['New Visitor']['Referral']['ideal_pageview_percent']);
arsort($userinArray['Returning Visitor']['Referral']['ideal_pageview_percent']);
arsort($userinArray['Loyal Visitor']['Referral']['ideal_pageview_percent']);

arsort($userinArray['New Visitor']['Organic']['ideal_pageview_percent']);
arsort($userinArray['Returning Visitor']['Organic']['ideal_pageview_percent']);
arsort($userinArray['Loyal Visitor']['Organic']['ideal_pageview_percent']);

arsort($userinArray['New Visitor']['Other']['ideal_pageview_percent']);
arsort($userinArray['Returning Visitor']['Other']['ideal_pageview_percent']);
arsort($userinArray['Loyal Visitor']['Other']['ideal_pageview_percent']);

$visitorname = array('New Visitor','Returning Visitor','Loyal Visitor');
$mediumname = array('Direct','Social','Referral', 'Organic','Other');

foreach($visitorname as $v) {
    foreach ($mediumname as $m) {
        $urlcount = 1;
        // $totaluserinmedium[$v][$m] = array('users'=>$userinArray[$v][$m]['user']);
        $countingkey=0;
        $countingkeys=0;
        $countingkey = count($userinArray[$v][$m]['ideal_pageview_percent']);
        if($countingkey >=5) {
            $countingkeys =5;
        }
        else{
            $countingkeys =$countingkey;
        }
        foreach ($userinArray[$v][$m]['ideal_pageview_percent'] as $fkey=>$fvalue) {
            if($urlcount<=5) {
                $mediumwise[$v][$m][] = array(
                    'visitor'=> $v,
                    'medium' => $m,
                    'idealpageurl' => $fkey,
                    'users'=>$userinArray[$v][$m]['user'],
                    'idealpageearn' => round($fvalue,2),
                    'countingkey' => $countingkeys
                );
            }
            $urlcount++;
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

$daterange[] = date("d M",strtotime($start_date))." - ".date("d M, Y",strtotime($end_date));
$final_array = array(
    "top_landingpage"=>$top_pages_of_site,
    "userinarray"=>$userinArray,
    // 'totaluserinmedium'=>$totaluserinmedium,
    'mediumwise'=>$final_mediumwise,
    "daterange"=>$daterange
);


   return $final_array; 
}/***calculation function end*****/

?>