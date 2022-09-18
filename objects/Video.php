<?php
#Author BY AD
class Video{

 #database connection and table name
    private $conn;
    private $table_display;
    private $table_adtype;
    private $table_adunit;
    private $table_sites;
    private $table_device;
    private $table_geo;

    #object properties
    public $uniq_id;
    public $child_net_code;
    public $strtdate;
    public $enddate;
    public $range;
    

    
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
          $this->table_display = "videooverview_today";
          break;
        case "yesterday":
          $this->table_display = "videooverview_yesterday";
          break;
        case "7days":
          $this->table_display = "videooverview_7days";
          break;
         case "10days":
          $this->table_display = "videooverview_10days";
          break; 
        case "last30days":
          $this->table_display = "videooverview_30days";
          break;
        case "thismonth":
          $this->table_display = "videooverview_thismonth";
          break;
        case "lastmonth":
          $this->table_display = "videooverview_previousmonth";
          break;
        case "3month":
          $this->table_display = "videooverview_3month";
          break;        
        default:
          $this->table_display = "mcm_ad_exch_video_report";
      }
      return $this->table_display;
    }
    #get overview publisher data
    public function getOverview(){
      #get table name 
      $table = $this->getTableOverview($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetch = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM mcm_ad_exch_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
      }else{
      if($table == "mcm_ad_exch_video_report"){
      $queryFetch = 'SELECT ad_exch_date as date,SUM(ad_request) as adr,SUM(ad_exch_clicks) as clicks,SUM(ad_exch_covg) as adrc,SUM(match_request)*100/SUM(ad_request) as covg,SUM(match_request) as madr,SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx,SUM(ad_exch_revenue) as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'" group by ad_exch_date ORDER BY ad_exch_date DESC';
       }
        else{
          $queryFetch = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
        }
      }
      #create memcache unique key
      $querykey = "KEY_".$this->child_net_code.'_'.md5($queryFetch);
      $result_cache = $memtest->get($querykey);
      if ($result_cache) {
      $assoc=$result_cache;
      #print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
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
      
      return $assoc;

    }
    #get table name ad type
    public function getTableAdtype($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_adtype = "videoadtype_today";
          break;
        case "yesterday":
          $this->table_adtype = "videoadtype_yesterday";
          break;
        case "7days":
          $this->table_adtype = "videoadtype_7days";
          break;
         case "10days":
          $this->table_adtype = "videoadtype_10days";
          break; 
        case "last30days":
          $this->table_adtype = "videoadtype_30days";
          break;
        case "thismonth":
          $this->table_adtype = "videoadtype_thismonth";
          break;
        case "lastmonth":
          $this->table_adtype = "videoadtype_previousmonth";
          break;
        case "3month":
          $this->table_adtype = "videoadtype_3month";
          break;        
        default:
          $this->table_adtype = "mcm_adtypewise_video_report";
      }
      return $this->table_adtype;
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
        $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM mcm_adtypewise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{
      if($table == "mcm_adtypewise_video_report"){
       
       $queryFetchAdtype = 'SELECT ad_type_date as date,ad_type,dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx, mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_type_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_type_date,revenue,ad_type DESC';
      }else{

      $queryFetchAdtype = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
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
      
      return $assoc;
    }
    #get table name ad type
    public function getTableAdunit($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_adunit = "videoadunit_today";
          break;
        case "yesterday":
          $this->table_adunit = "videoadunit_yesterday";
          break;
        case "7days":
          $this->table_adunit = "videoadunit_7days";
          break;
         case "10days":
          $this->table_adunit = "videoadunit_10days";
          break; 
        case "last30days":
          $this->table_adunit = "videoadunit_30days";
          break;
        case "thismonth":
          $this->table_adunit = "videoadunit_thismonth";
          break;
        case "lastmonth":
          $this->table_adunit = "videoadunit_previousmonth";
          break;
        case "3month":
          $this->table_adunit = "videoadunit_3month";
          break;        
        default:
          $this->table_adunit = "mcm_adunitwise_video_report";
      }
      return $this->table_adunit;
    }
    #get adunits data
    public function getAdunits(){
     
      #get table name 
      $table = $this->getTableAdunit($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetchAdunit = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_adunitwise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{
      if($table == "mcm_adunitwise_video_report"){
       
       $queryFetchAdunit = 'SELECT ad_unitwise_date as date,"No Tag" as rep_name,dfp_ad_unit as unit_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_unitwise_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_unitwise_date DESC';
      }else{

      $queryFetchAdunit = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
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
      
      return $assoc;
    }
    #get table name Sites
    public function getTableSites($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_sites = "videosites_today";
          break;
        case "yesterday":
          $this->table_sites = "videosites_yesterday";
          break;
        case "7days":
          $this->table_sites = "videosites_7days";
          break;
        case "10days":
          $this->table_sites = "videosites_10days";
          break;  
        case "last30days":
          $this->table_sites = "videosites_30days";
          break;
        case "thismonth":
          $this->table_sites = "videosites_thismonth";
          break;
        case "lastmonth":
          $this->table_sites = "videosites_previousmonth";
          break;
        case "3month":
          $this->table_sites = "videosites_3month";
          break;        
        default:
          $this->table_sites = "mcm_domainwise_video_report";
      }
      return $this->table_sites;
    }
    #get Sites data
    public function getSites(){
     
      #get table name 
      $table = $this->getTableSites($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetchSites = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_domainwise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{
      if($table == "mcm_domainwise_video_report"){
       
       $queryFetchSites = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_domain_date,site_name DESC';
      }else{

      $queryFetchSites = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
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
     
      return $assoc;
    }
    #get table name Device
    public function getTableDevice($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_device = "videodevice_today";
          break;
        case "yesterday":
          $this->table_device = "videodevice_yesterday";
          break;
        case "7days":
          $this->table_device = "videodevice_7days";
          break;
        case "10days":
          $this->table_device = "videodevice_10days";
          break;  
        case "last30days":
          $this->table_device = "videodevice_30days";
          break;
        case "thismonth":
          $this->table_device = "videodevice_thismonth";
          break;
        case "lastmonth":
          $this->table_device = "videodevice_previousmonth";
          break;
        case "3month":
          $this->table_device = "videodevice_3month";
          break;        
        default:
          $this->table_device = "mcm_devicecategory_video_report";
      }
      return $this->table_device;
    }
    #get Device data
    public function getDevice(){
     
      #get table name 
      $table = $this->getTableDevice($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_devicecategory_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{
      if($table == "mcm_devicecategory_video_report"){
       
       $queryFetchDevice = 'SELECT ad_device_date as date,device_category as device,dfp_ad_unit as rep_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue,ad_device_date,device_category DESC';
      }else{

      $queryFetchDevice = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
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
     
      return $assoc;
    }
    #get table name Geo
    public function getTableGeo($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_geo = "videogeo_today";
          break;
        case "yesterday":
          $this->table_geo = "videogeo_yesterday";
          break;
        case "7days":
          $this->table_geo = "videogeo_7days";
          break;
        case "10days":
          $this->table_geo = "videogeo_10days";
          break;  
        case "last30days":
          $this->table_geo = "videogeo_30days";
          break;
        case "thismonth":
          $this->table_geo = "videogeo_thismonth";
          break;
        case "lastmonth":
          $this->table_geo = "videogeo_previousmonth";
          break;
        case "3month":
          $this->table_geo = "videogeo_3month";
          break;        
        default:
          $this->table_geo = "mcm_countrywise_video_report";
      }
      return $this->table_geo;
    }
    #get Geo data
    public function getGeo(){
     
      #get table name 
      $table = $this->getTableGeo($this->range);
      #get memcached class
      $memtest = $this->getMemCache();
      #query
      #condtion for demo account 
      if($this->child_net_code == "demo_9999999"){
        $queryFetchGeo = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM mcm_countrywise_video_report_demo where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
      }else{
      if($table == "mcm_countrywise_video_report"){
       
       $queryFetchGeo = 'SELECT country_name,device_category as rep_name,ad_coun_date as date,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY revenue DESC';
      }else{

      $queryFetchGeo = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
      }
     }
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
     
      return $assoc;
    }  

 }
?>