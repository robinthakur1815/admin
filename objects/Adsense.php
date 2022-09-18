<?php
#Author BY AD
class Adsense{

 #database connection and table name
    private $conn;
    private $table_overview="adsense_daywise";
    

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
      $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => $this->table_overview,
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
        ['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]],
        ['$sort'=>['_id'=>-1]]
        ],
        'cursor' => new stdClass,
    ]);
    
    $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
     return $cursor_lplist;

    }
    
    #get adtype data
    public function getAdtype(){
     
      #query
     $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_adtypewise',
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
        ['$group' => ['_id'=>[
                    'adunit' => '$ad_format_name',
                    'date' => '$date'
                ],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings']]],
              ['$sort'=>['adunit'=>-1]],
              
            ],
            'cursor' => new stdClass,
        ]);
        $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
           
          return $cursor_lplist;
    }
  
    #get Sites data
    public function getSites(){
      #query
      $command_lplist = new MongoDB\Driver\Command([
          'aggregate' => 'adsense_domainwise',
          'pipeline' => [
              ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id'=>[
                      'website' => '$domain_name',
                      'date' => '$date'
                  ],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings']]]
          ],
          'cursor' => new stdClass,
      ]);
      $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
     
      return $cursor_lplist;
    }
    
    #get Device data
    public function getDevice(){

      #query
      $command_lplist = new MongoDB\Driver\Command([
          'aggregate' => 'adsense_devicewise',
          'pipeline' => [
              ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
          ['$group' => ['_id'=>[
                      'device' => '$platform_type_name',
                      'date' => '$date'
                  ],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings']]]
          ],
          'cursor' => new stdClass,
      ]);
      $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
     
      return $cursor_lplist; 

    }
    
 }
?>