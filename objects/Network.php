<?php
#Author BY SY
class Network{

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
    
    #get table name Sites
    public function getTableSites($tablename){
      
      switch ($tablename) {
        case "today":
          $this->table_sites = "dissites_today";
          break;
        case "yesterday":
          $this->table_sites = "dissites_yesterday";
          break;
        case "7days":
          $this->table_sites = "dissites_7days";
          break;
         case "10days":
          $this->table_sites = "dissites_10days";
          break; 
        case "last30days":
          $this->table_sites = "dissites_30days";
          break;
        case "thismonth":
          $this->table_sites = "dissites_thismonth";
          break;
        case "lastmonth":
          $this->table_sites = "dissites_previousmonth";
          break;
        case "3month":
          $this->table_sites = "dissites_3month";
          break;        
        default:
          $this->table_sites = "mcm_domainwise_report";
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
		if($table == "mcm_domainwise_report"){
			$queryFetchSites = 'SELECT ad_domain_date as date,site_name,dfp_ad_unit as rep_name,mcm_clicks as clicks, mcm_adreq as adr,mcm_matchreq as madr, mcm_matchreq*100/mcm_adreq as covg, mcm_impression as adimr, mcm_earnings/mcm_impression*1000 as ecpmx,mcm_earnings as revenue FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$this->strtdate.'" and "'.$this->enddate.'" ORDER BY ad_domain_date DESC';
		}else{
			$queryFetchSites = 'SELECT * FROM ' . $table . ' where child_net_code ="'.$this->child_net_code.'"';
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

    #get Sites data
    public function getNetworkAdsData($dataType){
		#get memcached class
		$memtest = $this->getMemCache();

		$startDate = date('Y-m-d', strtotime($this->strtdate));
		$endDate = date('Y-m-d', strtotime($this->enddate));

		#query
		if(strtolower($dataType) == "domain"){
			$queryFetchSites = 'SELECT ad_domain_date as date, site_name, dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_impression as adimr, mcm_earnings as revenue FROM mcm_domainwise_report where child_net_code ="'.$this->child_net_code.'" AND ad_domain_date between "'.$startDate.'" and "'.$endDate.'" ORDER BY ad_domain_date DESC';
		}else if(strtolower($dataType) == "device"){
			$queryFetchSites = 'SELECT ad_device_date as date, device_category as device_name, dfp_ad_unit as rep_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_impression as adimr, mcm_earnings as revenue FROM mcm_devicecategory_report where child_net_code ="'.$this->child_net_code.'" AND ad_device_date between "'.$startDate.'" and "'.$endDate.'" ORDER BY ad_device_date DESC';
		}else if(strtolower($dataType) == "geo"){
			$queryFetchSites = 'SELECT ad_coun_date as date, country_name as geo_name, mcm_clicks as clicks, mcm_adreq as adr, mcm_matchreq as madr, mcm_impression as adimr, mcm_earnings as revenue FROM mcm_countrywise_report where child_net_code ="'.$this->child_net_code.'" AND ad_coun_date between "'.$startDate.'" and "'.$endDate.'" ORDER BY ad_coun_date DESC';
		}else{
			$queryFetchSites = 'SELECT * FROM mcm_devicecategory_report where child_net_code ="10101010"';//Pass Blank Data
		}

		#create memcache unique key
		$querykeySites = "KEY_".$this->child_net_code.'_'.md5($queryFetchSites);
		$result_cache = $memtest->get($querykeySites);
		if ($result_cache) {
			$assoc=$result_cache;
		}else{
			$row = $this->conn->prepare($queryFetchSites);
			$row->execute();
			$stmt_result = $row->get_result();
			$resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
			$assoc=$resp;
			$memtest->set($querykeySites, $resp,1800);
			$rows = $stmt_result->num_rows;
		}

$finalArr = array();
if(count($assoc)>0){
	foreach($assoc as $key => $value){


if(strtolower($dataType) == "domain"){
	$arrKey = $value['site_name'];
	@$finalArr[$arrKey]['site_name'] = $value['site_name'];
}else if(strtolower($dataType) == "device"){
	$arrKey = $value['device_name'];
	@$finalArr[$arrKey]['device_name'] = $value['device_name'];
}else if(strtolower($dataType) == "geo"){
	$arrKey = $value['geo_name'];
	@$finalArr[$arrKey]['geo_name'] = $value['geo_name'];
}
	//@$finalArr[$arrKey]['date'] = $value['date'];

	@$finalArr[$arrKey]['adr']+=$value['adr'];
	@$finalArr[$arrKey]['adimr']+=$value['adimr'];
	@$finalArr[$arrKey]['madr']+=$value['madr'];
	@$finalArr[$arrKey]['clicks']+=$value['clicks'];
	@$finalArr[$arrKey]['covg'] = $finalArr[$arrKey]['madr'] > 0 ? number_format(($finalArr[$arrKey]['madr']*100)/$finalArr[$arrKey]['adr'],1) :'0.00';
	@$finalArr[$arrKey]['ctr'] = $finalArr[$arrKey]['adimr'] > 0 ? number_format($finalArr[$arrKey]['clicks']/$finalArr[$arrKey]['adimr']*100,1):'0.00';
	@$finalArr[$arrKey]['revenue']+=number_format($value['revenue'],2);
	@$finalArr[$arrKey]['ecpm'] = $finalArr[$arrKey]['adimr'] > 0 ? number_format($finalArr[$arrKey]['revenue']/$finalArr[$arrKey]['adimr']*1000,2) : '0.00';
	@$finalArr[$arrKey]['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);
	}
}
		return $finalArr;
    }

 }
?>