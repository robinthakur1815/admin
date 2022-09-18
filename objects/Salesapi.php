<?php
#Author BY SS
class Salesapi{
#database connection and table name
private $conn;
public $connMongoDb;
private $table_name1 = "mcm_ad_exch_report";
private $table_name2 = "mcm_ad_exch_app_report";
private $table_name3 = "mcm_ad_exch_video_report";
private $table_pub = "publisher_master";
private $table_user = "users";
private $table_acc_mgr = "account_manager";
private $table_web="publishers_website";
private $table_app="publishers_app";
private $table_services="publisher_services";
#object properties
public $strtdate;
public $accounts;
public $enddate; 
public $fetched_team_name;
public $uniq_id;
public $pub_uniq_id;
public $parent_id;
public $first_name;
public $last_name;
public $email;
public $role_id;
public $contact;
public $salt_id;
public $password;
public $subuser_id;
public $salesuser_id;
public $userRole;
public $onboardedDate;
public $status;
public $serv_id;
public $pub_id;
public $pub_uniqid;
public $pub_type;
public $org_type;
public $org_name;
public $domain_managed;
public $team_size;
public $direct_sales;
public $traffic_source;
public $primary_geo;
public $inventory_quality;
public $adx_for_display;
public $adx_for_video;
public $adx_for_app;
public $display_share;
public $video_share;
public $app_share;
public $adsense_id;
public $adsense_share;
public $sales_id;
public $channel_id;
public $remark;
public $refer;
public $refer_name;
public $refer_email;
public $refer_contact;
public $app_names;
public $analytics_id;
public $email_status;
public $pub_email;
public $domain_id;
public $app_id;
public $vertical;
public $vertical2;
public $app_status; 
public $dbAdsenseId;  

public $where;

#constructor with $db as database connection
public function __construct($db,$connMongoDb,$strtdate,$enddate){
    $this->conn = $db;
    $this->connMongoDb = $connMongoDb;
    $this->strtdate = $strtdate;
    $this->enddate = $enddate;
}
#fetch master data
public function masterData(){
    //  echo"hgavscgh";
     $queryFetch= 'SELECT SUM(mcm_domainwise_report.mcm_earnings) FROM '.$this->table_pub.'
     INNER JOIN mcm_domainwise_report ON 
     publisher_master.child_net_code = mcm_domainwise_report.child_net_code 
     WHERE sal_id  = "'.$this->id.'" and mcm_status ="APPROVED" 
     ';
     // and created_at BETWEEN "2019-09-13" AND "2022-01-13" 
     // and created_at between '".$start_date."' and '".$end_date."'
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
     }
     
     #fetch salesData data
 public function salesData(){
        $queryFetch= 'SELECT sal_id FROM '.$this->table_pub.' WHERE sal_id  = "'.$this->id.'" and mcm_status ="APPROVED"';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
     }
#overall tab
public function getrevenue(){
    #MCM
    $querymcm="SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name, psm.manager_id,psm.network_flag, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name,pm.network_flag from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!='' GROUP BY child_net_code";
    // echo $querymcm;die;
	#prepare query
    $row1 = $this->conn->prepare($querymcm);
    #execute query 
    $row1->execute();
    $stmt_mcm = $row1->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;

    foreach ($rowMcm as $value_total) {
    if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
        $gross_adx_mcm += $value_total['gross_adx_mcm'];
    }
    if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
        $net_adx_mcm += $value_total['net_adx_mcm'];
    }
    }
    
    $gross_adx = $gross_adx_mcm;
    $net_adx = $net_adx_mcm;
    // $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
    // #prepare query
    // $rows = $this->conn->prepare($query_pubId);
    // #execute query 
    // $rows->execute();
    // //return $row;
    // $stmt_result = $rows->get_result();
    // $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    // foreach($row_result as $value_pubId) {

    // $uniqid = $value_pubId['pub_uniq_id'];

    //     if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //         $afc_share = $value_pubId['pub_display_share'];
    //     }
    //     else{
    //         $afc_share = 15;
    //     }
    //     // $afc_share = 15;
    // #Directdeal
    // $command_directdeal_gross = new MongoDB\Driver\Command([
    //     'aggregate' => 'DFP_directdeal',
    //     'pipeline' => [
    //         ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //         ['$group' => ['_id' => [
    //                 'date' => '$date'
    //             ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
    //         ['$sort'=>['_id'=>-1]]
    //     ],
    //     'cursor' => new stdClass,
    // ]);
    // $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
    // foreach($directdeal_gross as $value_direct) {
    // $gross_direct += number_format(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    // $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)*$afc_share/1000),2);
    // }

    // #HeaderBidder
    // $command_HB = new MongoDB\Driver\Command([
    //     'aggregate' => 'header_bidder',
    //     'pipeline' => [
    //         ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //         ['$group' => ['_id' => [
    //                 'date' => '$DATE'
    //             ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
    //         ['$sort'=>['_id'=>-1]]
    //     ],
    //     'cursor' => new stdClass,
    // ]);
    // $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
    // foreach($HB as $value_hb) {
    // $gross_hb += number_format($value_hb->adserver_revenue,2);
    // $net_hb += number_format(($value_hb->adserver_revenue*$afc_share/100),2);
    // }

    // }

    #Adsense
    // $command_adsense_gross = new MongoDB\Driver\Command([
    //     'aggregate' => 'adsense_daywise',
    //     'pipeline' => [
    //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
    //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
    //     ],
    //     'cursor' => new stdClass,
    // ]);
    // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
    // foreach($adsense_gross as $value_adsense) {
    // $gross_adsense += $value_adsense->gross_adsense;
    // }

    // $total_gross = $gross_adx+$gross_hb+$gross_adsense+$gross_direct;
    // $total_net = $net_adx+$net_hb+$gross_adsense+$gross_direct;
    // $total_gross = $gross_adx+$gross_hb+$gross_direct;
    // $total_net = $net_adx+$net_hb+$net_direct;
    $total_gross = $gross_adx;
    $total_net = $net_adx;

    #Net % breakup
    $net_percent_adx = (($net_adx/$total_net)*100);
    // $net_percent_adsense = (($gross_adsense/$total_net)*100);
    // $net_percent_hb = (($net_hb/$total_net)*100);
    // $net_percent_direct = (($net_direct/$total_net)*100);

    // $total_netPer = $net_percent_adx+$net_percent_adsense+$net_percent_hb+$net_percent_direct;
    // $total_netPer = $net_percent_adx+$net_percent_hb+$net_percent_direct;
    $total_netPer = $net_percent_adx;
    return json_encode(array(
        'ADX'=>array('Topline'=>number_format($gross_adx, 2), 'Bottomline'=>number_format($net_adx, 2), 'NetBreakup'=>number_format($net_percent_adx, 2)),
        // 'HB'=>array('Topline'=>number_format($gross_hb, 2), 'Bottomline'=>number_format($net_hb, 2), 'NetBreakup'=>number_format($net_percent_hb, 2)),
        // // 'Adsense'=>array('Topline'=>number_format($gross_adsense, 2), 'Bottomline'=>number_format($gross_adsense, 2), 'NetBreakup'=>number_format($net_percent_adsense, 2)),
        // 'DirectDeal'=>array('Topline'=>number_format($gross_direct, 2), 'Bottomline'=>number_format($net_direct, 2), 'NetBreakup'=>number_format($net_percent_direct, 2)),
        'HB'=>array('Topline'=>'0', 'Bottomline'=>'0', 'NetBreakup'=>'0',),
        'DirectDeal'=>array('Topline'=>'0', 'Bottomline'=>'0', 'NetBreakup'=>'0',),
        'Total'=>array('Topline'=>number_format($total_gross, 2), 'Bottomline'=>number_format($total_net, 2), 'NetBreakup'=>number_format($total_netPer, 2))  
                     
    ));

}
// #Get Top 15 Adx Contributor 
public function Top15AdxContributor(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_mcm =0;
    $net_mcm = 0;
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_adsense =0;
    $gross_direct =0;
    $top_adx = array();
    foreach ($rowMcm as $value_total) {
            $publisher_name = $value_total['adx_p_name'];
            #Gross
            if(isset($value_total['gross_adx_mcm'])){
            $gross_mcm = $value_total['gross_adx_mcm'];
            }
          
            #Net
            if(isset($value_total['net_adx_mcm'])){
            $net_mcm = $value_total['net_adx_mcm'];
            }
           
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_mcm);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_mcm);
            }
    }

    // $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
    //     #prepare query
    //     $rows = $this->conn->prepare($query_pubId);
    //     #execute query 
    //     $rows->execute();
    //     //return $row;
    //     $stmt_result = $rows->get_result();
    //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    // foreach($row_result as $value_pubId) {
    //     $publisher_name = $value_pubId['adx_p_name'];
    //     $uniqid = $value_pubId['pub_uniq_id'];
    //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //             $afc_share = $value_pubId['pub_display_share'];
    //         }
    //         else{
    //             $afc_share = 15;
    //         }
    //         // $afc_share = 15;
    //     #Directdeal
    //     $command_directdeal_gross = new MongoDB\Driver\Command([
    //         'aggregate' => 'DFP_directdeal',
    //         'pipeline' => [
    //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$date'
    //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
       
    //     foreach($directdeal_gross as $value_direct) {
    //         $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
    
    //         // publisher WISE CODE
    //         $top_adx[$publisher_name]['gross'] += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $top_adx[$publisher_name]['net'] += round((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
    //     }
    //     // #HeaderBidder
    //     $command_HB = new MongoDB\Driver\Command([
    //         'aggregate' => 'header_bidder',
    //         'pipeline' => [
    //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$DATE'
    //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
    //     // echo "<pre>";print_r($HB);die;
    //     foreach($HB as $value_hb) {
    //         $gross_hb += round($value_hb->adserver_revenue,2);
    //         $net_hb += round(($value_hb->adserver_revenue*$afc_share/100),2);
    
    //         // publisher WISE CODE
    //         $top_adx[$publisher_name]['gross'] += round($value_hb->adserver_revenue,2);
    //         $top_adx[$publisher_name]['net'] += round(($value_hb->adserver_revenue*$afc_share/100),2);
    //     }
        

    // }
 
    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        // if($i<=15){
        $top[$i]['PublisherName'] = $top_key; 
        $top[$i]['Topline'] = number_format($top_value['gross'], 2); 
        $top[$i]['Bottomline'] = number_format($top_value['net'], 2);
        $i++;  
        // }
    } 
    $json_response = json_encode($top);
    return $json_response;
}
#Sales Representative Overview
public function getrevenuesalesrep(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name,psm.network_flag, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name,pm.network_flag from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;
    $fetched_team_name=0;
    $total_net_salerep=0;

        foreach ($rowMcm as $value_total) {
            if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
                $gross_adx_mcm += $value_total['gross_adx_mcm'];
            }
            if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
                $net_adx_mcm += $value_total['net_adx_mcm'];
            }
           
            $sales_team_id = $value_total['sal_id'];
            $salesrep[$sales_team_id]['gross'] = round($gross_adx_mcm,2);
        
            $salesrep[$sales_team_id]['net'] = round($net_adx_mcm,2);
            }
            // $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
            // #prepare query
            // $rows = $this->conn->prepare($query_pubId);
            // #execute query 
            // $rows->execute();
            // $stmt_result = $rows->get_result();
            // $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);
            // foreach($row_result as $value_pubId) {

            //     $uniqid = $value_pubId['pub_uniq_id'];
            //     $sales_team_id = $value_pubId['sal_id'];
            //     $channelname = $value_pubId['chan_name'];
            
            //     if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
            //         $afc_share = $value_pubId['pub_display_share'];
            //     }
            //     else{
            //         $afc_share = 15;
            //     }
            //     // $afc_share = 15;
            //     $command_directdeal_gross = new MongoDB\Driver\Command([
            //         'aggregate' => 'DFP_directdeal',
            //         'pipeline' => [
            //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
            //             ['$group' => ['_id' => [
            //                     'date' => '$date'
            //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
            //             ['$sort'=>['_id'=>-1]]
            //         ],
            //         'cursor' => new stdClass,
            //     ]);
            //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
            //     foreach($directdeal_gross as $value_direct) {
            //         $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
            //         $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
            
            //         // SALESREP WISE CODE
            //         $salesrep[$sales_team_id]['gross'] += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
            //         $salesrep[$sales_team_id]['net'] += round((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
            //     }
            //     $command_HB = new MongoDB\Driver\Command([
            //         'aggregate' => 'header_bidder',
            //         'pipeline' => [
            //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
            //             ['$group' => ['_id' => [
            //                     'date' => '$DATE'
            //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
            //             ['$sort'=>['_id'=>-1]]
            //         ],
            //         'cursor' => new stdClass,
            //     ]);
                
            //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
            
            //     foreach($HB as $value_hb) {
            //         $gross_hb += round($value_hb->adserver_revenue,2);
            //         $net_hb += round(($value_hb->adserver_revenue*$afc_share/100),2);
            
            //         // SALESREP WISE CODE
            //         $salesrep[$sales_team_id]['gross'] += round($value_hb->adserver_revenue,2);
            //         $salesrep[$sales_team_id]['net'] += round(($value_hb->adserver_revenue*$afc_share/100),2);
            //     }

            //     #Adsense
            //     // $command_adsense_gross = new MongoDB\Driver\Command([
            //     //     'aggregate' => 'adsense_daywise',
            //     //     'pipeline' => [
            //     //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
            //     //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
            //     //     ],
            //     //     'cursor' => new stdClass,
            //     // ]);
            //     // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
            //     // foreach($adsense_gross as $value_adsense) {
            //     //         $gross_adsense += round($value_adsense->gross_adsense,2);
            //     //         // $net_adsense += round(($value_adsense->gross_adsense*$afc_share/100),2);
                
            //     //         // SALESREP WISE CODE
            //     //         $salesrep[$sales_team_id]['gross'] += round($value_adsense->gross_adsense,2);
            //     //         // $salesrep[$sales_team_id]['net'] += round(($value_adsense->gross_adsense*$afc_share/100),2);
            //     // }
            // }
            $active=0;
            $query_active_inactive = "SELECT pub.sal_id, pub.child_net_code, pub.adx_partner_id, pub.pub_adsense_id, ud.pub_adx_status, ud.pub_adsense_status FROM publisher_master AS pub JOIN users AS ud ON pub.pub_email=ud.email";
            $row4 = $this->conn->prepare($query_active_inactive);
            #execute query 
            $row4->execute();
            $stmt_exe = $row4->get_result();
            $rowExe = $stmt_exe->fetch_all(MYSQLI_ASSOC);
            foreach($rowExe as $val_act_inact) {
                        
                if(($val_act_inact['child_net_code']!="" || $val_act_inact['adx_partner_id']!="" && $val_act_inact['pub_adx_status']==1) || ($val_act_inact['pub_adsense_id']!="" && $val_act_inact['pub_adsense_status']==1)){
                    $salesrep[$val_act_inact['sal_id']]['active'] += $active+1;                      
                }
            }
            //  To call Sales Member name
            foreach ($salesrep as $team_id=>$sale_value) {
                if($team_id != ""){
                $get_team_id = "SELECT sal_name from sales_team WHERE sal_id='$team_id'";
                $row5 = $this->conn->prepare($get_team_id);
                $row5->execute();
                $stmt_exe1 = $row5->get_result();
                $rowExe1 = $stmt_exe1->fetch_array(MYSQLI_ASSOC);
                $fetched_team_name = $rowExe1['sal_name'];
                if($fetched_team_name==""){ $fetched_team_name='others';}
                $salesrep_detail[$fetched_team_name]['gross'] = round($sale_value['gross'],2);
                $salesrep_detail[$fetched_team_name]['net'] = round($sale_value['net'],2);
                $salesrep_detail[$fetched_team_name]['active'] = $sale_value['active'];
                $total_net_salerep += round($sale_value['net'],2);
                }
                else{
                    continue;
                }
            }
            arsort($salesrep_detail);
            $i = 1;
            $top = array(); 
        foreach($salesrep_detail as $sales_key=>$sales_value) {
            $top[$i]['Representative Name'] = $sales_key; 
            $top[$i]['Topline'] = number_format($sales_value['gross'],2); 
            $top[$i]['Bottomline'] = number_format($sales_value['net'],2);
            $top[$i]['Active'] = $sales_value['active'];
            $i++;
        } 
        $json_response = json_encode($top);
        return $json_response;
}
#Breakup of Net Revenue SalesRep Wise
public function netrevsalesrep(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name,psm.network_flag, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name, pm.network_flag from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;
    $fetched_team_name=0;
    $total_net_salerep=0;

        foreach ($rowMcm as $value_total) {
            if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
                $gross_adx_mcm += $value_total['gross_adx_mcm'];
            }
            if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
                $net_adx_mcm += $value_total['net_adx_mcm'];
            }
           
            $sales_team_id = $value_total['sal_id'];
            $salesrep[$sales_team_id]['gross'] = round($gross_adx_mcm,2);
        
            $salesrep[$sales_team_id]['net'] = round($net_adx_mcm,2);
            }
            // $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
            // #prepare query
            // $rows = $this->conn->prepare($query_pubId);
            // #execute query 
            // $rows->execute();
            // //return $row;
            // $stmt_result = $rows->get_result();
            // $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);
            // foreach($row_result as $value_pubId) {

            //     $uniqid = $value_pubId['pub_uniq_id'];
            //     $sales_team_id = $value_pubId['sal_id'];
            //     $channelname = $value_pubId['chan_name'];
            
            //     if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
            //         $afc_share = $value_pubId['pub_display_share'];
            //     }
            //     else{
            //         $afc_share = 15;
            //     }
            //     // $afc_share = 15;
            //     $command_directdeal_gross = new MongoDB\Driver\Command([
            //         'aggregate' => 'DFP_directdeal',
            //         'pipeline' => [
            //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
            //             ['$group' => ['_id' => [
            //                     'date' => '$date'
            //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
            //             ['$sort'=>['_id'=>-1]]
            //         ],
            //         'cursor' => new stdClass,
            //     ]);
            //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
            //     foreach($directdeal_gross as $value_direct) {
            //         $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
            
            //         // SALESREP WISE CODE
            //         $salesrep[$sales_team_id]['gross'] += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
            
            //     }
            //     $command_HB = new MongoDB\Driver\Command([
            //         'aggregate' => 'header_bidder',
            //         'pipeline' => [
            //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
            //             ['$group' => ['_id' => [
            //                     'date' => '$DATE'
            //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
            //             ['$sort'=>['_id'=>-1]]
            //         ],
            //         'cursor' => new stdClass,
            //     ]);
                
            //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
            
            //     foreach($HB as $value_hb) {
            //         $gross_hb += round($value_hb->adserver_revenue,2);
            //         $net_hb += round(($value_hb->adserver_revenue*$afc_share/100),2);
            //         // SALESREP WISE CODE
            //         $salesrep[$sales_team_id]['gross'] += round($value_hb->adserver_revenue,2);
            //         $salesrep[$sales_team_id]['net'] += round(($value_hb->adserver_revenue*$afc_share/100),2);
            //     }
            //         #Adsense
            //         // $command_adsense_gross = new MongoDB\Driver\Command([
            //         //     'aggregate' => 'adsense_daywise',
            //         //     'pipeline' => [
            //         //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
            //         //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
            //         //     ],
            //         //     'cursor' => new stdClass,
            //         // ]);
            //         // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
            //         // foreach($adsense_gross as $value_adsense) {
            //         //         $gross_adsense += round($value_adsense->gross_adsense,2);
            //         //         // $net_adsense += round(($value_adsense->gross_adsense*$afc_share/100),2);
                    
            //         //         // SALESREP WISE CODE
            //         //         $salesrep[$sales_team_id]['gross'] += round($value_adsense->gross_adsense,2);
            //         //         // $salesrep[$sales_team_id]['net'] += round(($value_adsense->gross_adsense*$afc_share/100),2);
            //         // }
            // }
            $active=0;
            $query_active_inactive = "SELECT pub.sal_id, pub.child_net_code, pub.adx_partner_id, pub.pub_adsense_id, ud.pub_adx_status, ud.pub_adsense_status FROM publisher_master AS pub JOIN users AS ud ON pub.pub_email=ud.email";
            $row4 = $this->conn->prepare($query_active_inactive);
            #execute query 
            $row4->execute();
            $stmt_exe = $row4->get_result();
            $rowExe = $stmt_exe->fetch_all(MYSQLI_ASSOC);
            foreach($rowExe as $val_act_inact) {
                        
                if(($val_act_inact['child_net_code']!="" || $val_act_inact['adx_partner_id']!="" && $val_act_inact['pub_adx_status']==1) || ($val_act_inact['pub_adsense_id']!="" && $val_act_inact['pub_adsense_status']==1)){
                    $salesrep[$val_act_inact['sal_id']]['active'] += $active+1;                      
                }
            }
            //  To call Sales Member name
            foreach ($salesrep as $team_id=>$sale_value) {
                if($team_id != ""){
                $get_team_id = "SELECT sal_name from sales_team WHERE sal_id='$team_id'";
                $row5 = $this->conn->prepare($get_team_id);
                $row5->execute();
                $stmt_exe1 = $row5->get_result();
                $rowExe1 = $stmt_exe1->fetch_array(MYSQLI_ASSOC);
                $fetched_team_name = $rowExe1['sal_name'];
                if($fetched_team_name==""){ $fetched_team_name='others';}
                $salesrep_detail[$fetched_team_name]['gross'] = round($sale_value['gross'],2);
                $salesrep_detail[$fetched_team_name]['net'] = round($sale_value['net'],2);
                $salesrep_detail[$fetched_team_name]['active'] = $sale_value['active'];
                $total_net_salerep += round($sale_value['net'],2);
                }
                else{
                    continue;
                }
            }
            arsort($salesrep_detail);
            $i = 1;
            $top = array(); 
            foreach($salesrep_detail as $mem_name => $vals) {
                $top[$i]['Sales Person Name'] = $mem_name; 
                $top[$i]['Percentage Net Revenue'] = number_format(($vals['net']/$total_net_salerep)*100,2); 
                $i++;  
            }
        $json_response = json_encode($top);
        return $json_response;
}
#Breakup of Channel ROI
public function getchannelbreakup(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name,psm.network_flag, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name,pm.network_flag from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
   
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;
    $fetched_team_name=0;
    $total_net_salerep=0;

        foreach ($rowMcm as $value_total) {
            if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
                $gross_adx_mcm += $value_total['gross_adx_mcm'];
            }
            
            if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
                $net_adx_mcm += $value_total['net_adx_mcm'];
            }
            
            if ($value_total['chan_name']!="") {
               $channelname = $value_total['chan_name']; 
               $channel_detail[$channelname]['net'] += ($value_total['net_adx_mcm']);
               $total_net_channel += ($value_total['net_adx_mcm']);
           }
           }
        //    $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
        //     #prepare query
        //     $rows = $this->conn->prepare($query_pubId);
        //     #execute query 
        //     $rows->execute();
        //     //return $row;
        //     $stmt_result = $rows->get_result();
        //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);
        //     foreach($row_result as $value_pubId) {

        //         $uniqid = $value_pubId['pub_uniq_id'];
        //         $sales_team_id = $value_pubId['sal_id'];
        //         $channelname = $value_pubId['chan_name'];
            
        //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
        //             $afc_share = $value_pubId['pub_display_share'];
        //         }
        //         else{
        //             $afc_share = 15;
        //         }
        //         // $afc_share = 15;
        //         $command_directdeal_gross = new MongoDB\Driver\Command([
        //             'aggregate' => 'DFP_directdeal',
        //             'pipeline' => [
        //                 ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
        //                 ['$group' => ['_id' => [
        //                         'date' => '$date'
        //                     ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
        //                 ['$sort'=>['_id'=>-1]]
        //             ],
        //             'cursor' => new stdClass,
        //         ]);
        //         $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
        //         foreach($directdeal_gross as $value_direct) {
        //            $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);

        //            // Channel Wise Code
        //            if ($channelname!="") {
        //                $channel_detail[$channelname]['net'] += (($value_direct->direct_impression*$value_direct->newecpm)/1000);
        //                $total_net_channel += (($value_direct->direct_impression*$value_direct->newecpm)/1000);
        //            }
        //        }
            
            
        //         $command_HB = new MongoDB\Driver\Command([
        //             'aggregate' => 'header_bidder',
        //             'pipeline' => [
        //                 ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
        //                 ['$group' => ['_id' => [
        //                         'date' => '$DATE'
        //                     ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
        //                 ['$sort'=>['_id'=>-1]]
        //             ],
        //             'cursor' => new stdClass,
        //         ]);
                
        //         $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
        //         foreach($HB as $value_hb) {
        //            // Channel Wise Code
        //            if ($channelname!="") {
        //                $channel_detail[$channelname]['net'] += ($value_hb->adserver_revenue*$afc_share/100);
        //                $total_net_channel += ($value_hb->adserver_revenue*$afc_share/100);
                       
        //            }
        //        }
        //        #Adsense
        //     //    $command_adsense_gross = new MongoDB\Driver\Command([
        //     //     'aggregate' => 'adsense_daywise',
        //     //     'pipeline' => [
        //     //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
        //     //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
        //     //     ],
        //     //     'cursor' => new stdClass,
        //     // ]);
        //     // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
        //     // foreach($adsense_gross as $value_adsense) {

        //     //      // Channel Wise Code
        //     //     //  if ($channelname!="") {
        //     //     //     $channel_detail[$channelname]['net'] += ($value_adsense->gross_adsense*$afc_share/100);
        //     //     //     $total_net_channel += ($value_adsense->gross_adsense*$afc_share/100);
                    
        //     //     // }
        //     // }
               
        //     }
               arsort($channel_detail);
               $i = 1;
               $top = array(); 
               foreach ($channel_detail as $channel_name => $valc) {
                   $top[$i]['name'] = $channel_name;
                   if($top[$i]['name']==""){ $top[$i]['name']='others';} 
                   $top[$i]['val'] = number_format(($valc['net']/$total_net_channel)*100, 2); 
                   $i++;
               }
               $json_response = json_encode($top);
               return $json_response;
}
#Verify Email
public function verifyEmail($salesuser = NULL){
    if($salesuser !=''){
        $queryFetch= 'SELECT email FROM ' . $this->table_user . ' WHERE email = "'.trim(strip_tags($this->email)).'" and id!='.$salesuser.'';
    }else{
        $queryFetch= 'SELECT email FROM ' . $this->table_user . ' WHERE email = "'.trim(strip_tags($this->email)).'"';
    }
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    return $row;
}

#fetch sales user
public function getallSalesuser($whereCondition){
	$queryEdit1alluser= "SELECT u.id, u.role_id, u.email as emailId, CONCAT(u.f_name,' ',u.l_name) as userName, r.role_name as userRole, r.created_at as onboardedDate,u.user_status as status FROM users as u JOIN role_master as r ON u.role_id=r.role_id  ".$whereCondition." GROUP BY u.email ORDER BY u.f_name ASC";
	#prepare query
	$edit_rowalluser = $this->conn->prepare($queryEdit1alluser);
	#execute query 
	$edit_rowalluser->execute();
	return $edit_rowalluser;
}

public function getallSalesuser8(){
    $queryEdit1alluser= "SELECT u.role_id, u.email as emailId, CONCAT(u.f_name,' ',u.l_name) as userName, r.role_name as userRole, r.created_at as onboardedDate,u.user_status as status FROM users as u JOIN role_master as r ON u.role_id=r.role_id WHERE u.role_id IN(8) GROUP BY u.email ORDER BY u.f_name ASC";
    // echo $queryEdit1alluser;die;
    #prepare query
    $edit_rowalluser = $this->conn->prepare($queryEdit1alluser);
    #execute query 
    $edit_rowalluser->execute();
    return $edit_rowalluser;
}
public function getallSalesadmin7(){
    $queryEdit1alluser= "SELECT u.role_id, u.email as emailId, CONCAT(u.f_name,' ',u.l_name) as userName, r.role_name as userRole, r.created_at as onboardedDate,u.user_status as status FROM users as u JOIN role_master as r ON u.role_id=r.role_id WHERE u.role_id IN(7) GROUP BY u.email ORDER BY u.f_name ASC";
    // echo $queryEdit1alluser;die;
    #prepare query
    $edit_rowalluser = $this->conn->prepare($queryEdit1alluser);
    #execute query 
    $edit_rowalluser->execute();
    return $edit_rowalluser;
}

#fetch sales user
public function getoneSalesuser(){
    $queryEditbyuser= "SELECT u.id, u.f_name, u.l_name, u.email, u.contact, u.role_id, u.user_status, u.user_created_date, u.salt_key, r.role_name FROM users as u JOIN role_master as r ON u.role_id=r.role_id WHERE id=".$this->salesuser_id."";
    $edit_rowbyuser = $this->conn->prepare($queryEditbyuser);
    $edit_rowbyuser->execute();
    return $edit_rowbyuser;
}

#update Sales-user
public function updateSalesuser(){
    #Email validation check
    $resultEmail = $this->verifyEmail($this->salesuser_id);
    $resultEmail->store_result();
    $rows = $resultEmail->num_rows;
    if($rows > 0){
        return 2;
    }else{
        #sanitize
        $this->first_name=htmlspecialchars(trim(strip_tags($this->first_name)));
        $this->last_name=htmlspecialchars(trim(strip_tags($this->last_name)));
        $this->email=htmlspecialchars(trim(strip_tags($this->email)));
		$this->contact=htmlspecialchars(trim(strip_tags($this->contact)));
        $this->role_id=trim(strip_tags($this->role_id));
        $this->status=trim(strip_tags($this->status));

        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) 
			return 3;

        #query to update record in user_details table
        $queryUser='UPDATE users SET `f_name`="'.$this->first_name.'", `l_name`="'.$this->last_name.'",`email`="'.$this->email.'", `contact`="'.$this->contact.'", user_status="'.$this->status.'", role_id="'.$this->role_id.'" WHERE `id`='.$this->salesuser_id.'';
        $stmt_user = $this->conn->prepare($queryUser);
        $stmt_user->execute();
        if($stmt_user->execute()){
            return true;
        }
    }
	return false;
}

public function adManagerData(){
    $querytabular = 'SELECT CONCAT(pub_fname," ",IFNULL(pub_lname,"")) as name,pub_email,date(created_at) as onboarddate,company_id, child_net_code,mcm_status,mcm_nonmcm_status,pub_id FROM '.$this->table_pub.'';
    $pubdash = $this->conn->prepare($querytabular);
    $pubdash->execute();
    $stmt_result3 = $pubdash->get_result();
    $resp3 = $stmt_result3->fetch_all(MYSQLI_ASSOC);
    return $resp3;
}
public function tagStatusData(){
    $querytag = 'SELECT DISTINCT child_net_code FROM mcm_childnetcode_report where child_net_code!=""';
    $pubTag = $this->conn->prepare($querytag);
    $pubTag->execute();
    $stmt_result = $pubTag->get_result();
    $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
    return $resp;
}
#Get AD manager Invite form data
public function adinviteData(){
    $queryForm = 'SELECT pm.pub_email,u.contact,pm.pub_type,pm.pub_org_type,pm.pub_company, pm.total_domain_mang,pm.edit_team_size,pm.direct_sale,pm.adx_for_display,pm.adx_for_video,pm.adx_for_app,pm.pub_display_share,pm.pub_video_share,pm.pub_app_share,pm.pub_adsense_id,pm.pub_adsense_share,pm.pub_analytics_id,pm.remark,pm.pub_uniq_id,pm.sal_id,pm.channel_id,pm.email_status FROM '.$this->table_pub.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email WHERE pm.pub_id='.$this->pub_id.'';

    $pubForm = $this->conn->prepare($queryForm);
    $pubForm->execute();
    $stmt_result = $pubForm->get_result();
    $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);

    #get app
    $respApp = $this->getApp($this->pub_id);
    #get sales team
    $respSal = $this->salesTeam();
    
    #get sales channel
    $respSalChannel = $this->salesChannel(); 
    

    return array($resp,$respApp,$respSal,$respSalChannel);
}  
 #Get App name
public function getApp($pub_id){
      $queryApp = 'SELECT id,app_name from publishers_app where pub_id='.$pub_id.'';
      $app = $this->conn->prepare($queryApp);
      $app->execute();
      $stmt_result_app = $app->get_result();
      $respApp = $stmt_result_app->fetch_all(MYSQLI_ASSOC);
      return $respApp;
}
#Get sales Team
 public function salesTeam(){
      $querySales = 'SELECT sal_id,sal_name from sales_team where sal_status="Y"';
      $salesR = $this->conn->prepare($querySales);
      $salesR->execute();
      $stmt_result_sales = $salesR->get_result();
      $respSal = $stmt_result_sales->fetch_all(MYSQLI_ASSOC);
      return $respSal;
 }
 #Get Roles
 public function role(){
    $querySales = 'SELECT role_id,role_name from role_master';
    $salesR = $this->conn->prepare($querySales);
    $salesR->execute();
    // $stmt_result_sales = $salesR->get_result();
    // $respSal = $stmt_result_sales->fetch_all(MYSQLI_ASSOC);
    return $salesR;
}
 #Get sales Channel
 public function salesChannel(){
      $querySales = 'SELECT channel_id,chan_name from sales_channel';
      $salesR = $this->conn->prepare($querySales);
      $salesR->execute();
      $stmt_result_sales = $salesR->get_result();
      $respSalChannel = $stmt_result_sales->fetch_all(MYSQLI_ASSOC);
      return $respSalChannel;
 }
 #Get reffer details
 public function refferDetails(){
      $queryReffer = 'SELECT * from refferbypub_detail where pub_uniqid="'.$this->pub_uniqid.'"';
      $reffer = $this->conn->prepare($queryReffer);
      $reffer->execute();
      $stmt_result_reffer = $reffer->get_result();
      $respreffer = $stmt_result_reffer->fetch_all(MYSQLI_ASSOC);
      return $respreffer;
 }
#update pusblisher invite data
public function updateInviteData(){

    $this->pub_type=htmlspecialchars(trim(strip_tags($this->pub_type)));
    $this->org_type=htmlspecialchars(trim(strip_tags($this->org_type)));
    $this->org_name=htmlspecialchars(trim(strip_tags($this->org_name)));
    $this->domain_managed=htmlspecialchars(trim(strip_tags($this->domain_managed)));
    $this->team_size=htmlspecialchars(trim(strip_tags($this->team_size)));
    $this->direct_sales=htmlspecialchars(trim(strip_tags($this->direct_sales)));
    $this->adx_for_display=htmlspecialchars(trim(strip_tags($this->adx_for_display)));
    $this->adx_for_video=htmlspecialchars(trim(strip_tags($this->adx_for_video)));
    $this->adx_for_app=htmlspecialchars(trim(strip_tags($this->adx_for_app)));
    $this->display_share=htmlspecialchars(trim(strip_tags($this->display_share)));
    $this->video_share=htmlspecialchars(trim(strip_tags($this->video_share)));
    $this->app_share=htmlspecialchars(trim(strip_tags($this->app_share)));
    $this->adsense_id=htmlspecialchars(trim(strip_tags($this->adsense_id)));
    $this->adsense_share=htmlspecialchars(trim(strip_tags($this->adsense_share)));
    $this->sales_id=htmlspecialchars(trim(strip_tags($this->sales_id)));
    $this->channel_id=htmlspecialchars(trim(strip_tags($this->channel_id)));
    $this->remark=htmlspecialchars(trim(strip_tags($this->remark)));
    $this->refer_name=htmlspecialchars(trim(strip_tags($this->refer_name)));
    $this->refer_email=htmlspecialchars(trim(strip_tags($this->refer_email)));
    $this->refer_contact=htmlspecialchars(trim(strip_tags($this->refer_contact)));
    $this->analytics_id=trim(strip_tags($this->analytics_id));
    $this->email_status=$this->email_status;

    $queryPubUp = 'UPDATE '.$this->table_pub.' SET `pub_type`="'.$this->pub_type.'",`pub_org_type`="'.$this->org_type.'",`pub_company`="'.$this->org_name.'",`total_domain_mang`='.$this->domain_managed.',`edit_team_size`="'.$this->team_size.'",`direct_sale`="'.$this->direct_sales.'",`adx_for_display`='.$this->adx_for_display.',`adx_for_video`='.$this->adx_for_video.',`adx_for_app`='.$this->adx_for_app.',`pub_display_share`='.$this->display_share.',`pub_video_share`='.$this->video_share.',`pub_app_share`='.$this->app_share.',`pub_adsense_id`="'.$this->adsense_id.'",`pub_adsense_share`='.$this->adsense_share.',`pub_analytics_id`="'.$this->analytics_id.'",`sal_id`='.$this->sales_id.',`channel_id`='.$this->channel_id.',`remark`="'.$this->remark.'" WHERE pub_id='.$this->pub_id.'';

    #prepare query
    $stmt_pubU = $this->conn->prepare($queryPubUp);
    #execute query
    $stmt_pubU->execute();


    #Refer details
    if($this->refer == "Y"){
        $queryRe = 'SELECT pub_uniq_id FROM '.$this->table_pub.' WHERE pub_email="'.$this->refer_email.'"';
            $Ref = $this->conn->prepare($queryRe);
            $Ref->execute();
            $stmt_result_ref = $Ref->get_result();
            $respRef = $stmt_result_ref->fetch_array(MYSQLI_ASSOC);

            $queryRefins = 'INSERT INTO `refferbypub_detail` SET `refferby_name`="'.$this->refer_name.'", `refferby_email`="'.$this->refer_email.'", `refferby_phno`="'.$this->refer_contact.'",`refferby_uniqid`="'.$respRef['pub_uniq_id'].'",`pub_uniqid`="'.$this->pub_uniq_id.'"';
            $Refins = $this->conn->prepare($queryRefins);
            $Refins->execute();
            
    }
    #Email
    // if($this->adx_for_display == 1 || $this->adx_for_video == 1 || $this->adx_for_app == 1){
    //     if($this->email_status == 'N'){
    //        $html = '<html><head>
    //         <meta charset="utf-8" />
    //         <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
    //          <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    //          <meta name="viewport" content="width=device-width, initial-scale=1" />
    //          <meta name="robots" content="noindex,nofollow" />
    //           <title>Welcome to Auxo Ads! Your domain is ready for monetization</title> 
    //             </head>
    //          <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
    //                 <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
    //                         <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
    //                         <tbody>
    //                             <tr style="background: #D6C4FB !important;">
    //                             <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
    //                                     </td>
    //                                     <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
    //                                     <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
    //                                     </td>-->
    //                                     </tr>
    //                                     </tbody>
    //                                     </table>
    //                                     <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;border: 5px solid #f2f2f0;">
    //                                     <tbody>
    //                                     <tr>
    //                                     <td>
    //                                     <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->org_name.',</p>
    //                                     <p style="font-size: 18px; line-height: 33px;">Welcome to Auxo Ads!</p>
    //                                     <p style="font-size: 18px; line-height: 33px;">Auxo Ads team aims to help publishers manage the increasing complex world of programmatic monetization, leveraging data and traffic management.</p>
    //                                     <p style="font-size: 18px; line-height: 33px;">Auxo platform ensures revenue maximization by creating a robust and custom full stack ad management tailored for your inventory. This custom setup leverages data and our direct connect with advertiser ecosystem to grow your revenues.</p>
    //                                     <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >We will coordinate with you on the below Starting Step :</span></u>
    //                                     <ul>
    //                                     <li style="font-size: 18px; line-height: 33px;">Activate GPT tags that initiate monetization via GAM360.</li>
    //                                     <li style="font-size: 18px; line-height: 33px;">Integrate our code in the footer that enables better tagging.</li>
    //                                     <li style="font-size: 18px; line-height: 33px;">Set up your domain\'s programmatic demand stack.</li>
    //                                     </ul>
    //                                     <p style="font-size: 18px; line-height: 33px;"> We will get things up and running shortly.</p>
    //                                     <p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://www.safe.cybermediaservices.net/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Newsletter</span></a>, or read our <a href="https://blog-safe.cybermediaservices.net/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>
    //                                     <br>
    //                                     <!--<p>
    //                                     <a href="javascript: void(0);" style="display: inline-block;padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
    //                                     </p>-->
    //                                     <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Stay Safe & Happy Earnings!</span><br>
    //                                     <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
    //                                     </td>
    //                                     </tr>
    //                                     </tbody>
    //                                     </table>
    //                                     <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">  
    //                                     <span style="position: relative; top: -5px;">
    //                                     Visit us at
    //                                     <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
    //                                         <br/>
    //                                         <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
    //                                         </span>
    //                                         </span>
    //                                         </center>
    //                                         </center>
    //                                         </body>
    //                                         </html>'; 
    //        $subject = "Welcome to Auxo Ads! Your domain is ready for monetization!";
    //        $mailer = $this->mailPub($html,$subject,$this->pub_email);
            
    //     }
    // }
    return true;

}
#get ad manager domain listing
public function adManagerDomainData(){
    $queryDomain = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pub_email,date(pm.created_at) as onboarddate,pm.company_id,pm.child_net_code,pw.site_id,pw.web_name,pw.site_id,pw.web_status,pw.id as domain_id,pm.mcm_nonmcm_status,pm.pub_id from '.$this->table_web.' as pw join '.$this->table_pub.' as pm on pm.pub_id=pw.pub_id';
    $domain = $this->conn->prepare($queryDomain);
    $domain->execute();
    $stmt_result_domain = $domain->get_result();
    $respDomain = $stmt_result_domain->fetch_all(MYSQLI_ASSOC);
    return $respDomain;
}
#Get AD manager Domain form data
public function adDomainData(){
    $queryForm = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_email,pm.pub_type,pm.pub_org_type,pm.pub_company,web.web_name,web.web_traffic_source,web.web_primary_geo,web.web_inventory_qty,web.email_status,web.vertical,web.vertical2,web.web_analtics_id,u.contact,web.email_status,pm.mcm_nonmcm_status FROM '.$this->table_pub.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email inner join '.$this->table_web.' as web on pm.pub_id=web.pub_id WHERE pm.pub_id='.$this->pub_id.' and web.id='.$this->domain_id.'';
    $pubForm = $this->conn->prepare($queryForm);
    $pubForm->execute();
    $stmt_result = $pubForm->get_result();
    $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
    return $resp;
}
# post domain form data
public function updateDomainData(){
    
    $this->traffic_source=htmlspecialchars(trim(strip_tags($this->traffic_source)));
    $this->primary_geo=htmlspecialchars(trim(strip_tags($this->primary_geo)));
    $this->inventory_quality=htmlspecialchars(trim(strip_tags($this->inventory_quality)));
    $this->vertical=htmlspecialchars(trim(strip_tags($this->vertical)));
    $this->vertical2=htmlspecialchars(trim(strip_tags($this->vertical2)));
    $this->analytics_id=htmlspecialchars(trim(strip_tags($this->analytics_id)));



    $queryDomainUp = 'UPDATE '.$this->table_web.' SET `web_analtics_id`="'.$this->analytics_id.'",`web_traffic_source`="'.$this->traffic_source.'",`web_primary_geo`="'.$this->primary_geo.'",`web_inventory_qty`="'.$this->inventory_quality.'",`vertical`="'.$this->vertical.'",`vertical2`="'.$this->vertical2.'" WHERE id='.$this->domain_id.'';

    #prepare query
    $stmt_DomainU = $this->conn->prepare($queryDomainUp);
    #execute query
    $stmt_DomainU->execute();
    if($this->mcm_nonmcm_status == 1){

        if($this->email_status == 'N')
        {
    //    $subject="Auxo Update - Your domain is ready for monetization"; 
    //    $html = '<html><head>
    //                         <meta charset="utf-8" />
    //                                     <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
    //                                     <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    //                                     <meta name="viewport" content="width=device-width, initial-scale=1" />
    //                                     <meta name="robots" content="noindex,nofollow" />
    //                                     <title>Auxo Update - Your domain is ready for monetization</title> 
    //                                     </head>
    //                                     <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
    //                                     <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
    //                                     <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
    //                                     <tbody>
    //                                     <tr style="background: #D6C4FB !important;">
    //                                     <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
    //                                     </td>
    //                                     <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
    //                                     <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
    //                                     </td>-->
    //                                     </tr>
    //                                     </tbody>
    //                                     </table>
    //                                         <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;border: 5px solid #f2f2f0;">
    //                                             <tbody>
    //                                             <tr>
    //                                                 <td>
    //                                                 <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->pub_name.',</p>
                                        
    //                                                 <p style="font-size: 18px; line-height: 33px;">Congratulations! Your domain '.$this->web_name.' has been approved for GAM 360 monetization.</p>

    //                                                 <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >We will coordinate with you on the below Starting Step : </span></u>
    //                                                 <ul>
    //                                                     <li style="font-size: 18px; line-height: 33px;">Activate GPT tags that initiate monetization via GAM360.</li>
    //                                                     <li style="font-size: 18px; line-height: 33px;">Integrate our code in the footer that enables better tagging.</li>
    //                                                     <li style="font-size: 18px; line-height: 33px;">Set up your domain\'s programmatic demand stack.</li>
    //                                                 </ul>

    //                                                 <p style="font-size: 18px; line-height: 33px;">We will get things up and running shortly.</p>

    //                                                 <p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://www.safe.cybermediaservices.net/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Ads Newsletter</span></a>, or read our <a href="https://blog-safe.cybermediaservices.net/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>
    //                                                 <br>
                                        
    //                                                 <!--<p>
    //                                                 <a href="javascript: void(0);" style="display: inline-block;
    //                                                 padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
    //                                                 </p>-->
    //                                                     <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Stay Safe & Happy Earnings!</span><br>
    //                                                     <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
    //                                                 </td>
    //                                                 </tr>
    //                                             </tbody>
    //                                             </table>
                                        
    //                                         <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">  
    //                                         <span style="position: relative; top: -5px;">
    //                                             Visit us at
    //                                             <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
    //                                             <br/>
    //                                             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
    //                                             </span>
    //                                         </span>
    //                                         </center>
    //                                     </center>
    //                                     </body>
    //                                     </html>'; 
            
        //    $mailer = $this->mailPub($html,$subject,$this->pub_email);
    }
    }else{
    if($this->email_status == "N"){
        // $subject="Welcome to Auxo Ads! Your domain is ready for monetization!";
        // $html = '<html><head>
        //                             <meta charset="utf-8" />
        //                             <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
        //                             <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        //                             <meta name="viewport" content="width=device-width, initial-scale=1" />
        //                             <meta name="robots" content="noindex,nofollow" />
        //                             <title>Welcome to Auxo Ads! Your domain is ready for monetization</title> 
        //                             </head>
        //                             <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
        //                             <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
        //                             <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
        //                             <tbody>
        //                             <tr style="background: #D6C4FB !important;">
        //                             <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
        //                             </td>
        //                             <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
        //                             <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
        //                             </td>-->
        //                             </tr>
        //                             </tbody>
        //                             </table>
        //                             <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;border: 5px solid #f2f2f0;">
        //                             <tbody>
        //                             <tr>
        //                             <td>
        //                             <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->pub_name.',</p>
        //                             <p style="font-size: 18px; line-height: 33px;">Domain : '.$this->web_name.' has been successfully on-boarded! </p>
        //                             <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >Our team will coordinate with you on the below starting step :</span></u>
        //                             <ul>
        //                             <li style="font-size: 18px; line-height: 33px;">Set up your custom ad stack that powers your programmatic monetization.</li>
        //                             <li style="font-size: 18px; line-height: 33px;">Integrate our one-line code that enables faster ads.txt.</li>
        //                             <li style="font-size: 18px; line-height: 33px;">Integrate our CDP solution that powers your data analytics.</li>
        //                             </ul>
        //                             <p style="font-size: 18px; line-height: 33px;">Furthermore, we will also provide a list of recommendations to get your optimizations started.</p>
        //                             <p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://www.safe.cybermediaservices.net/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Ads Newsletter</span></a>, or read our <a href="https://blog-safe.cybermediaservices.net/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>
        //                             <br>
        //                             <!--<p>
        //                             <a href="javascript: void(0);" style="display: inline-block;padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
        //                             </p>-->
        //                             <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Stay Safe & Happy Earnings!</span><br>
        //                             <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
        //                             </td>
        //                             </tr>
        //                             </tbody>
        //                             </table>
        //                             <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">  
        //                             <span style="position: relative; top: -5px;">Visit us at
        //                             <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
        //                                 <br/>
        //                                 <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
        //                                 </span>
        //                                 </span>
        //                                 </center>
        //                                 </center>
        //                                 </body>
        //                                 </html>';

        //    $mailer = $this->mailPub($html,$subject,$this->pub_email);
    }
    } 

    return true;
}
#get ad manager app listing
public function adManagerAppData(){
    $queryApp = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pub_email,date(pm.created_at) as onboarddate,pm.company_id,pm.child_net_code,pw.app_name,pw.status,pw.id as app_id,pm.pub_id,pm.child_net_code from '.$this->table_app.' as pw join '.$this->table_pub.' as pm on pm.pub_id=pw.pub_id';
    $app = $this->conn->prepare($queryApp);
    $app->execute();
    $stmt_result_app = $app->get_result();
    $respApp = $stmt_result_app->fetch_all(MYSQLI_ASSOC);
    return $respApp;

}
#Get AD manager APP form data
public function adAppData(){
    $queryForm = 'SELECT pm.pub_email,pm.pub_type,pm.pub_org_type,pm.pub_company,app.app_name,app.traffic_source,app.primary_geo,app.inventory_qty,app.vertical,app.vertical2,u.contact FROM '.$this->table_pub.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email inner join '.$this->table_app.' as app on pm.pub_id=app.pub_id WHERE pm.pub_id='.$this->pub_id.' and app.id='.$this->app_id.'';
    $pubForm = $this->conn->prepare($queryForm);
    $pubForm->execute();
    $stmt_result = $pubForm->get_result();
    $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);

    return $resp;
} 
# post domain form data
public function updateAppData(){
    $this->traffic_source=htmlspecialchars(trim(strip_tags($this->traffic_source)));
    $this->primary_geo=htmlspecialchars(trim(strip_tags($this->primary_geo)));
    $this->inventory_quality=htmlspecialchars(trim(strip_tags($this->inventory_quality)));
    $this->vertical=htmlspecialchars(trim(strip_tags($this->vertical)));
    $this->vertical2=htmlspecialchars(trim(strip_tags($this->vertical2)));
    
    $queryAppUp = 'UPDATE '.$this->table_app.' SET `traffic_source`="'.$this->traffic_source.'",`primary_geo`="'.$this->primary_geo.'",`inventory_qty`="'.$this->inventory_quality.'",`vertical`="'.$this->vertical.'",`vertical2`="'.$this->vertical2.'" WHERE id='.$this->app_id.'';

    #prepare query
    $stmt_AppU = $this->conn->prepare($queryAppUp);
    #execute query
    if($stmt_AppU->execute()){

    return true; 
    }else{
        return false;
    }
}
#Active Account Manager Count
public function Activesalesmanager(){
    // $query_active= 'SELECT role_id,user_status FROM '.$this->table_user.' WHERE role_id="8"';
    // #prepare query
    // $active_acc_manager = $this->conn->prepare($query_active);
    // #execute query 
    // $active_acc_manager->execute();
    // return $active_acc_manager;


    // Total Active / Inactive 
    // $active=0;
    // $inactive=0;
    // // echo $fetched_team_id;die;
    // $query_active_inactive = "SELECT pub.pub_email, pub.pub_adx_partner_id, pub.childNetworkCode, pub.pub_adsense_partner_id, ud.pub_adx_status, ud.pub_adsense_status FROM publisher_master AS pub JOIN user_details AS ud ON pub.pub_email=ud.email WHERE pub.sales_team_id=".$fetched_team_id."";
    // $query_active_inactive = $database->dbc->prepare($query_active_inactive);
    // $query_active_inactive->execute();
    // $query_active_inactive = $query_active_inactive->fetchall(PDO::FETCH_ASSOC);

    // foreach($query_active_inactive as $val_act_inact) {
    //     if(($val_act_inact['pub_adx_partner_id']!="" && $val_act_inact['pub_adx_status']==1) || ($val_act_inact['pub_adsense_partner_id']!="" && $val_act_inact['pub_adsense_status']==1) || ($val_act_inact['childNetworkCode']!="")){
    //         $active++;
    //     }
    //     else{
    //         $inactive++;
    //     }
    // }
}
#all publisher of adx for show more button
public function allpubs(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, bdr.impression,  usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net, SUM(mcru.mcm_cnc_impr) as impression FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_mcm =0;
    $net_mcm = 0;
    $gross_adx_mcm =0;
    $gross_adx_impr =0;
    $net_adx_mcm =0;
    $gross_adsense =0;
    $gross_direct =0;
    $top_adx = array();
    foreach ($rowMcm as $value_total) {
            $publisher_name = $value_total['adx_p_name'];
            #Gross
            if(isset($value_total['gross_adx_mcm'])){
            $gross_mcm = $value_total['gross_adx_mcm'];
            }
          
            #Net
            if(isset($value_total['net_adx_mcm'])){
            $net_mcm = $value_total['net_adx_mcm'];
            }

             #impr
             if(isset($value_total['impression'])){
                $gross_adx_impr = $value_total['impression'];
            }
           
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;
            $top_adx[$publisher_name]['impr'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_mcm);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_mcm);
            }
            if(isset($top_adx[$publisher_name]['impr'])){
                $top_adx[$publisher_name]['impr'] += ($gross_adx_impr);
            }
    }
    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        $top[$i]['PublisherName'] = $top_key; 
        $top[$i]['Impressions'] = number_format($top_value['impr'], 2);
        $top[$i]['CPM'] = number_format(($top_value['gross']/$top_value['impr'])*1000, 2);
        $top[$i]['Topline'] = number_format($top_value['gross'], 2); 
        $top[$i]['Bottomline'] = number_format($top_value['net'], 2);
        $i++; 
    } 
    $json_response = json_encode($top);
    return $json_response;
}
#overall tab For sales user dashboard
public function getrevenuesalesuser(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!='' and psm.sal_id=".$this->sales_id."";
	#prepare query
    $row1 = $this->conn->prepare($query_mcm);
    #execute query 
    $row1->execute();
    $stmt_mcm = $row1->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;

    foreach ($rowMcm as $value_total) {
    if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
        $gross_adx_mcm += $value_total['gross_adx_mcm'];
    }
    if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
        $net_adx_mcm += $value_total['net_adx_mcm'];
    }
    }
    
    $gross_adx = $gross_adx_mcm;
    $net_adx = $net_adx_mcm;
    //     $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
    //     #prepare query
    //     $rows = $this->conn->prepare($query_pubId);
    //     #execute query 
    //     $rows->execute();
    //     //return $row;
    //     $stmt_result = $rows->get_result();
    //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    // foreach($row_result as $value_pubId) {

    //     $uniqid = $value_pubId['pub_uniq_id'];

    //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //             $afc_share = $value_pubId['pub_display_share'];
    //         }
    //         else{
    //             $afc_share = 15;
    //         }
    //         // $afc_share = 15;
    //     #Directdeal
    //     $command_directdeal_gross = new MongoDB\Driver\Command([
    //         'aggregate' => 'DFP_directdeal',
    //         'pipeline' => [
    //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$date'
    //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
    //     foreach($directdeal_gross as $value_direct) {
    //     $gross_direct += number_format(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //     $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)*$afc_share/1000),2);
    //     }

    //     #HeaderBidder
    //     $command_HB = new MongoDB\Driver\Command([
    //         'aggregate' => 'header_bidder',
    //         'pipeline' => [
    //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$DATE'
    //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
    //     foreach($HB as $value_hb) {
    //     $gross_hb += number_format($value_hb->adserver_revenue,2);
    //     $net_hb += number_format(($value_hb->adserver_revenue*$afc_share/100),2);
    //     }

    // }

        #Adsense
        // $command_adsense_gross = new MongoDB\Driver\Command([
        //     'aggregate' => 'adsense_daywise',
        //     'pipeline' => [
        //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
        //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
        //     ],
        //     'cursor' => new stdClass,
        // ]);
        // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
        // foreach($adsense_gross as $value_adsense) {
        // $gross_adsense += $value_adsense->gross_adsense;
        // }

    // $total_gross = $gross_adx+$gross_hb+$gross_adsense+$gross_direct;
    // $total_net = $net_adx+$net_hb+$gross_adsense+$gross_direct;
    // $total_gross = $gross_adx+$gross_hb+$gross_direct;
    // $total_net = $net_adx+$net_hb+$net_direct;
    $total_gross = $gross_adx;
    $total_net = $net_adx;

    #Net % breakup
    $net_percent_adx = (($net_adx/$total_net)*100);
    // $net_percent_adsense = (($gross_adsense/$total_net)*100);
    // $net_percent_hb = (($net_hb/$total_net)*100);
    // $net_percent_direct = (($net_direct/$total_net)*100);

    // $total_netPer = $net_percent_adx+$net_percent_adsense+$net_percent_hb+$net_percent_direct;
    // $total_netPer = $net_percent_adx+$net_percent_hb+$net_percent_direct;
    $total_netPer = $net_percent_adx;



    return json_encode(array(
        'ADX'=>array('Topline'=>number_format($gross_adx, 2), 'Bottomline'=>number_format($net_adx, 2), 'NetBreakup'=>number_format($net_percent_adx, 2)),
        // 'HB'=>array('Topline'=>number_format($gross_hb, 2), 'Bottomline'=>number_format($net_hb, 2), 'NetBreakup'=>number_format($net_percent_hb, 2)),
        // 'Adsense'=>array('Topline'=>number_format($gross_adsense, 2), 'Bottomline'=>number_format($gross_adsense, 2), 'NetBreakup'=>number_format($net_percent_adsense, 2)),
        // 'DirectDeal'=>array('Topline'=>number_format($gross_direct, 2), 'Bottomline'=>number_format($net_direct, 2), 'NetBreakup'=>number_format($net_percent_direct, 2)),
        'HB'=>array('Topline'=>'0', 'Bottomline'=>'0', 'NetBreakup'=>'0'),
        'DirectDeal'=>array('Topline'=>'0', 'Bottomline'=>'0', 'NetBreakup'=>'0'),
        'Total'=>array('Topline'=>number_format($total_gross, 2), 'Bottomline'=>number_format($total_net, 2), 'NetBreakup'=>number_format($total_netPer, 2))                  
    ));

}
#Get Top 15 Adx Contributor for sales user
public function Top15AdxContributorsalesuser(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!='' and psm.sal_id=".$this->sales_id."";
    // echo $query_mcm;die;
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_mcm =0;
    $net_mcm = 0;
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_adsense =0;
    $gross_direct =0;
    $top_adx = array();
    foreach ($rowMcm as $value_total) {
            $publisher_name = $value_total['adx_p_name'];
            #Gross
            if(isset($value_total['gross_adx_mcm'])){
            $gross_mcm = $value_total['gross_adx_mcm'];
            }
          
            #Net
            if(isset($value_total['net_adx_mcm'])){
            $net_mcm = $value_total['net_adx_mcm'];
            }
           
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_mcm);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_mcm);
            }
    }


    // $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
    //     #prepare query
    //     $rows = $this->conn->prepare($query_pubId);
    //     #execute query 
    //     $rows->execute();
    //     //return $row;
    //     $stmt_result = $rows->get_result();
    //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    // foreach($row_result as $value_pubId) {
    //     $publisher_name = $value_pubId['adx_p_name'];
    //     $uniqid = $value_pubId['pub_uniq_id'];
    //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //             $afc_share = $value_pubId['pub_display_share'];
    //         }
    //         else{
    //             $afc_share = 15;
    //         }
    //         // $afc_share = 15;
    //     #Directdeal
    //     $command_directdeal_gross = new MongoDB\Driver\Command([
    //         'aggregate' => 'DFP_directdeal',
    //         'pipeline' => [
    //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$date'
    //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
       
    //     foreach($directdeal_gross as $value_direct) {
    //         $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
    
    //         // publisher WISE CODE
    //         $top_adx[$publisher_name]['gross'] += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $top_adx[$publisher_name]['net'] += round((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
    //     }
    //     // #HeaderBidder
    //     $command_HB = new MongoDB\Driver\Command([
    //         'aggregate' => 'header_bidder',
    //         'pipeline' => [
    //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$DATE'
    //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
    //     // echo "<pre>";print_r($HB);die;
    //     foreach($HB as $value_hb) {
    //         $gross_hb += round($value_hb->adserver_revenue,2);
    //         $net_hb += round(($value_hb->adserver_revenue*$afc_share/100),2);
    
    //         // publisher WISE CODE
    //         $top_adx[$publisher_name]['gross'] += round($value_hb->adserver_revenue,2);
    //         $top_adx[$publisher_name]['net'] += round(($value_hb->adserver_revenue*$afc_share/100),2);
    //     }
        

    // }
    
    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        // if($i<=15){
        $top[$i]['PublisherName'] = $top_key; 
        $top[$i]['Topline'] = number_format($top_value['gross'], 2); 
        $top[$i]['Bottomline'] = number_format($top_value['net'], 2);
        $i++;  
        // }
    } 
    $json_response = json_encode($top);
    return $json_response;
}
#all publisher of adx for show more button
public function allpubssalesuser(){
    #MCM
    $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, bdr.impression,  usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net, SUM(mcru.mcm_cnc_impr) as impression FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$this->strtdate."' and '".$this->enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!='' and psm.sal_id=".$this->sales_id."";
    $row_mcm = $this->conn->prepare($query_mcm);
    #execute query 
    $row_mcm->execute();
    $stmt_mcm = $row_mcm->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_mcm =0;
    $net_mcm = 0;
    $gross_adx_mcm =0;
    $gross_adx_impr =0;
    $net_adx_mcm =0;
    $gross_adsense =0;
    $gross_direct =0;
    $top_adx = array();
    foreach ($rowMcm as $value_total) {
            $publisher_name = $value_total['adx_p_name'];
            #Gross
            if(isset($value_total['gross_adx_mcm'])){
            $gross_mcm = $value_total['gross_adx_mcm'];
            }
          
            #Net
            if(isset($value_total['net_adx_mcm'])){
            $net_mcm = $value_total['net_adx_mcm'];
            }

             #impr
             if(isset($value_total['impression'])){
                $gross_adx_impr = $value_total['impression'];
            }
           
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;
            $top_adx[$publisher_name]['impr'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_mcm);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_mcm);
            }
            if(isset($top_adx[$publisher_name]['impr'])){
                $top_adx[$publisher_name]['impr'] += ($gross_adx_impr);
            }
    }
    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        $top[$i]['PublisherName'] = $top_key; 
        $top[$i]['Impressions'] = number_format($top_value['impr'], 2);
        $top[$i]['CPM'] = number_format(($top_value['gross']/$top_value['impr'])*1000, 2);
        $top[$i]['Topline'] = number_format($top_value['gross'], 2); 
        $top[$i]['Bottomline'] = number_format($top_value['net'], 2);
        $i++; 
    } 
    $json_response = json_encode($top);
    return $json_response;
}
public function adsenseUniqid(){
    $pubId ='select CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id as id from '.$this->table_pub.' as pm join '.$this->table_user.' as ud on ud.uniq_id=pm.pub_uniq_id where ud.pub_adsense_status=1 AND pm.pub_adsense_id!="" AND pm.pub_adsense_id IS NOT NULL AND pm.sal_id='.$this->sales_id.'';
    $pubIds = $this->conn->prepare($pubId);
    $pubIds->execute();
    $stmt_resultadsense = $pubIds->get_result();
    $pubIdresult = $stmt_resultadsense->fetch_all(MYSQLI_ASSOC);
    return $pubIdresult;
   }
   public function adxUniqid(){
    $pubId ='select CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id as id from '.$this->table_pub.' as pm join '.$this->table_user.' as ud on ud.uniq_id=pm.pub_uniq_id where ud.pub_adx_status=1 AND pm.adx_partner_id!="" AND pm.adx_partner_id IS NOT NULL AND pm.sal_id='.$this->sales_id.'';
    $pubIds = $this->conn->prepare($pubId);
    $pubIds->execute();
    $stmt_resultadsense = $pubIds->get_result();
    $pubIdresult = $stmt_resultadsense->fetch_all(MYSQLI_ASSOC);
    return $pubIdresult;
   }
#get interval Date
public function getIntervalDate($date_interval){
	$query ="SELECT ".$date_interval;
	$rep = $this->conn->prepare($query);

	$rep->execute();
	$stmt_result = $rep->get_result();
	$resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
	return $resp;
}
#Network Partner	
public function getOverAllPublisherList($accounts, $strtdate, $enddate){
	if($accounts=="adx"){
		$query ='SELECT CONCAT(PM.pub_fname," ",IFNULL(PM.pub_lname,"")) as name, PM.pub_email, PM.pub_uniq_id, PM.pub_id, PM.sal_id, ST.sal_name, PM.channel_id, SC.chan_name, PM.child_net_code, date(PM.created_at) as onboarddate, U.pub_adx_status, U.pub_adsense_status, U.user_status FROM publisher_master as PM JOIN users as U ON U.uniq_id=PM.pub_uniq_id LEFT JOIN sales_channel as SC ON SC.channel_id=PM.channel_id LEFT JOIN sales_team as ST ON ST.sal_id=PM.sal_id where U.pub_adx_status=1 AND PM.child_net_code !="" AND PM.child_net_code IS NOT NULL';
		$rep = $this->conn->prepare($query);

		$rep->execute();
		$stmt_result = $rep->get_result();
		$resultData = $stmt_result->fetch_all(MYSQLI_ASSOC);
		
		$Final_Array = array();
		if(count($resultData)>0){
			foreach($resultData as $key => $resultVal){
				$childNetwork = $resultVal['child_net_code'];

				$response = $this->conn->prepare("SELECT site_name, sum(mcm_clicks) as clicks, sum(mcm_adreq) as adr, sum(mcm_matchreq) as madr, sum(mcm_impression) as adimr, ROUND(SUM(mcm_earnings), 2) as revenue FROM `mcm_domainwise_report` WHERE `child_net_code` LIKE '".$childNetwork."' AND mcm_earnings!=0 AND (ad_domain_date BETWEEN '".$strtdate."' AND '".$enddate."') GROUP by site_name");
				$response->execute();
				$statementResult = $response->get_result();
				$resultOverAll = $statementResult->fetch_all(MYSQLI_ASSOC);

				if(count($resultOverAll)>0){
					foreach($resultOverAll as $overAllKey => $overAllVal){

					@$Final_Array[$overAllVal['site_name']]['site_name'] = $overAllVal['site_name'];
					
					@$Final_Array[$overAllVal['site_name']]['name'] = $resultVal['name'];
					@$Final_Array[$overAllVal['site_name']]['pub_email'] = $resultVal['pub_email'];
					@$Final_Array[$overAllVal['site_name']]['pub_uniq_id'] = $resultVal['pub_uniq_id'];
					@$Final_Array[$overAllVal['site_name']]['pub_id'] = $resultVal['pub_id'];
					@$Final_Array[$overAllVal['site_name']]['sal_id'] = $resultVal['sal_id'];
					@$Final_Array[$overAllVal['site_name']]['sal_name'] = $resultVal['sal_name'];
					@$Final_Array[$overAllVal['site_name']]['channel_id'] = $resultVal['channel_id'];
					@$Final_Array[$overAllVal['site_name']]['chan_name'] = $resultVal['chan_name'];
					@$Final_Array[$overAllVal['site_name']]['child_net_code'] = $resultVal['child_net_code'];
					@$Final_Array[$overAllVal['site_name']]['pub_adx_status'] = $resultVal['pub_adx_status'];
					@$Final_Array[$overAllVal['site_name']]['pub_adsense_status'] = $resultVal['pub_adsense_status'];
					@$Final_Array[$overAllVal['site_name']]['onboarddate'] = $resultVal['onboarddate'];
					@$Final_Array[$overAllVal['site_name']]['user_status'] = $resultVal['user_status'];

					@$Final_Array[$overAllVal['site_name']]['adr']+=$overAllVal['adr'];
					@$Final_Array[$overAllVal['site_name']]['adimr']+=$overAllVal['adimr'];
					@$Final_Array[$overAllVal['site_name']]['madr']+=$overAllVal['madr'];
					@$Final_Array[$overAllVal['site_name']]['clicks']+=$overAllVal['clicks'];
					@$Final_Array[$overAllVal['site_name']]['covg'] = $Final_Array[$overAllVal['site_name']]['madr'] > 0 ? number_format(($Final_Array[$overAllVal['site_name']]['madr']*100)/$Final_Array[$overAllVal['site_name']]['adr'],1) :'0.00';
					@$Final_Array[$overAllVal['site_name']]['ctr'] = $Final_Array[$overAllVal['site_name']]['adimr'] > 0 ? number_format($Final_Array[$overAllVal['site_name']]['clicks']/$Final_Array[$overAllVal['site_name']]['adimr']*100,1):'0.00';
					@$Final_Array[$overAllVal['site_name']]['revenue']+=number_format($overAllVal['revenue'],2);
					@$Final_Array[$overAllVal['site_name']]['ecpm'] = $Final_Array[$overAllVal['site_name']]['adimr'] > 0 ? number_format($Final_Array[$overAllVal['site_name']]['revenue']/$Final_Array[$overAllVal['site_name']]['adimr']*1000,2) : '0.00';
					@$Final_Array[$overAllVal['site_name']]['revenue_15']+=number_format(($overAllVal['revenue']-($overAllVal['revenue']*0.15)),2);
					}
				}
			}
		}
		return $Final_Array;

	}else if($accounts=="adsense"){

		$rep = $this->conn->prepare('SELECT CONCAT(PM.pub_fname," ",IFNULL(PM.pub_lname,"")) as name, PM.pub_email, PM.pub_uniq_id, PM.pub_adsense_id, PM.pub_id, PM.sal_id, ST.sal_name, PM.channel_id, SC.chan_name, PM.child_net_code, date(PM.created_at) as onboarddate, U.pub_adx_status, U.pub_adsense_status, U.user_status FROM publisher_master as PM JOIN users as U ON U.uniq_id=PM.pub_uniq_id LEFT JOIN sales_channel as SC ON SC.channel_id=PM.channel_id LEFT JOIN sales_team as ST ON ST.sal_id=PM.sal_id where U.pub_adsense_status=1 AND PM.pub_adsense_id!="" AND PM.pub_adsense_id IS NOT NULL AND PM.pub_fname!="" AND PM.pub_fname IS NOT NULL');
		$rep->execute();
		$stmt_result = $rep->get_result();
		$resultData = $stmt_result->fetch_all(MYSQLI_ASSOC);

		$Final_Array = array();
		$adSenseResult = array();
		if(count($resultData)>0){
			foreach($resultData as $key => $resultVal){
				$pubUniqId = $resultVal['pub_uniq_id'];

$command_lplist = new MongoDB\Driver\Command([
'aggregate' => 'adsense_domainwise',
'pipeline' => [
	['$match'=>['date'=>['$gte' =>$strtdate,'$lte' =>$enddate,]]],
	['$group' => ['_id' => '$domain_name', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]]
],
'cursor' => new stdClass,
]);
$cursor_lplist = $this->connMongoDb->executeCommand($pubUniqId,$command_lplist);

foreach($cursor_lplist as $val) {
	$adSenseResult[] = array(
		'site_name'=>$val->_id,
		'adr'=>$val->totalad_requests,
		'adimr'=>$val->totalad_imp,
		'madr'=>$val->totalmatchad_requests,
		'clicks'=>$val->total_click,
		'covg'=>$val->covg,
		'ctr'=>$val->ctr,
		'revenue'=>$val->total_earning
	);
}

foreach($adSenseResult as $value){

	@$total_array[$value['site_name']]['site_name'] = $value['site_name'];

@$total_array[$value['site_name']]['name'] = $resultVal['name'];
@$total_array[$value['site_name']]['pub_email'] = $resultVal['pub_email'];
@$total_array[$value['site_name']]['pub_uniq_id'] = $resultVal['pub_uniq_id'];
@$total_array[$value['site_name']]['pub_id'] = $resultVal['pub_id'];
@$total_array[$value['site_name']]['sal_id'] = $resultVal['sal_id'];
@$total_array[$value['site_name']]['sal_name'] = $resultVal['sal_name'];
@$total_array[$value['site_name']]['channel_id'] = $resultVal['channel_id'];
@$total_array[$value['site_name']]['chan_name'] = $resultVal['chan_name'];
@$total_array[$value['site_name']]['child_net_code'] = $resultVal['child_net_code'];
@$total_array[$value['site_name']]['pub_adx_status'] = $resultVal['pub_adx_status'];
@$total_array[$value['site_name']]['pub_adsense_status'] = $resultVal['pub_adsense_status'];
@$total_array[$value['site_name']]['onboarddate'] = $resultVal['onboarddate'];
@$total_array[$value['site_name']]['user_status'] = $resultVal['user_status'];

	@$total_array[$value['site_name']]['adr']+=$value['adr'];
	@$total_array[$value['site_name']]['adimr']+=$value['adimr'];
	@$total_array[$value['site_name']]['madr']+=$value['madr'];
	@$total_array[$value['site_name']]['clicks']+=$value['clicks'];
	@$total_array[$value['site_name']]['covg'] = $total_array[$value['site_name']]['madr'] > 0 ? number_format(($total_array[$value['site_name']]['madr']*100)/$total_array[$value['site_name']]['adr'],1) :'0.00';
	@$total_array[$value['site_name']]['ctr'] = $total_array[$value['site_name']]['adimr'] > 0 ? number_format($total_array[$value['site_name']]['clicks']/$total_array[$value['site_name']]['adimr']*100,1):'0.00';
	@$total_array[$value['site_name']]['revenue']+=number_format($value['revenue'],2);
	@$total_array[$value['site_name']]['ecpm'] = $total_array[$value['site_name']]['adimr'] > 0 ? number_format($total_array[$value['site_name']]['revenue']/$total_array[$value['site_name']]['adimr']*1000,2) : '0.00';
	@$total_array[$value['site_name']]['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);
}


			}
		}

		return $total_array;
	}
}
#Get Top 15 Adx Contributor  with deal and header bidder
// public function Top15AdxContributor($accounts, $strtdate, $enddate){
//     #MCM
//     if($accounts=="adx"){
//     $query_mcm = "SELECT bdr.child_net_code,psm.adx_p_name, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$strtdate."' and '".$enddate."'   GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id WHERE psm.child_net_code!=''";
//     $row_mcm = $this->conn->prepare($query_mcm);
//     #execute query 
//     $row_mcm->execute();
//     $stmt_mcm = $row_mcm->get_result();
//     $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
//     $gross_mcm =0;
//     $net_mcm = 0;
//     $gross_adx_mcm =0;
//     $net_adx_mcm =0;
//     $gross_adsense =0;
//     $gross_direct =0;
//     $top_adx = array();
//     foreach ($rowMcm as $value_total) {
//             $publisher_name = $value_total['adx_p_name'];
//             #Gross
//             if(isset($value_total['gross_adx_mcm'])){
//             $gross_mcm = $value_total['gross_adx_mcm'];
//             }
          
//             #Net
//             if(isset($value_total['net_adx_mcm'])){
//             $net_mcm = $value_total['net_adx_mcm'];
//             }
           
//             $top_adx[$publisher_name]['gross'] = 0;
//             $top_adx[$publisher_name]['net'] = 0;
//             $top_adx[$publisher_name]['pub_name'] = 0;

//             #TOP ADX PUB CODE
//             if(isset($top_adx[$publisher_name]['pub_name'])){
//             $top_adx[$publisher_name]['pub_name'] = $publisher_name;
//             }
//             if(isset($top_adx[$publisher_name]['gross'])){
//             $top_adx[$publisher_name]['gross'] += ($gross_mcm);
//             }
//             if(isset($top_adx[$publisher_name]['net'])){
//             $top_adx[$publisher_name]['net'] += ($net_mcm);
//             }
//         }
       
//         return $top_adx;
//     }
    // elseif($accounts=="DFP_directdeal"){

    // $query_pubId = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=5';
   
    //     #prepare query
    //     $rows = $this->conn->prepare($query_pubId);
    //     #execute query 
    //     $rows->execute();
    //     //return $row;
    //     $stmt_result = $rows->get_result();
    //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);
    // foreach($row_result as $value_pubId) {
    //     $publisher_name = $value_pubId['name'];
    //     $uniqid = $value_pubId['pub_uniq_id'];
    //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //             $afc_share = $value_pubId['pub_display_share'];
    //         }
    //         else{
    //             $afc_share = 15;
    //         }
    //         // $afc_share = 15;
    //     #Directdeal
    //     $command_directdeal_gross = new MongoDB\Driver\Command([
    //         'aggregate' => 'DFP_directdeal',
    //         'pipeline' => [
    //             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$date'
    //                 ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
    //     $top_adx = array();
    //     // $top_adx[$uniqid]['name']=$publisher_name;
    //     // echo $top_adx[$uniqid]['name'];
    //     foreach($directdeal_gross as $value_direct) {
    //         $gross_direct += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
            
    //         // publisher WISE CODE
    //         // $top_adx[$uniqid]['name']=$publisher_name;
    //         $top_adx[$uniqid]['gross'] += round(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
    //         $top_adx[$uniqid]['net'] += round((($value_direct->direct_impression*$value_direct->newecpm)/1000)*$afc_share/100,2);
    //     }
    //   }
    //     return $top_adx;

    // }
    //     // #HeaderBidder
    // elseif($accounts=="header_bidder"){
    //         $query_pubId = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=5';
    //     #prepare query
    //     $rows = $this->conn->prepare($query_pubId);
    //     #execute query 
    //     $rows->execute();
    //     //return $row;
    //     $stmt_result = $rows->get_result();
    //     $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    //     foreach($row_result as $value_pubId) {
    //     $publisher_name = $value_pubId['name'];
    //     $uniqid = $value_pubId['pub_uniq_id'];
    //         if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
    //             $afc_share = $value_pubId['pub_display_share'];
    //         }
    //         else{
    //             $afc_share = 15;
    //         }
    //     $command_HB = new MongoDB\Driver\Command([
    //         'aggregate' => 'header_bidder',
    //         'pipeline' => [
    //             ['$match'=>['DATE'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
    //             ['$group' => ['_id' => [
    //                     'date' => '$DATE'
    //                 ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
    //             ['$sort'=>['_id'=>-1]]
    //         ],
    //         'cursor' => new stdClass,
    //     ]);
    //     $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
    //     // echo "<pre>";print_r($HB);die;
    //     $top_adx=array();
    //     foreach($HB as $value_hb) {
    //         $gross_hb += round($value_hb->adserver_revenue,2);
    //         $net_hb += round(($value_hb->adserver_revenue*$afc_share/100),2);
    
    //         // publisher WISE CODE
    //         $top_adx[$publisher_name]['gross'] += round($value_hb->adserver_revenue,2);
    //         $top_adx[$publisher_name]['net'] += round(($value_hb->adserver_revenue*$afc_share/100),2);
    //     }
    // }
        
    //         return $top_adx;
    // }
 
// }


// public function array_sort($array, $on, $order=SORT_DESC){

//     $new_array = array();
//     $sortable_array = array();

//     if (count($array) > 0) {
//         foreach ($array as $k => $v) {
//             if (is_array($v)) {
//                 foreach ($v as $k2 => $v2) {
//                     if ($k2 == $on) {
//                         $sortable_array[$k] = $v2;
//                     }
//                 }
//             } else {
//                 $sortable_array[$k] = $v;
//             }
//         }

//         switch ($order) {
//             case SORT_ASC:
//                 asort($sortable_array);
//                 break;
//             case SORT_DESC:
//                 arsort($sortable_array);
//                 break;
//         }

//         foreach ($sortable_array as $k => $v) {
//             $new_array[$k] = $array[$k];
//         }
//     }

//     return $new_array;
// }
public function toppatti(){
    #MCM
    $current_year = date('Y');
    $curMonth = date("m", time());
    $curQuarter = ceil($curMonth/3);
    if($curQuarter==1){
    $start_date = ($current_year.'-04-01');
    $end_date = ($current_year.'-06-30');
    }
    elseif($curQuarter==2){
        $start_date = ($current_year.'-07-01');
        $end_date = ($current_year.'-09-30');
    }
    elseif($curQuarter==3){
        $start_date = ($current_year.'-10-01');
        $end_date = ($current_year.'-12-31');
    }
    elseif($curQuarter==4){
        $start_date = ($current_year.'-01-01');
        $end_date = ($current_year.'-03-31');
    }
    $query_mcm = "SELECT bdr.child_net_code,ussd.adx_p_name, usd.chan_name, psm.manager_id, psm.sal_id, ROUND(bdr.revenue,2) as gross_adx_mcm, bdr.net as net_adx_mcm FROM ( SELECT mcru.child_net_code, SUM(mcru.mcm_cnc_revenue) as revenue, ROUND(((SUM(mcru.mcm_cnc_revenue)*15)/100),2) as net FROM `mcm_childnetcode_report` as mcru where mcru.date between '".$start_date."' and '".$end_date."' GROUP BY mcru.child_net_code ) bdr INNER JOIN ( select pm.pub_uniq_id, pm.child_net_code, pm.manager_id,pm.sal_id,pm.channel_id, CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as adx_p_name from publisher_master as pm ) psm ON psm.child_net_code = bdr.child_net_code INNER JOIN ( select ud.channel_id,ud.chan_name from sales_channel as ud ) usd ON usd.channel_id = psm.channel_id INNER JOIN ( select usd.uniq_id,CONCAT(IFNULL(usd.f_name, ''),' ',IFNULL(usd.l_name, '')) as adx_p_name from users as usd ) ussd ON ussd.uniq_id = psm.pub_uniq_id  WHERE psm.child_net_code!='' and psm.sal_id=".$this->sales_id."";
    // echo  $query_mcm;die;
	#prepare query
    $row1 = $this->conn->prepare($query_mcm);
    #execute query 
    $row1->execute();
    $stmt_mcm = $row1->get_result();
    $rowMcm = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    $gross_adx_mcm =0;
    $net_adx_mcm =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adsense =0;
    $gross_direct =0;

    foreach ($rowMcm as $value_total) {
    if(isset($value_total['gross_adx_mcm']) && !empty($value_total['gross_adx_mcm'])){
        $gross_adx_mcm += $value_total['gross_adx_mcm'];
    }
    if(isset($value_total['net_adx_mcm']) && !empty($value_total['net_adx_mcm'])){
        $net_adx_mcm += $value_total['net_adx_mcm'];
    }
    }
    
    $gross_adx = $gross_adx_mcm;
    $net_adx = $net_adx_mcm;
        $query_pubId = 'SELECT pm.pub_uniq_id,pm.sal_id, ps.service_id, sc.chan_name as channel_name, pm.pub_display_share FROM '.$this->table_pub.' as pm JOIN sales_channel as sc ON pm.channel_id=sc.channel_id JOIN publisher_services as ps ON ps.uniq_id=pm.pub_uniq_id WHERE ps.service_id=4 OR ps.service_id=5';
        #prepare query
        $rows = $this->conn->prepare($query_pubId);
        #execute query 
        $rows->execute();
        //return $row;
        $stmt_result = $rows->get_result();
        $row_result = $stmt_result->fetch_all(MYSQLI_ASSOC);

    foreach($row_result as $value_pubId) {

        $uniqid = $value_pubId['pub_uniq_id'];

            if($value_pubId['pub_display_share']!="" && $value_pubId['pub_display_share']!=0) {
                $afc_share = $value_pubId['pub_display_share'];
            }
            else{
                $afc_share = 15;
            }
            // $afc_share = 15;
        #Directdeal
        $command_directdeal_gross = new MongoDB\Driver\Command([
            'aggregate' => 'DFP_directdeal',
            'pipeline' => [
                ['$match'=>['date'=>['$gte' =>$start_date,'$lte' =>$end_date,]]],
                ['$group' => ['_id' => [
                        'date' => '$date'
                    ], 'direct_impression' => ['$sum' => '$direct_impression'], 'newecpm' =>['$sum' => '$ecpm_set_acc_mgr']]],
                ['$sort'=>['_id'=>-1]]
            ],
            'cursor' => new stdClass,
        ]);
        $directdeal_gross = $this->connMongoDb->executeCommand($uniqid,$command_directdeal_gross);
        foreach($directdeal_gross as $value_direct) {
        $gross_direct += number_format(($value_direct->direct_impression*$value_direct->newecpm)/1000,2);
        $net_direct += number_format((($value_direct->direct_impression*$value_direct->newecpm)*$afc_share/1000),2);
        }

        #HeaderBidder
        $command_HB = new MongoDB\Driver\Command([
            'aggregate' => 'header_bidder',
            'pipeline' => [
                ['$match'=>['DATE'=>['$gte' =>$start_date,'$lte' =>$end_date,]]],
                ['$group' => ['_id' => [
                        'date' => '$DATE'
                    ], 'adserver_revenue' => ['$sum' => '$AD_SERVER_CPM_AND_CPC_REVENUE'],]],
                ['$sort'=>['_id'=>-1]]
            ],
            'cursor' => new stdClass,
        ]);
        $HB = $this->connMongoDb->executeCommand($uniqid,$command_HB);
        foreach($HB as $value_hb) {
        $gross_hb += number_format($value_hb->adserver_revenue,2);
        $net_hb += number_format(($value_hb->adserver_revenue*$afc_share/100),2);
        }

    }

        #Adsense
        // $command_adsense_gross = new MongoDB\Driver\Command([
        //     'aggregate' => 'adsense_daywise',
        //     'pipeline' => [
        //         ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate]]],
        //         ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
        //     ],
        //     'cursor' => new stdClass,
        // ]);
        // $adsense_gross = $this->connMongoDb->executeCommand('adsense_db',$command_adsense_gross);
        // foreach($adsense_gross as $value_adsense) {
        // $gross_adsense += $value_adsense->gross_adsense;
        // }

    // $total_gross = $gross_adx+$gross_hb+$gross_adsense+$gross_direct;
    // $total_net = $net_adx+$net_hb+$gross_adsense+$gross_direct;
    $total_gross = $gross_adx+$gross_hb+$gross_direct;
    $total_net = $net_adx+$net_hb+$net_direct;

    #Net % breakup
    $net_percent_adx = (($net_adx/$total_net)*100);
    // $net_percent_adsense = (($gross_adsense/$total_net)*100);
    $net_percent_hb = (($net_hb/$total_net)*100);
    $net_percent_direct = (($net_direct/$total_net)*100);

    // $total_netPer = $net_percent_adx+$net_percent_adsense+$net_percent_hb+$net_percent_direct;
    $total_netPer = $net_percent_adx+$net_percent_hb+$net_percent_direct;
    $qtdtopline=$gross_adx+$gross_hb+$gross_direct;
    $qtdbottomline=$net_adx+$net_hb+$net_direct;
    $query_onboard = "SELECT COUNT(created_at) as onboarding from publisher_master WHERE created_at between '".$start_date."' and '".$end_date."' and sal_id=".$this->sales_id."";
    // echo  $query_onboard;die;
	#prepare query
    $rowon = $this->conn->prepare($query_onboard);
    #execute query 
    $rowon->execute();
    $stmt_mcmon = $rowon->get_result();
   
    $rowMcmon = $stmt_mcmon->fetch_all(MYSQLI_ASSOC);
    return json_encode(array(
        'QTD Top Line'=>number_format($qtdtopline, 2),
        'QTD Bottom Line'=>number_format($qtdbottomline, 2),
        'QTD display/app/video'=>number_format($gross_adx, 2),
        'QTD Onboardings'=>$rowMcmon,
        // 'QTD display/app/video'=>number_format($gross_adx, 2),
        // 'Adsense'=>array('Topline'=>number_format($gross_adsense, 2), 'Bottomline'=>number_format($gross_adsense, 2), 'NetBreakup'=>number_format($net_percent_adsense, 2)),
        // 'DirectDeal'=>array('Topline'=>number_format($gross_direct, 2), 'Bottomline'=>number_format($net_direct, 2), 'NetBreakup'=>number_format($net_percent_direct, 2)),
        // 'Total'=>array('Topline'=>number_format($total_gross, 2), 'Bottomline'=>number_format($total_net, 2), 'NetBreakup'=>number_format($total_netPer, 2))                  
    ));

}
public function toppattisale(){
    $query_onboard = "SELECT sal_name from sales_team where sal_id=".$this->sales_id."";
	#prepare query
    $rowon = $this->conn->prepare($query_onboard);
    #execute query 
    $rowon->execute();
    return $rowon;

}




//   public function mailPub($html,$subject,$email){
    
//     include_once('../mailerLib/class.phpmailer.php');
//     $body_bank = $html;
//             $mail  = new PHPMailer();
//             $mail->IsSMTP();  
//             $mail->Host       = "103.76.212.101";
//             $mail->SMTPDebug  = 1; 
//             $mail->SMTPAuth   = true;   
//             $mail->Username   = 'noreply@cybermedia.co.in';
//             $mail->Password   = 'K6Cx*5G%W8j';
//             $mail->Port = "587";
//             $mail->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
//             $mail->Subject = $subject;           
            
//             $mail->addAddress('srishtis@cybermedia.co.in');
//             $mail->isHTML(true);
//             $mail->Body = $body_bank;
//             if($mail->Send()){
              
//                 return true;
//              }else{
//                 return false;
//              }
//   }
}
?>