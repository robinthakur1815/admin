<?php
#Author BY AD
class DashboardAuxo{

 #database connection and table name
    private $conn;
    private $table_display;
    private $table_app;
    private $table_video;
    private $table_adtype;
    private $table_adtype_app;
    private $table_adtype_video;
    private $table_adunit;
    private $table_adunit_app;
    private $table_adunit_video;
    private $table_sites;
    private $table_sites_app;
    private $table_sites_video;
    private $table_device;
    private $table_device_app;
    private $table_device_video;
    private $table_geo;

    #object properties
    public $uniq_id;
    public $child_net_code;
    public $child_net_codeApp;
    public $child_net_codeVid;
    public $strtdate;
    public $enddate;
    public $range;
    public $top_box_table;

    
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
		
    }
    public function getMemCache(){
      $memtest = new Memcached();
      $memtest->addServer("localhost", 11211);
      return $memtest;
    }
    public function getTableOverview($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_display = "disoverview_today";
           $this->table_app = "appoverview_today";
           $this->table_video = "videooverview_today";
          break;
        case "yesterday":
          $this->table_display = "disoverview_yesterday";
          $this->table_app = "appoverview_yesterday";
          $this->table_video = "videooverview_yesterday";
          break;
        case "7days":
          $this->table_display = "disoverview_7days";
          $this->table_app = "appoverview_7days";
          $this->table_video = "videooverview_7days";
          break;
         case "10days":
          $this->table_display = "disoverview_10days";
          $this->table_app = "appoverview_10days";
          $this->table_video = "videooverview_10days";
          break;  
        case "last30days":
          $this->table_display = "disoverview_30days";
          $this->table_app = "appoverview_30days";
          $this->table_video = "videooverview_30days";
          break;
        case "thismonth":
          $this->table_display = "disoverview_thismonth";
          $this->table_app = "appoverview_thismonth";
          $this->table_video = "videooverview_thismonth";
          break;
        case "lastmonth":
          $this->table_display = "disoverview_previousmonth";
          $this->table_app = "appoverview_previousmonth";
          $this->table_video = "videooverview_previousmonth";
          break;
        case "3month":
          $this->table_display = "disoverview_3month";
          $this->table_app = "appoverview_3month";
          $this->table_video = "videooverview_3month";
          break;        
        default:
          $this->table_display = "mcm_ad_exch_report";
          $this->table_app = "mcm_ad_exch_app_report";
          $this->table_video = "mcm_ad_exch_video_report";
      }
      return array("Display"=>$this->table_display,"App"=>$this->table_app,"Video"=>$this->table_video);
    }
	
    #get overview publisher data
    public function getOverview(){
      #get table name 
      $table = $this->getTableOverview($this->range);
      
      #get memcached class
      $memtest = $this->getMemCache();
      #query For Display
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
         $queryFetch = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM mcm_ad_exch_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
      }else{ 
      if($table['Display'] == "mcm_ad_exch_report"){
      $queryFetch = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
       }
        else{
          $queryFetch = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
        }
       } 
         #create memcache unique key
      $querykey = "KEY_".$this->child_net_code.'_'.md5($queryFetch);
      $result_cache = $memtest->get($querykey);
      if ($result_cache) {
      $assoc=$result_cache;
      #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";die;
      }else{
      #prepare query
      $row = $this->conn->prepare($queryFetch);
      #execute query 
      $row->execute();
      $stmt_result = $row->get_result();
      $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
      $assoc=$resp; // Results storing in var
      $memtest->set($querykey, $resp,1800);
      $rows = $stmt_result->num_rows;
      #echo "data comes from database";
      }
       #End Code For Display

      #query for App
      if($this->child_net_code == "demo_9999999"){
         $queryFetchApp = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM mcm_ad_exch_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
      }else{ 
      if($table['App'] == "mcm_ad_exch_app_report"){
      $queryFetchApp = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
       }
        else{
          $queryFetchApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
        }
      }
       #create memcache unique key
      $querykeyApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchApp);
      $result_cacheApp = $memtest->get($querykeyApp);
      if ($result_cacheApp) {
        $assocApp = $result_cacheApp;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
       }else{
        #prepare query
        $rowApp = $this->conn->prepare($queryFetchApp);
        #execute query 
        $rowApp->execute();
        $stmt_resultApp = $rowApp->get_result();
        $resApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
        $assocApp = $resApp; // Results storing in var
        $memtest->set($querykeyApp, $resApp,1800);
        $rowsApp = $stmt_resultApp->num_rows;
        #echo "data comes from database";
        
        }
       
      #query for video
      if($this->child_net_code == "demo_9999999"){
           $queryFetchVid = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM mcm_ad_exch_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';

      }else{  
      if($table['Video'] == "mcm_ad_exch_video_report"){
      $queryFetchVid = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
       }
        else{
          $queryFetchVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
        } 
      }  
     #create memcache unique key
      $querykeyVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchVid);
      $result_cacheVid = $memtest->get($querykeyVid);
      if ($result_cacheVid) {
        $assocVid = $result_cacheVid;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowVid = $this->conn->prepare($queryFetchVid);
        #execute query 
        $rowVid->execute();
        $stmt_resultVid = $rowVid->get_result();
        $respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
        $assocVid = $respVid; // Results storing in var
        $memtest->set($querykeyVid, $respVid,1800);
        $rowsVid = $stmt_resultVid->num_rows;
        #echo "data comes from database";
      }
      
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);

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
          // $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_earnings as revenue FROM mcm_adtypewise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
        $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM mcm_adtypewise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{
      if($table['Display'] == "mcm_adtypewise_report"){
       
       // $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
        $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
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
          // $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_earnings as revenue FROM mcm_adtypewise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
        $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM mcm_adtypewise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{
      if($table['App'] == "mcm_adtypewise_app_report"){
       
       // $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
       $queryFetchAdtypeApp = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
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
        // $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_adtypewise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
        $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM mcm_adtypewise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{
      if($table['Video'] == "mcm_adtypewise_video_report"){
       
       // $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
        $queryFetchAdtypeVid = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{

      $queryFetchAdtypeVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
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
        $rowsVid = $stmt_resultVid->num_rows;
        #echo "data comes from database";
      }
      
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
    #get table name ad type
    public function getTableAdunit($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_adunit = "disadunit_today";
          $this->table_adunit_app = "appadunit_today";
          $this->table_adunit_video = "videoadunit_today";
          break;
        case "yesterday":
          $this->table_adunit = "disadunit_yesterday";
          $this->table_adunit_app = "appadunit_yesterday";
          $this->table_adunit_video = "videoadunit_yesterday";
          break;
        case "7days":
          $this->table_adunit = "disadunit_7days";
          $this->table_adunit_app = "appadunit_7days";
          $this->table_adunit_video = "videoadunit_7days";
          break;
        case "10days":
          $this->table_adunit = "disadunit_10days";
          $this->table_adunit_app = "appadunit_10days";
          $this->table_adunit_video = "videoadunit_10days";
          break;  
        case "last30days":
          $this->table_adunit = "disadunit_30days";
          $this->table_adunit_app = "appadunit_30days";
          $this->table_adunit_video = "videoadunit_30days";
          break;
        case "thismonth":
          $this->table_adunit = "disadunit_thismonth";
          $this->table_adunit_app = "appadunit_thismonth";
          $this->table_adunit_video = "videoadunit_thismonth";
          break;
        case "lastmonth":
          $this->table_adunit = "disadunit_previousmonth";
          $this->table_adunit_app = "appadunit_previousmonth";
          $this->table_adunit_video = "videoadunit_previousmonth";
          break;
        case "3month":
          $this->table_adunit = "disadunit_3month";
          $this->table_adunit_app = "appadunit_3month";
          $this->table_adunit_video = "videoadunit_3month";
          break;        
        default:
          $this->table_adunit = "mcm_adunitwise_report";
          $this->table_adunit_app = "mcm_adunitwise_app_report";
          $this->table_adunit_video = "mcm_adunitwise_video_report";
      }
     
      return array("Display"=>$this->table_adunit,"App"=>$this->table_adunit_app,"Video"=>$this->table_adunit_video);
    }
    #get adunits data
    public function getAdunits(){
     
      #get table name 
      $table = $this->getTableAdunit($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query For Display
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
         $queryFetchAdunit = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM mcm_adunitwise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{
      if($table['Display'] == "mcm_adunitwise_report"){
       
       $queryFetchAdunit = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{

      $queryFetchAdunit = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
        #create memcache unique key
        $querykeyAu = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdunit);
        $result_cache = $memtest->get($querykeyAu);
      if ($result_cache) {
        $assoc=$result_cache;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $row = $this->conn->prepare($queryFetchAdunit);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $assoc=$resp; // Results storing in var
        $memtest->set($querykeyAu, $resp,1800);
        $rows = $stmt_result->num_rows;
        #echo "data comes from database";
      }
      #End Code For Display Adunits
      #query For App
      #query
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetchAdunitApp = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM mcm_adunitwise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{
      if($table['App'] == "mcm_adunitwise_app_report"){
       
       $queryFetchAdunitApp = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{

      $queryFetchAdunitApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
        #create memcache unique key
        $querykeyAuApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdunitApp);
        $result_cacheApp = $memtest->get($querykeyAuApp);
      if ($result_cacheApp) {
        $assocApp = $result_cacheApp;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowApp = $this->conn->prepare($queryFetchAdunitApp);
        #execute query 
        $rowApp->execute();
        $stmt_resultApp = $rowApp->get_result();
        $respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
        $assocApp = $respApp; // Results storing in var
        $memtest->set($querykeyAuApp, $respApp,1800);
        $rowsApp = $stmt_resultApp->num_rows;
        #echo "data comes from database";
      }
      #query for video
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
            $queryFetchAdunitVid = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM mcm_adunitwise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{
      if($table['Video'] == "mcm_adunitwise_video_report"){
       
       $queryFetchAdunitVid = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{

      $queryFetchAdunitVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
        #create memcache unique key
        $querykeyAuVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchAdunitVid);
        $result_cacheVid = $memtest->get($querykeyAuVid);
      if ($result_cacheVid) {
        $assocVid = $result_cacheVid;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowVid = $this->conn->prepare($queryFetchAdunitVid);
        #execute query 
        $rowVid->execute();
        $stmt_resultVid = $rowVid->get_result();
        $respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
        $assocVid = $respVid; // Results storing in var
        $memtest->set($querykeyAuVid, $respVid,1800);
        $rowsVid = $stmt_resultVid->num_rows;
        #echo "data comes from database";
      }
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
    #get table name Sites
    public function getTableSites($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_sites = "dissites_today";
          $this->table_sites_app = "appsites_today";
          $this->table_sites_video = "videosites_today";
          break;
        case "yesterday":
          $this->table_sites = "dissites_yesterday";
          $this->table_sites_app = "appsites_yesterday";
          $this->table_sites_video = "videosites_yesterday";
          break;
        case "7days":
          $this->table_sites = "dissites_7days";
          $this->table_sites_app = "appsites_7days";
          $this->table_sites_video = "videosites_7days";
          break;
         case "10days":
          $this->table_sites = "dissites_10days";
          $this->table_sites_app = "appsites_10days";
          $this->table_sites_video = "videosites_10days";
          break;  
        case "last30days":
          $this->table_sites = "dissites_30days";
          $this->table_sites_app = "appsites_30days";
          $this->table_sites_video = "videosites_30days";
          break;
        case "thismonth":
          $this->table_sites = "dissites_thismonth";
          $this->table_sites_app = "appsites_thismonth";
          $this->table_sites_video = "videosites_thismonth";
          break;
        case "lastmonth":
          $this->table_sites = "dissites_previousmonth";
          $this->table_sites_app = "appsites_previousmonth";
          $this->table_sites_video = "videosites_previousmonth";
          break;
        case "3month":
          $this->table_sites = "dissites_3month";
          $this->table_sites_app = "appsites_3month";
          $this->table_sites_video = "videosites_3month";
          break;        
        default:
          $this->table_sites = "mcm_domainwise_report";
          $this->table_sites_app = "mcm_domainwise_app_report";
          $this->table_sites_video = "mcm_domainwise_video_report";
      }
     
      return array("Display"=>$this->table_sites,"App"=>$this->table_sites_app,"Video"=>$this->table_sites_video);
    }
    #get Sites data
    public function getSites(){
     
      #get table name 
      $table = $this->getTableSites($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query For Display 
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
           $queryFetchSites = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_domainwise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{
      if($table['Display'] == "mcm_domainwise_report"){
       
       $queryFetchSites = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{

      $queryFetchSites = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
        #create memcache unique key
        $querykeySites = "KEY_".$this->child_net_code.'_'.md5($queryFetchSites);
        $result_cache = $memtest->get($querykeySites);
      if ($result_cache) {
        $assoc=$result_cache;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $row = $this->conn->prepare($queryFetchSites);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $assoc=$resp; // Results storing in var
        $memtest->set($querykeySites, $resp,1800);
        $rows = $stmt_result->num_rows;
        #echo "data comes from database";
      }
       #End Code For Display

      #query for App
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
         $queryFetchSitesApp = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_domainwise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{
       if($table['App'] == "mcm_domainwise_app_report"){
       
       $queryFetchSitesApp = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{

        $queryFetchSitesApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
        #create memcache unique key
        $querykeySitesApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchSitesApp);
        $result_cacheApp = $memtest->get($querykeySitesApp);
      if ($result_cacheApp) {
        $assocApp=$result_cacheApp;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
          #prepare query
          $rowApp = $this->conn->prepare($queryFetchSitesApp);
          #execute query 
          $rowApp->execute();
          $stmt_resultApp = $rowApp->get_result();
          $respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
          $assocApp = $respApp; // Results storing in var
          $memtest->set($querykeySitesApp, $respApp,1800);
          $rowsApp = $stmt_resultApp->num_rows;
          #echo "data comes from database";
      }
      #query for video
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
          $queryFetchSitesVid = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_domainwise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{
      if($table['Video'] == "mcm_domainwise_video_report"){
       
       $queryFetchSitesVid = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{

      $queryFetchSitesVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
        #create memcache unique key
        $querykeySitesVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchSitesVid);
        $result_cacheVid = $memtest->get($querykeySitesVid);
      if ($result_cacheVid) {
        $assocVid = $result_cacheVid;
        #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
      }else{
        #prepare query
        $rowVid = $this->conn->prepare($queryFetchSitesVid);
        #execute query 
        $rowVid->execute();
        $stmt_resultVid = $rowVid->get_result();
        $respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
        $assocVid = $respVid; // Results storing in var
        $memtest->set($querykeySitesVid, $respVid,1800);
        $rowsVid = $stmt_resultVid->num_rows;
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
          $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_devicecategory_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{
      if($table['Display'] == "mcm_devicecategory_report"){
       
       $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
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
        $rows = $stmt_result->num_rows;
        #echo "data comes from database";
      }
       #End Code For Display

      #query for App
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
           $queryFetchDeviceApp = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_devicecategory_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{
      if($table['App'] == "mcm_devicecategory_app_report"){
       
       $queryFetchDeviceApp = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDeviceApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
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
        $rowsApp = $stmt_resultApp->num_rows;
        #echo "data comes from database";
      }
      #query for video
       #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
           $queryFetchDeviceVid = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM mcm_devicecategory_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{
      if($table['Video'] == "mcm_devicecategory_video_report"){
       
       $queryFetchDeviceVid = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDeviceVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
      }}
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
        $rowsVid = $stmt_resultVid->num_rows;
        #echo "data comes from database";
      }
      return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
      
	#get table name Geo
    public function getTableGeodash($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_geo = "disgeo_today";
          $this->table_geo_app = "appgeo_today";
          $this->table_geo_video = "videogeo_today";
          break;
        case "yesterday":
          $this->table_geo = "disgeo_yesterday";
          $this->table_geo_app = "appgeo_yesterday";
          $this->table_geo_video = "videogeo_yesterday";
          break;
        case "7days":
          $this->table_geo = "disgeo_7days";
          $this->table_geo_app = "appgeo_7days";
          $this->table_geo_video = "videogeo_7days";
          break;
        case "10days":
          $this->table_geo = "disgeo_10days";
          $this->table_geo_app = "appgeo_10days";
          $this->table_geo_video = "videogeo_10days";
          break;  
        case "last30days":
          $this->table_geo = "disgeo_30days";
          $this->table_geo_app = "appgeo_30days";
          $this->table_geo_video = "videogeo_30days";
          break;
        case "thismonth":
          $this->table_geo = "disgeo_thismonth";
          $this->table_geo_app = "appgeo_thismonth";
          $this->table_geo_video = "videogeo_thismonth";
          break;
        case "lastmonth":
          $this->table_geo = "disgeo_previousmonth";
          $this->table_geo_app = "appgeo_previousmonth";
          $this->table_geo_video = "videogeo_previousmonth";
          break;
        case "3month":
          $this->table_geo = "disgeo_3month";
          $this->table_geo_app = "appgeo_3month";
          $this->table_geo_video = "videogeo_3month";
          break;        
        default:
          $this->table_geo = "mcm_countrywise_report";
          $this->table_geo_app = "mcm_countrywise_app_report";
          $this->table_geo_video = "mcm_countrywise_video_report";
      }
      return array("Display"=>$this->table_geo,"App"=>$this->table_geo_app,"Video"=>$this->table_geo_video);
      
    }
	#get geo data
    public function getGeodash(){
     
		#get table name 
		$table = $this->getTableGeodash($this->range);
		#get memcached class
		$memtest = $this->getMemCache();
		#query
     #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
            $queryFetchGeo = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_countrywise_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
      }else{
		if($table['Display'] == "mcm_countrywise_report"){

			$queryFetchGeo = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
		}else{
			$queryFetchGeo = 'SELECT * FROM ' . $table['Display'] . ' where child_net_code ="'.$this->child_net_code.'"';
		}}
		#create memcache unique key
		$querykeyGeo = "KEY_".$this->child_net_code.'_'.md5($queryFetchGeo);
		$result_cache = $memtest->get($querykeyGeo);
		if ($result_cache) {
			$assoc=$result_cache;
			#print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
		}else{
			#prepare query
			$row = $this->conn->prepare($queryFetchGeo);
			#execute query 
			$row->execute();
			$stmt_result = $row->get_result();
			$resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
			$assoc=$resp; // Results storing in var
			$memtest->set($querykeyGeo, $resp,1800);
			$rows = $stmt_result->num_rows;
			#echo "data comes from database";
		}
		#End Code For Display Adtype
		#query For App
     #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
         $queryFetchGeoApp = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_countrywise_app_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
      }else{
		if($table['App'] == "mcm_countrywise_app_report"){
			$queryFetchGeoApp = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
		}else{
			$queryFetchGeoApp = 'SELECT * FROM ' . $table['App'] . ' where child_net_code ="'.$this->child_net_code.'"';
		}
   }
		#create memcache unique key
		$querykeyGeoApp = "KEY_".$this->child_net_code.'_'.md5($queryFetchGeoApp);
		$result_cacheApp = $memtest->get($querykeyGeoApp);
		if ($result_cacheApp) {
			$assocApp=$result_cacheApp;
			#print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
		}else{
			#prepare query
			$rowApp = $this->conn->prepare($queryFetchGeoApp);
			#execute query 
			$rowApp->execute();
			$stmt_resultApp = $rowApp->get_result();
			$respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
			$assocApp=$respApp; // Results storing in var
			$memtest->set($querykeyGeoApp, $respApp,1800);
			$rowsApp = $stmt_resultApp->num_rows;
			#echo "data comes from database";
		}
		#App Adtype end
		#query for video
     #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
         $queryFetchGeoVid = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_countrywise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
      }else{
		if($table['Video'] == "mcm_countrywise_video_report"){
			$queryFetchGeoVid = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
		}else{
			$queryFetchGeoVid = 'SELECT * FROM ' . $table['Video'] . ' where child_net_code ="'.$this->child_net_code.'"';
		}
   }
		#create memcache unique key
		$querykeyGeoVid = "KEY_".$this->child_net_code.'_'.md5($queryFetchGeoVid);
		$result_cacheVid = $memtest->get($querykeyGeoVid);
		if($result_cacheVid){
			$assocVid=$result_cacheVid;
			#print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
		}else{
			#prepare query
			$rowVid = $this->conn->prepare($queryFetchGeoVid);
			#execute query 
			$rowVid->execute();
			$stmt_resultVid = $rowVid->get_result();
			$respVid = $stmt_resultVid->fetch_all(MYSQLI_ASSOC);
			$assocVid=$respVid; // Results storing in var
			$memtest->set($querykeyGeoVid, $respVid,1800);
			$rowsVid = $stmt_resultVid->num_rows;
			#echo "data comes from database";
		}
      
		return array("Display"=>$assoc,"App"=>$assocApp,"Video"=>$assocVid);
    }
 }
?>