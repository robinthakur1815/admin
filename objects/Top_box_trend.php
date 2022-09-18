<?php
#Author BY SY
class TopBoxTrend{

 #database connection and table name
    private $conn;
    private $table_overview="adsense_daywise";
	
	private $top_box_table_display = "mcm_devicecategory_report";
	private $top_box_table_app = "mcm_devicecategory_app_report";
	private $top_box_table_video = "mcm_devicecategory_video_report";
	private $top_box_demo_display = "mcm_devicecategory_report_demo";
	private $top_box_demo_app = "mcm_devicecategory_app_report_demo";
	private $top_box_demo_video = "mcm_devicecategory_video_report_demo";
	
	
	
    #object properties
    public $uniq_id;
    public $child_net_code;
    public $strtdate;
    public $enddate;
    public $range;
    public $analytics_id;
    
    
    #constructor with $db as database connection
    public function __construct($db,$connMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $connMongoDb;
    }
	#Top Box For Display
	public function headerTopBox1_display(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_display." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_display." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
		
	}
	public function headerTopBox2_display(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_display." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_display." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
	}
	#Top Box For App
	public function headerTopBox1_app(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_app." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_app." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
		
	}
	public function headerTopBox2_app(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_app." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_app." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
	}
	#Top Box For Video
	public function headerTopBox1_video(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_video." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_video." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_20daystart."' and '".$last_20daysend."'";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
		
	}
	public function headerTopBox2_video(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		if($this->child_net_code == "demo_9999999"){
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_demo_video." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}else{
			$querylast_last10days= "SELECT SUM(mcm_adreq) as ad_request,SUM(mcm_impression) as ad_exch_impression,SUM(mcm_clicks) as ad_exch_clicks,SUM(mcm_earnings) as ad_exch_revenue,ad_device_date as ad_exch_date FROM ".$this->top_box_table_video." WHERE child_net_code = '".$this->child_net_code."' AND ad_device_date between '".$last_10days."' and '".$todayDate."' GROUP BY ad_device_date";
		}
		$row_lastlast = $this->conn->prepare($querylast_last10days);
		$row_lastlast->execute();
		return $row_lastlast;
	}
	
	public function headerTopBox1_HB(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'header_bidder',
			'pipeline' => [
				['$match'=>['DATE'=>['$gte' =>$last_20daystart,'$lte' =>$last_20daysend,]]],
			['$group' => ['_id' => '$DATE', 'totalad_requests' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalad_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'total_click' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CLICKS'], 'total_earning' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE']]],
			['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;
    }
	public function headerTopBox2_HB(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'header_bidder',
			'pipeline' => [
				['$match'=>['DATE'=>['$gte' =>$last_10days,'$lte' =>$todayDate,]]],
			['$group' => ['_id' => '$DATE', 'totalad_requests' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalad_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'total_click' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CLICKS'], 'total_earning' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE']]],
			['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;
    }
	public function headerTopBox1_DD(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		
		$inData = array('IBM_07092020-30092020');
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'DFP_directdeal',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$last_20daystart,'$lte' =>$last_20daysend],'ad_unit_name'=>'PG_Indiaresult']],
				['$match'=>['order_name'=>['$nin' =>$inData]]],
				['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$direct_request'], 'totalad_imp' => ['$sum' => '$direct_impression'], 'total_click' => ['$sum' => '$ad_server_clicks'], 'total_earning' => ['$sum' => '$direct_revenue']]],
				['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
    
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;
		
    }
	public function headerTopBox2_DD(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		$inData = array('IBM_07092020-30092020');
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'DFP_directdeal',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$last_10days,'$lte' =>$todayDate],'ad_unit_name'=>'PG_Indiaresult']],
				['$match'=>['order_name'=>['$nin' =>$inData]]],
			['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$direct_request'], 'totalad_imp' => ['$sum' => '$direct_impression'], 'total_click' => ['$sum' => '$ad_server_clicks'], 'total_earning' => ['$sum' => '$direct_revenue']]],
			['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;
    }
	public function headerTopBox1_AD(){
		$last_20daystart = date('Y-m-d', strtotime("-19 days"));
		$last_20daysend = date('Y-m-d', strtotime("-10 days"));
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'adsense_daywise',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$last_20daystart,'$lte' =>$last_20daysend,]]],
			['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]],
			['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
    
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;
		
    }
	public function headerTopBox2_AD(){
		$todayDate = date('Y-m-d');
		$last_10days = date('Y-m-d', strtotime("-9 days"));
		
		
		$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'adsense_daywise',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$last_10days,'$lte' =>$todayDate,]]],
			['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]],
			['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
    
		$cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
		return $cursor_lplist;

    }
}

?>