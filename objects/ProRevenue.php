<?php
#Author BY AD
class ProRevenue{

 #database connection and table name
    private $conn;
    

    #object properties
    public $uniq_id;
    public $strtdate;
    public $enddate;
    
    

    
     #constructor with $db as database connection
    public function __construct($db,$connMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $connMongoDb;
    }
   
     #get overview publisher Adsense data
    public function getOverview(){
      
      #query
      $command_lplist1 = new MongoDB\Driver\Command([
          'aggregate' => 'header_bidder',
          'pipeline' => [
              ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id' => [
                      // 'tot_fill_rate' => '$TOTAL_FILL_RATE',
                      'date' => '$DATE'
                  ], 'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_rev' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'],'totalline_lvl_cpm' => ['$avg' => '$TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM'], 'total_ad_server_imp' => ['$sum' => '$AD_SERVER_IMPRESSIONS'],'total_ad_server_rev' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],'total_ad_server_cpm' => ['$avg' => '$AD_SERVER_WITHOUT_CPD_AVERAGE_ECPM'],'total_adx_imp' => ['$sum' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_IMPRESSIONS'],'total_adx_rev' => ['$sum' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_REVENUE'],'total_adx_cpm' => ['$avg' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_AVERAGE_ECPM']]],
          ['$sort'=>['_id'=>-1]]
          ],
          'cursor' => new stdClass,
      ]);

    
      $cursor_lplist1 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist1);

     #Direct Deals
    // $inData = array('IBM_07092020-30092020');
    // $command_lplist2 = new MongoDB\Driver\Command([
    //       'aggregate' => 'DFP_directdeal',
    //       'pipeline' => [
    //           ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate],'ad_unit_name'=>'PG_Indiaresult']],
    //       ['$match'=>['order_name'=>['$nin' =>$inData]]],
    //       ['$group' => ['_id' => [
    //                   // 'direct_coverage' => '$direct_coverage',
    //                   'date' => '$date'
    //               ], 'direct_request' => ['$sum' => '$direct_request'], 'direct_impression' => ['$sum' => '$direct_impression'], 'direct_revenue' => ['$sum' => '$direct_revenue'],'direct_cpm' => ['$sum' => '$direct_cpm'],'newecpm' =>['$sum' => '$ad_server_without_cpd_average_ecpm']]],
    //       ['$sort'=>['_id'=>-1]]
    //       ],
    //       'cursor' => new stdClass,
    //   ]);
    //   $cursor_lplist2 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist2); 
     //return array($cursor_lplist1,$cursor_lplist2);
     return $cursor_lplist1;

    }
    #get Device data
    public function getDevice(){

      #query
     $command_lplist1 = new MongoDB\Driver\Command([
        'aggregate' => 'countrywise_hb',
        'pipeline' => [
            ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
        ['$group' => ['_id' => [
            'category' => '$DEVICE_NAME',
                    'date' => '$DATE'
                ], 'total_revenue' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'],'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_cpm' => ['$avg' => '$TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM']]],
            ['$sort'=>['_id'=>-1]]
            ],
            'cursor' => new stdClass,
        ]);
    $cursor_lplist1 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist1);
     
      return $cursor_lplist1; 

    }
      
    #get Geo data
    public function getGeo(){

      #query
    $command_lplist1 = new MongoDB\Driver\Command([
          'aggregate' => 'countrywise_hb',
          'pipeline' => [
              ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id' => [
              'country' => '$COUNTRY_NAME',
                      'date' => '$DATE',
              'category' => '$DEVICE_NAME'
                  ], 'total_revenue' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'],'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_cpm' => ['$avg' => '$TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM']]],
          ['$sort'=>['_id'=>-1]]
          ],
          'cursor' => new stdClass,
      ]);
    $cursor_lplist1 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist1);
     
      return $cursor_lplist1; 

    }
	public function getAdunits(){

		#query
		$command_lplist2 = new MongoDB\Driver\Command([
			'aggregate' => 'countrywise_hb',
			'pipeline' => [['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
			['$group' => ['_id' => [
              'category' => '$AD_UNIT_NAME',
                      'date' => '$DATE',
                  ], 'total_revenue' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'],'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_cpm' => ['$avg' => '$TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM']]],
          ['$sort'=>['_id'=>-1]]
          ],
          'cursor' => new stdClass,
		]);
		$cursor_lplist2 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist2);
     
		return $cursor_lplist2; 
		
    }
	#get HB With Direct Deals data
    public function getHBWithDD(){

		#query
		$command_lplist1 = new MongoDB\Driver\Command([
			'aggregate' => 'header_bidder',
			'pipeline' => [
				['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
				['$group' => ['_id' => [
                      'date' => '$DATE'
                  ], 'total_served_count' => ['$sum' => '$TOTAL_CODE_SERVED_COUNT'], 'totalline_lvl_imp' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'], 'totalline_lvl_rev' => ['$sum' => '$TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'],'totalline_lvl_cpm' => ['$avg' => '$TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM'], 'total_ad_server_imp' => ['$sum' => '$AD_SERVER_IMPRESSIONS'],'total_ad_server_rev' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],'total_ad_server_cpm' => ['$avg' => '$AD_SERVER_WITHOUT_CPD_AVERAGE_ECPM'],'total_adx_imp' => ['$sum' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_IMPRESSIONS'],'total_adx_rev' => ['$sum' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_REVENUE'],'total_adx_cpm' => ['$avg' => '$AD_EXCHANGE_LINE_ITEM_LEVEL_AVERAGE_ECPM']]],
				['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
		$cursor_lplist1 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist1);
     
	 
		$inData = array('IBM_07092020-30092020');
		$command_lplist2 = new MongoDB\Driver\Command([
			'aggregate' => 'DFP_directdeal',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate],'ad_unit_name'=>'PG_Indiaresult']],
				['$match'=>['order_name'=>['$nin' =>$inData]]],
				['$group' => ['_id' => [
						'date' => '$date'
					],'direct_revenue' => ['$sum' => '$direct_revenue'],'direct_request' => ['$sum' => '$direct_request'],'direct_impression' => ['$sum' => '$direct_impression'],'direct_cpm' => ['$sum' => '$direct_cpm'],'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
				['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
		]);
		$cursor_lplist2 = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist2);
	 
      return array("HB_data"=>$cursor_lplist1,"DD_data"=>$cursor_lplist2); 

    }
	
 }
?>