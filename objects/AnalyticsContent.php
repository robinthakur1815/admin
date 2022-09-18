<?php
#Author BY SS
class Analytics{

 #database connection and table name
    private $conn;
    private $table_name = "analy_accounts";
    private $table_display = "mcm_ad_exch_report";

    #object properties
    public $uniq_id;
    public $account_id;
    public $child_net_code;
    public $strtdate;
    public $enddate;
    public $pstart_week;
    public $pend_week;
    public $start_week;
    public $end_week;
 
       
    #constructor with $db as database connection
    public function __construct($db,$connMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $connMongoDb;
    }
   #Get siteurl
    public function getSiteUrl($accId){
    $queryFetch= 'SELECT site_url,profile_id FROM ' . $this->table_name . ' WHERE account_id='.$accId.'';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    $stmt_result = $row->get_result();
    $resp1 = $stmt_result->fetch_all(MYSQLI_ASSOC);
    return $resp1;
    
    }
     #get content performance publisher data
    public function getContent(){
      
      #query
      $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_daywise',
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
            //['$match'=>['date'=>['$gte' =>"2021-09-02",'$lte' =>"2021-09-08",]]],
        ['$group' => ['_id' => '$date', 'totalad_imp' => ['$sum' => '$impressions'], 'total_pageviews' => ['$sum' => '$page_views'], 'total_earning' => ['$sum' => '$earnings']]],
        ['$sort'=>['_id'=>-1]]
        ],
        'cursor' => new stdClass,
    ]);
    $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);

    $queryFetchP='SELECT SUM(ad_exch_impression) as adimr,SUM(ad_exch_revenue)/SUM(ad_exch_impression)*1000 as ecpmx FROM ' . $this->table_display . ' WHERE child_net_code ="'.$this->child_net_code.'" AND ad_exch_date between "'.$this->strtdate.'" and "'.$this->enddate.'"';
    #prepare query
    $rowP = $this->conn->prepare($queryFetchP);
    #execute query 
    $rowP->execute();
    $stmt_resultP = $rowP->get_result();
    $resp = $stmt_resultP->fetch_array(MYSQLI_ASSOC);

    $siteUrl = $this->getSiteUrl($this->account_id);
     return array($cursor_lplist,$resp,$siteUrl);

    }
    #get common query for line chart and donut chart
    public function getComChartQuery(){
      
      #query
      $where = ['date'=>['$gte' => $this->pstart_week, '$lte' => $this->pend_week], 'account_id'=>['$eq'=>$this->account_id]];
        $select_fields  = [];
        $options        = [
            'projection'    => $select_fields,
            'sort'          => ['date' => -1]
        ];


        $query = new MongoDB\Driver\Query($where, $options);
        $resultL = $this->connMongoDb->executeQuery('analytics_db.analy_ret_new', $query);

        return $resultL;

    }
    #get high bounce
    public function getHighBounce(){
      
      #query
      $where    = ['date'=>['$gte' => $this->start_week, '$lte' => $this->end_week], 'account_id'=>['$eq'=>$this->account_id]];
        $select_fields  = [];
        $options        = [
            'projection'    => $select_fields,
            'limit'         => 100000,
            'sort'          => ['date' => -1]
        ];

        $query = new MongoDB\Driver\Query($where, $options);
        $row = $this->connMongoDb->executeQuery('analytics_db.analy_land_exit', $query);
        return $row;

    }

    #get traffic source
    public function getTrafficSource(){
      
      #query
      $where = ['date'=>['$gte' => $this->pstart_week, '$lte' => $this->pend_week], 'account_id'=>['$eq'=>$this->account_id]];
        $select_fields  = [];
        $options        = [
            'projection'    => $select_fields,
            'sort'          => ['date' => -1]
        ];

        $query = new MongoDB\Driver\Query($where, $options);
        $row = $this->connMongoDb->executeQuery('analytics_db.analy_traffic_source', $query);
        return $row;

    }
    public function heatmap(){
      
      #query
      $where = ['date'=>['$gte' => $this->strtdate, '$lte' => $this->enddate], 'account_id'=>['$eq'=>$this->account_id]];
        $select_fields  = [];
        $options        = [
            'projection'    => $select_fields,
            'sort'          => ['date' => -1]
        ];

        $query = new MongoDB\Driver\Query($where, $options);
        $row = $this->connMongoDb->executeQuery('analytics_db.analy_traffic_source', $query);
        return $row;

    }
 }
?>