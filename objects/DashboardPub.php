<?php
#Author BY AD
class DashboardPub{

 #database connection and table name
    private $conn;
    private $table_overview="adsense_daywise";

    #object properties
    public $uniq_id;
    public $child_net_code;
    public $strtdate;
    public $enddate;
    public $range;
    public $analytics_id;
    public $top_box_table;
    

    
    #constructor with $db as database connection
    public function __construct($db,$connMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $connMongoDb;
    }
    public function getMemCache(){
      $memtest = new Memcached();
      $memtest->addServer("localhost", 11211);
      return $memtest;
    }
    
    public function getAdvTrend(){
      $command_lplist1 = new MongoDB\Driver\Command([
    'aggregate' => 'advertisers',
    'pipeline' => [
        // ['$match'=>['date'=>['$gte' =>$start,'$lte' =>$end,]]],
    ['$group' => ['_id' => [
                'VERTICAL' => '$ADVERTISER_VERTICAL'
            ], 'IMPRESSION' => ['$sum' => '$IMPRESSIONS'], 'CPM' => ['$avg' => '$AD_ECPM']]],
      ['$sort'=>['_id'=>+1]]
      ],
      'cursor' => new stdClass,
  ]);
  $cursor_lplist1 = $this->connMongoDb->executeCommand('advertiser_details',$command_lplist1);

  foreach($cursor_lplist1 as $adv_result){
        $vert[]=array(
            'vertical'=>$adv_result->_id->VERTICAL,
            'impression'=>$adv_result->IMPRESSION,
            'CPM'=>round($adv_result->CPM,2)
        );
      }
    foreach($vert as $key=>$val) {
      $vertical[] = $val['vertical'];
      $impression[] = $val['impression'];
      $CPM[] = $val['CPM'];
    }  
     
    


      $request_array = array('vertical'=>$vertical,'impression'=>$impression,'CPM'=>$CPM);

       return $request_array;
    }

    #get table name ad type
    public function getTableAdtype($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_adtype = "disadtype_today";
          $this->table_adtype_app = "appadtype_today";
          $this->table_adtype_video = "videoadtype_today";
          break;
        case "yesterday":
          $this->table_adtype = "disadtype_yesterday";
          $this->table_adtype_app = "appadtype_yesterday";
          $this->table_adtype_video = "videoadtype_yesterday";
          break;
        case "7days":
          $this->table_adtype = "disadtype_7days";
          $this->table_adtype_app = "appadtype_7days";
          $this->table_adtype_video = "videoadtype_7days";
          break;
        case "10days":
          $this->table_adtype = "disadtype_10days";
          $this->table_adtype_app = "appadtype_10days";
          $this->table_adtype_video = "videoadtype_10days";
          break;  
        case "last30days":
          $this->table_adtype = "disadtype_30days";
          $this->table_adtype_app = "appadtype_30days";
          $this->table_adtype_video = "videoadtype_30days";
          break;
        case "thismonth":
          $this->table_adtype = "disadtype_thismonth";
          $this->table_adtype_app = "appadtype_thismonth";
          $this->table_adtype_video = "videoadtype_thismonth";
          break;
        case "lastmonth":
          $this->table_adtype = "disadtype_previousmonth";
          $this->table_adtype_app = "appadtype_previousmonth";
          $this->table_adtype_video = "videoadtype_previousmonth";
          break;
        case "3month":
          $this->table_adtype = "disadtype_3month";
          $this->table_adtype_app = "appadtype_3month";
          $this->table_adtype_video = "videoadtype_3month";
          break;        
        default:
          $this->table_adtype = "mcm_adtypewise_report";
          $this->table_adtype_app = "mcm_adtypewise_app_report";
          $this->table_adtype_video = "mcm_adtypewise_video_report";
      }
      return array("Display"=>$this->table_adtype,"App"=>$this->table_adtype_app,"Video"=>$this->table_adtype_video);
      
    }
    #get adtype data
    public function getAdtype(){
     
      #get table name 
      $table = $this->getTableAdtype($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

          $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type, mcm_impression as adimr, mcm_earnings as revenue,mcm_adreq as adr FROM mcm_adtypewise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';

      }else{
      if($table['Display'] == "mcm_adtypewise_report"){
       
       $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type, mcm_impression as adimr, mcm_earnings as revenue,mcm_adreq as adr FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{

      $queryFetchAdtype = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
    }
        #create memcache unique key
        $querykeyAd = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdtype);
        $result_cache = $memtest->get($querykeyAd);
      if ($result_cache) {
        $assoc=$result_cache;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $row = $this->conn->prepare($queryFetchAdtype);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $assoc=$resp; // Results storing in var
        $memtest->set($querykeyAd, $resp,1800);
        $rows = $stmt_result->num_rows;
        #echo "data comes from database";
      }
      #End Code For Display Adtype
      #query For App
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

          $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type, mcm_impression as adimr, mcm_earnings as revenue,mcm_adreq as adr FROM mcm_adtypewise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';

      }else{
      if($table['App'] == "mcm_adtypewise_app_report"){
       
       $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type, mcm_impression as adimr, mcm_earnings as revenue,mcm_adreq as adr FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{

      $queryFetchAdtypeApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
      #create memcache unique key
      $querykeyAdApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdtypeApp);
      $result_cacheApp = $memtest->get($querykeyAdApp);
      if ($result_cacheApp) {
        $assocApp = $result_cacheApp;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowApp = $this->conn->prepare($queryFetchAdtypeApp);
        #execute query 
        $rowApp->execute();
        $stmt_resultApp = $rowApp->get_result();
        $respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
        $assocApp = $respApp; // Results storing in var
        $memtest->set($querykeyAdApp, $respApp,1800);
       
        #echo "data comes from database";
      }
      #App Adtype end
      #query for video
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

          $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type, mcm_impression as adimr, mcm_earnings as revenue,mcm_adreq as adr FROM mcm_adtypewise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';

      }else{
      if($table['Video'] == "mcm_adtypewise_video_report"){
       
       $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type,mcm_impression as adimr,mcm_earnings as revenue,mcm_adreq as adr FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{

      $queryFetchAdtypeVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
        #create memcache unique key
        $querykeyAdVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdtypeVid);
        $result_cacheVid = $memtest->get($querykeyAdVid);
      if ($result_cacheVid) {
        $assocVid = $result_cacheVid;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowVid = $this->conn->prepare($queryFetchAdtypeVid);
        #execute query 
        $rowVid->execute();
        $stmt_resultVid = $rowVid->get_result();
        $respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
        $assocVid = $respVid; // Results storing in var
        $memtest->set($querykeyAdVid, $respVid,1800);
        
        #echo "data comes from database";
      }
     
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
     #get table name Device
    public function getTableDevice($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_device = "disdevice_today";
          $this->table_device_app = "appdevice_today";
          $this->table_device_video = "videodevice_today";
          break;
        case "yesterday":
          $this->table_device = "disdevice_yesterday";
          $this->table_device_app = "appdevice_yesterday";
          $this->table_device_video = "videodevice_yesterday";
          break;
        case "7days":
          $this->table_device = "disdevice_7days";
          $this->table_device_app = "appdevice_7days";
          $this->table_device_video = "videodevice_7days";
          break;
        case "10days":
          $this->table_device = "disdevice_10days";
          $this->table_device_app = "appdevice_10days";
          $this->table_device_video = "videodevice_10days";
          break;  
        case "last30days":
          $this->table_device = "disdevice_30days";
          $this->table_device_app = "appdevice_30days";
          $this->table_device_video = "videodevice_30days";
          break;
        case "thismonth":
          $this->table_device = "disdevice_thismonth";
          $this->table_device_app = "appdevice_thismonth";
          $this->table_device_video = "videodevice_thismonth";
          break;
        case "lastmonth":
          $this->table_device = "disdevice_previousmonth";
          $this->table_device_app = "appdevice_previousmonth";
          $this->table_device_video = "videodevice_previousmonth";
          break;
        case "3month":
          $this->table_device = "disdevice_3month";
          $this->table_device_app = "appdevice_3month";
          $this->table_device_video = "videodevice_3month";
          break;        
        default:
          $this->table_device = "mcm_devicecategory_report";
          $this->table_device_app = "mcm_devicecategory_app_report";
          $this->table_device_video = "mcm_devicecategory_video_report";
      }
      
      return array("Display"=>$this->table_device,"App"=>$this->table_device_app,"Video"=>$this->table_device_video);
    }
    #get Device data
    public function getDevice(){
     
      #get table name 
      $table = $this->getTableDevice($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query For Display 
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

            $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr,mcm_earnings as revenue FROM mcm_devicecategory_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';

         }else{
      if($table['Display'] == "mcm_devicecategory_report"){
       
       $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr,mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDevice = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
        #create memcache unique key
        $querykeyDevice = "KEY_".$this->child_net_code.'_'.md5($queryFetchDevice);
        $result_cache = $memtest->get($querykeyDevice);
      if ($result_cache) {
        $assoc=$result_cache;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $row = $this->conn->prepare($queryFetchDevice);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $assoc=$resp; // Results storing in var
        $memtest->set($querykeyDevice, $resp,1800);
        
        #echo "data comes from database";
      }
       #End Code For Display

      #query for App
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

            $queryFetchDeviceApp = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr,mcm_earnings as revenue FROM mcm_devicecategory_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';

         }else{
      if($table['App'] == "mcm_devicecategory_app_report"){
       
       $queryFetchDeviceApp = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr,mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDeviceApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
      } }
        #create memcache unique key
        $querykeyDeviceApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchDeviceApp);
        $result_cacheApp = $memtest->get($querykeyDeviceApp);
      if ($result_cacheApp) {
        $assocApp = $result_cacheApp;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowApp = $this->conn->prepare($queryFetchDeviceApp);
        #execute query 
        $rowApp->execute();
        $stmt_resultApp = $rowApp->get_result();
        $respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
        $assocApp = $respApp; // Results storing in var
        $memtest->set($querykeyDeviceApp, $respApp,1800);
        
        #echo "data comes from database";
      }
      #query for video
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){

            $queryFetchDeviceVid = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr,mcm_earnings as revenue FROM mcm_devicecategory_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';

         }else{
      if($table['Video'] == "mcm_devicecategory_video_report"){
       
       $queryFetchDeviceVid = 'SELECT ad_device_date as date,device_category as device,mcm_adreq as adr,mcm_impression as adimr, mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDeviceVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      } } 
        #create memcache unique key
        $querykeyDeviceVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchDeviceVid);
        $result_cacheVid = $memtest->get($querykeyDeviceVid);
      if ($result_cacheVid) {
        $assocVid = $result_cacheVid;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowVid = $this->conn->prepare($queryFetchDeviceVid);
        #execute query 
        $rowVid->execute();
        $stmt_resultVid = $rowVid->get_result();
        $respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
        $assocVid = $respVid; // Results storing in var
        $memtest->set($querykeyDeviceVid, $respVid,1800);
       
        #echo "data comes from database";
      }
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
     #get Ad request Adsense data
   public function getAdsAdreq(){
       $id = $this->uniq_id;
       //$id ="CHEE_120618_174513";
      #query
      $command_lplist = new MongoDB\Driver\Command([
          'aggregate' => 'adsense_devicewise',
          'pipeline' => [
              ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id'=>[
                      'device' => '$platform_type_name',
                      'date' => '$date'
                  ],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'total_earning' => ['$sum' => '$earnings']]]
          ],
          'cursor' => new stdClass,
      ]);
      $cursor_lplist = $this->connMongoDb->executeCommand($id,$command_lplist);
     
      return $cursor_lplist; 

    }
     #get overview publisher pro data
    public function getProData(){
      $id = $this->uniq_id;
       //$id ="UNIQ@1092019_111435";
      #query
      $command_lplist1 = new MongoDB\Driver\Command([
          'aggregate' => 'header_bidder',
          'pipeline' => [
              ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id' => [
                      // 'tot_fill_rate' => '$TOTAL_FILL_RATE',
                      'date' => '$DATE'
                  ], 'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'],'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_rev' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE']]],
          ['$sort'=>['_id'=>-1]]
          ],
          'cursor' => new stdClass,
      ]);

    
      $cursor_lplist1 = $this->connMongoDb->executeCommand($id,$command_lplist1);

    
     return $cursor_lplist1;

    }
    #get industry benchmark data
    public function getBenchmark(){
      
      $accountid = $this->analytics_id;
      $csvfile = "analytics_hourly.indbenchmark";

      $where      = ['Date'=>['$gte' => $this->strtdate, '$lte' => $this->enddate], 'ACCOUNT_ID'=>['$eq'=>(int)$accountid]];
      $query = new MongoDB\Driver\Query($where);
      $result = $this->connMongoDb->executeQuery($csvfile, $query);
      

      foreach ($result as $val) {
          $no_analytic_flag = true;
          @$userinArray['user'] += $val->USERS;
          //pageviews data
          @$userinArray['pageviews'] += $val->PAGE_VIEWS;
          //session_per_user
          if(is_numeric($val->USERS)) {
            @$userinArray['session'] +=(float)($val->SESSIONS);
            @$userinArray['session_per_user'] =round((float)(($userinArray['session'])/($userinArray['user'])),2);
            //pages_per_session
            @$userinArray['pageview_per_session'] +=(float)($val->PAGE_VIEWS_PER_SESSION);
            @$userinArray['countuser'] +=count(($val->USERTYPE));
            @$userinArray['pages_per_session'] =round((float)($userinArray['pageview_per_session']/$userinArray['countuser']),2);
            //Bounce_Rate
            @$userinArray['bounce'] += (float)($val->BOUNCE_RATE);
            @$userinArray['bounce_rate'] = round((float)($userinArray['bounce']/$userinArray['countuser']),1);
          }
          
          //time_onpage
          if($val->TIME_ON_PAGE > 0) {
            @$userinArray['time'] += (float)(($val->TIME_ON_PAGE)/1000);
            @$userinArray['time_onpage'] =round((float)(($userinArray['time']/$userinArray['countuser'])/7),2);
          }

          if(strtolower($val->USERTYPE) == 'returning visitor') {
            @$userinArray['ret'] += $val->USERS;
          }
          @$userinArray['return_users'] = round((float)($userinArray['ret']/$userinArray['user'])*100,1);
      }
        #get publisher domain and vertical 
        $website = "SELECT web_name as website,vertical FROM publishers_website WHERE pub_uniq_id='".$this->uniq_id."'";
         #prepare query
        $row = $this->conn->prepare($website);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $respWeb = $stmt_result->fetch_array(MYSQLI_ASSOC);

        #get all publishers uniq id  who has same vertical 
        $pubQuery = "SELECT pub_uniq_id FROM publishers_website WHERE vertical IN (SELECT vertical FROM publishers_website WHERE pub_uniq_id='".$this->uniq_id."')";
         #prepare query
        $rowPub = $this->conn->prepare($pubQuery);
        #execute query 
        $rowPub->execute();
        $result_pub = $rowPub->get_result();
        $respPubid = $result_pub->fetch_all(MYSQLI_ASSOC);

        foreach($respPubid as $fv)  {
            // ********** sql_fetch_profile ************
            $alluniqid = $fv['pub_uniq_id'];
            $sfp = "SELECT pub_analytics_id,pub_adsense_id FROM publisher_master WHERE pub_uniq_id='".$alluniqid."'";
            
               #prepare query
              $rowAnalId = $this->conn->prepare($sfp);
              #execute query 
              $rowAnalId->execute();
              $result_AnalId = $rowAnalId->get_result();
              $sfp = $result_AnalId->fetch_array(MYSQLI_ASSOC);
            if($sfp['pub_analytics_id'] != ''){
              $arr_sfp[] = (int)$sfp['pub_analytics_id'];
            }
            if($sfp['pub_adsense_id'] != ''){
                $all_adsenseid[] = "ca-".$sfp['pub_adsense_id'];
               }
          } //pubid loop end

         

          #get all publisher data
      $queryall = new MongoDB\Driver\Command([
            'aggregate' => 'indbenchmark',
            'pipeline' => [
            ['$match'=>['Date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate],'ACCOUNT_ID'=>['$in'=>(array)$arr_sfp]]]
            ],
            'cursor' => new stdClass,
            ]);
            $resultall = $this->connMongoDb->executeCommand('analytics_hourly',$queryall);

            foreach ($resultall as $val) {
            @$alluserinArray['user'] += $val->USERS;
            //pageviews data
            @$alluserinArray['pageviews'] += $val->PAGE_VIEWS;
            //session_per_user
            if(is_numeric($val->USERS)) {
            @$alluserinArray['session'] +=(float)($val->SESSIONS);
            @$alluserinArray['session_per_user'] =round(((float)($alluserinArray['session'])/($alluserinArray['user']))*0.5,2);
            //pages_per_session
            @$alluserinArray['pageview_per_session'] +=(float)($val->PAGE_VIEWS_PER_SESSION);
            @$alluserinArray['countuser'] +=count(($val->USERTYPE));
            @$alluserinArray['pages_per_session'] =round((float)($alluserinArray['pageview_per_session']/$alluserinArray['countuser'])*0.5,2);
            //Bounce_Rate
            @$alluserinArray['bounce'] += (float)($val->BOUNCE_RATE);
            @$alluserinArray['bounce_rate'] =round((float)($alluserinArray['bounce']/$alluserinArray['countuser']),1);
            }

            //time_onpage
            if($val->TIME_ON_PAGE > 0) {
            @$alluserinArray['time'] += (float)(($val->TIME_ON_PAGE)/1000);
            @$alluserinArray['time_onpage'] =round((float)(($alluserinArray['time']/$alluserinArray['countuser'])/7),2);
            }

            if(strtolower($val->USERTYPE) == 'returning visitor') {
             @$alluserinArray['ret'] += $val->USERS;
            }
             @$alluserinArray['return_users'] = round(((float)($alluserinArray['ret']/$alluserinArray['user'])*100)*0.5,1);
            } //loop end


             #get adsense id
            $ads_sql = "SELECT pub_adsense_id FROM publisher_master WHERE pub_uniq_id='".$this->uniq_id."'";
              #prepare query
              $rowAdsId = $this->conn->prepare($ads_sql);
              #execute query 
              $rowAdsId->execute();
              $result_AdsId = $rowAdsId->get_result();
              $respAds = $result_AdsId->fetch_array(MYSQLI_ASSOC);
               $adsenseId = "ca-".$respAds['pub_adsense_id'];

              $where_adsense      = ['date'=>['$gte' => $this->strtdate, '$lte' => $this->enddate], 'ad_client_id'=>['$eq'=>$adsenseId]];
              $query_adsense = new MongoDB\Driver\Query($where_adsense);
              $result_adsense = $this->connMongoDb->executeQuery('adsense_db.adsense_daywise', $query_adsense);
              $adsense_final = array();
              if(!empty($result_adsense)){
                foreach($result_adsense as $val_adsense) {
                
                  @$adsense_final['earnings'] += $val_adsense->earnings ;
                  @$adsense_final['clicks'] += $val_adsense->clicks ;
                  @$adsense_final['cpm'] = round($adsense_final['earnings']/$adsense_final['clicks'],2) ;
                  @$adsense_final['cpm'] = (($adsense_final['clicks'] > 0 && $adsense_final['earnings'] > 0 ) ? round($adsense_final['earnings']/$adsense_final['clicks'],2) : 0);
                  @$adsense_final['impressions'] += $val_adsense->impressions ;
                  @$adsense_final['ctr'] = round(($adsense_final['clicks']/$adsense_final['impressions'])*100,1) ;
              }
             }

              #all adsense publisher data
             $queryall_adsense = new MongoDB\Driver\Command([
                    'aggregate' => 'adsense_daywise',
                    'pipeline' => [
                      ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate],'ad_client_id'=>['$in'=>(array)$all_adsenseid]]]
                    ],
                    'cursor' => new stdClass,
                  ]);
                  $resultall_adsense = $this->connMongoDb->executeCommand('adsense_db',$queryall_adsense);

                 $alladsense_final = array();
                  foreach($resultall_adsense as $val_adsense) {
                    @$alladsense_final['earnings'] += $val_adsense->earnings ;
                    @$alladsense_final['clicks'] += $val_adsense->clicks ;
                    @$alladsense_final['cpm'] = round($alladsense_final['earnings']/$alladsense_final['clicks']*1.5,2) ;
                    @$alladsense_final['impressions'] += $val_adsense->impressions ;
                    @$alladsense_final['ctr'] = round(($alladsense_final['clicks']/$alladsense_final['impressions'])*100*1.5,1) ;
                  }
          $respWeb['website'] = (($respWeb['website'] != "" ) ? $respWeb['website'] : 'N/A');
          $respWeb['vertical'] = (($respWeb['vertical'] != "") ? $respWeb['vertical'] : 'N/A');

          $userinArray['session_per_user'] = (($userinArray['session_per_user'] > 0) ? $userinArray['session_per_user'] : '0');
          $alluserinArray['session_per_user'] = (($alluserinArray['session_per_user'] > 0) ? $alluserinArray['session_per_user'] : '0');

          $userinArray['pages_per_session'] = (($userinArray['pages_per_session'] > 0) ? $userinArray['pages_per_session'] : '0');
          $alluserinArray['pages_per_session'] = (($alluserinArray['pages_per_session'] > 0) ? $alluserinArray['pages_per_session'] : '0');

          $userinArray['time_onpage'] = gmdate("H:i:s", $userinArray['time_onpage']);
          $alluserinArray['time_onpage'] = gmdate("H:i:s", $alluserinArray['time_onpage']);

          $userinArray['time_onpage'] = (($userinArray['time_onpage'] != "00:00:00") ? $userinArray['time_onpage'] : '00:00:00');
          $alluserinArray['time_onpage'] = (($alluserinArray['time_onpage'] != "00:00:00") ? $alluserinArray['time_onpage'] : '00:00:00');

          $userinArray['bounce_rate'] = (($userinArray['bounce_rate'] > 0) ? $userinArray['bounce_rate'].' %' : '0%');
          $alluserinArray['bounce_rate'] = (($alluserinArray['bounce_rate'] > 0) ? $alluserinArray['bounce_rate'].' %' : '0%');

          $userinArray['return_users'] = (($userinArray['return_users'] > 0) ? $userinArray['return_users'].' %' : '0%');
          $alluserinArray['return_users'] = (($alluserinArray['return_users'] > 0) ? $alluserinArray['return_users'].' %' : '0%');

          @$adsense_final['cpm'] = (($adsense_final['cpm'] > 0) ? '$ '.$adsense_final['cpm'] : '$ 0');
          $alladsense_final['cpm'] = (($alladsense_final['cpm'] > 0) ? '$ '.$alladsense_final['cpm'] : '$ 0');

          @$adsense_final['ctr'] = (($adsense_final['ctr'] > 0) ? $adsense_final['ctr'].' %' : '0%');
          $alladsense_final['ctr'] = (($alladsense_final['ctr'] > 0) ? $alladsense_final['ctr'].' %' : '0%');

          $enddate = date('d M , Y',strtotime($this->strtdate));
          $strtdate = date('d M',strtotime($this->enddate));
          $finaldaterange= $strtdate." - ".$enddate;
          if($no_analytic_flag == 0) {
            $respFinal['ind_bench'][] = array('finaldaterange'=>$finaldaterange,'vertical'=>strtoupper($respWeb['vertical']),'message'=>'Please Share Your GA Admin Access To View How Much eCPM Your Industry Is Generating Vs Yours.', 'no_analytic_flag'=>$no_analytic_flag);
          }
          else{
            $respFinal['ind_bench'][]  = array('finaldaterange'=>$finaldaterange,'website'=>$respWeb['website'], 'vertical'=>strtoupper($respWeb['vertical']), 's_spu'=>$userinArray['session_per_user'], 'a_spu'=>$alluserinArray['session_per_user'],'s_pps'=>$userinArray['pages_per_session'], 'a_pps'=>$alluserinArray['pages_per_session'], 's_atp'=>$userinArray['time_onpage'], 'a_atp'=>$alluserinArray['time_onpage'], 's_br'=>$userinArray['bounce_rate'], 'a_br'=>$alluserinArray['bounce_rate'], 's_ru'=>$userinArray['return_users'], 'a_ru'=>$alluserinArray['return_users'], 's_ectr'=>$adsense_final['ctr'], 'a_ectr'=>$alladsense_final['ctr'], 's_cpm'=>$adsense_final['cpm'], 'a_cpm'=>$alladsense_final['cpm'], 'no_analytic_flag'=>$no_analytic_flag);
          }
             // echo "<pre>";
             // print_r($resp);die;
         return $respFinal;
    }
	
	
	
 }
?>