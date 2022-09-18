<?php
#Author BY SS
class BackendAdmin{
#database connection and table name
private $conn;
public $connMongoDb;
private $table_name1 = "adx_standard_report_notagwise";
private $table_name2 = "adx_standardapp_report";
private $table_name3 = "adx_vedio_overview";
private $table_pub = "publisher_master_old";
private $table_pub1 = "publisher_master";
private $table_user_details = "user_details";
private $table_user = "users";
private $table_acc_mgr = "account_manager";
#object properties
public $strtdate;
public $enddate; 
public  $fetched_team_name;
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
#constructor with $db as database connection
public function __construct($db,$connMongoDb,$strtdate,$enddate){
    $this->conn = $db;
    $this->connMongoDb = $connMongoDb;
    $this->strtdate = $strtdate;
    $this->enddate = $enddate;
}
#Total Handling Accounts
public function TotalAccounts(){
    #Cyberads Account
    $query_cyberads = 'SELECT COUNT(pm.pub_uniq_id) as CyberAds FROM '.$this->table_pub.' as pm JOIN '.$this->table_user_details.' as ud ON (pm.pub_id=ud.id)  WHERE pm.ca_pro_flag = 1 AND ud.user_flag=1';
    #prepare query
    $rowads = $this->conn->prepare($query_cyberads);
    #execute query 
    $rowads->execute();
    $stmt_resultads = $rowads->get_result();
    $row_resultads = $stmt_resultads->fetch_array(MYSQLI_ASSOC);
    #Adx Account
    $query_adx = 'SELECT COUNT(pm.pub_adx_partner_id) as Adx FROM '.$this->table_pub.' as pm JOIN '.$this->table_user_details.' as ud ON (pm.pub_id=ud.id)  WHERE pm.ca_pro_flag = 0 AND ud.user_flag=1 AND pm.pub_adx_partner_id!=""';       
    #prepare query
    $rowsadx = $this->conn->prepare($query_adx);
    #execute query 
    $rowsadx->execute();
    $stmt_resultadx = $rowsadx->get_result();
    $row_resultadx = $stmt_resultadx->fetch_array(MYSQLI_ASSOC);
    #Adsense Account
    $query_adsense = 'SELECT COUNT(pm.pub_adsense_partner_id) as Adsense FROM '.$this->table_pub.' as pm  JOIN '.$this->table_user_details.' as ud ON (pm.pub_id=ud.id)  WHERE pm.ca_pro_flag = 0 AND ud.user_flag=1 AND pm.pub_adsense_partner_id!=""';       
    #prepare query
    $rowsadsense = $this->conn->prepare($query_adsense);
    #execute query 
    $rowsadsense->execute();
    $stmt_resultadsense = $rowsadsense->get_result();
    $row_resultadsense = $stmt_resultadsense->fetch_array(MYSQLI_ASSOC);
    $total = $row_resultadx['Adx']+$row_resultads['CyberAds']+$row_resultadsense['Adsense'];
    return json_encode(array(
        'Adx Acc'=>array($row_resultadx['Adx']),
        'CyberAds Acc'=>array($row_resultads['CyberAds']),
        'Adsense Acc'=>array($row_resultadsense['Adsense']),
        'Total Acc'=>array($total),          
    ));
}
#Active Account Manager Count
public function Activeaccmanager(){
    $query_active= 'SELECT COUNT(manager_id) as no_acc_manager FROM '.$this->table_acc_mgr.' WHERE manager_status="Y"';
    #prepare query
    $active_acc_manager = $this->conn->prepare($query_active);
    #execute query 
    $active_acc_manager->execute();
    return $active_acc_manager;
}
#Overall Performance
public function Overallperformance(){
     #Display
     $queryDisplay='SELECT SUM(adx.adx_earnings) AS gross_adx_display, SUM(adx.adx_adimpr) AS imp_adx_display FROM '.$this->table_name1.' adx WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
    #prepare query
     $row1 = $this->conn->prepare($queryDisplay);
     #execute query 
     $row1->execute();
     $stmt_disp = $row1->get_result();
     $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
     #App
     $queryApp='SELECT  SUM(adx.adx_earnings) AS gross_adx_app, SUM(adx.adx_adimpr) AS imp_adx_app FROM '.$this->table_name2.' adx  WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
     #prepare query
     $row2 = $this->conn->prepare($queryApp);
     #execute query 
     $row2->execute();
     $stmt_app = $row2->get_result();
     $rowApp = $stmt_app->fetch_all(MYSQLI_ASSOC);
     #Video
     $queryVideo='SELECT SUM(adx.revenue) AS gross_adx_video, SUM(adx.ad_impressions) AS imp_adx_video FROM '.$this->table_name3.' adx WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
     #prepare query
     $row3 = $this->conn->prepare($queryVideo);
     #execute query 
     $row3->execute();
     $stmt_video = $row3->get_result();
     $rowVideo = $stmt_video->fetch_all(MYSQLI_ASSOC);
     $result_total = array_merge($rowDisp,$rowApp,$rowVideo);
 
     $gross_adx_display =0;
     $net_adx_display =0;
     $gross_hb = 0;
     $net_hb = 0;
     $gross_adx_app =0;
     $net_adx_app =0;
     $gross_adx_video =0;
     $net_adx_video =0;
     $gross_adsense =0;
     $gross_direct =0;
     $fetched_team_name=0;
     $total_net_salerep=0;
     foreach ($result_total as $value_total) {
        if(isset($value_total['gross_adx_display']) && !empty($value_total['gross_adx_display'])){
            $gross_adx_display += $value_total['gross_adx_display'];
        }
        if(isset($value_total['imp_adx_display']) && !empty($value_total['imp_adx_display'])){
            $imp_adx_display += $value_total['imp_adx_display'];
        }
        if(isset($value_total['gross_adx_app']) && !empty($value_total['gross_adx_app'])){
            $gross_adx_app += $value_total['gross_adx_app'];
        }
        if(isset($value_total['imp_adx_app']) && !empty($value_total['imp_adx_app'])){
        $imp_adx_app += $value_total['imp_adx_app'];
        }
        if(isset($value_total['gross_adx_video']) && !empty($value_total['gross_adx_video'])){
        $gross_adx_video += $value_total['gross_adx_video'];
        }
        if(isset($value_total['imp_adx_video']) && !empty($value_total['imp_adx_video'])){
        $imp_adx_video += $value_total['imp_adx_video'];
        }
        }
        //Total Adx Revenue
        $total_adx_revenue = round($gross_adx_display+$gross_adx_app+$gross_adx_video,2);
        $imp_adx = round($imp_adx_display+$imp_adx_app+$imp_adx_video,2);
        //Total Adx ECPM
        $total_adx_ecpm = number_format((($total_adx_revenue/$imp_adx)*1000),2);
        //Total AdSense Revenue
    $command_adsense_gross = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_daywise',
        'pipeline' => [
        ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate,]]],
        ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
        ],
        'cursor' => new stdClass,
    ]);
    
    $adsense_gross = $this->connMongoDb->executeCommand("adsense_db",$command_adsense_gross);
    foreach($adsense_gross as $value_adsense) { 
        $gross_adsense += ($value_adsense->gross_adsense);
    
    }
    //Total AdSense Impressions
    $command_adsense_gross_imp = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_daywise',
        'pipeline' => [
        ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate,]]],
        ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense_imp' => ['$sum' => '$impressions']]],
        ],
        'cursor' => new stdClass,
    ]);
    
    $adsense_gross_imp = $this->connMongoDb->executeCommand("adsense_db",$command_adsense_gross_imp);
    
   
    foreach($adsense_gross_imp as $value_adsense_imp) { 
        $gross_adsense_imp += ($value_adsense_imp->gross_adsense_imp);
    
    }
    //Total AdSense ECPM
    $total_adsense_ecpm = number_format((($gross_adsense/$gross_adsense_imp)*1000),2);
    return json_encode(array(
        'Total Adx Revenue'=>array(number_format($total_adx_revenue)), 
        'Total Adx ECPM'=>array($total_adx_ecpm),  
        'Total Adsense Revenue'=>array(number_format($gross_adsense)), 
        'Total Adsense ECPM'=>array($total_adsense_ecpm),
    ));
}
#Overall Performance
public function Overallperformance7days(){
    #Display
    $queryDisplay='SELECT SUM(adx.adx_earnings) AS gross_adx_display, SUM(adx.adx_adimpr) AS imp_adx_display FROM '.$this->table_name1.' adx JOIN '.$this->table_pub.' p ON adx.adx_p_name = p.pub_acc_name WHERE date > DATE( NOW( ) ) - INTERVAL 7 DAY';
    // echo $queryDisplay;die;
   #prepare query
    $row1 = $this->conn->prepare($queryDisplay);
    #execute query 
    $row1->execute();
    $stmt_disp = $row1->get_result();
    $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    #App
    $queryApp='SELECT  SUM(adx.adx_earnings) AS gross_adx_app, SUM(adx.adx_adimpr) AS imp_adx_app FROM '.$this->table_name2.' adx JOIN '.$this->table_pub.' p ON adx.adx_p_name = p.pub_acc_name WHERE date > DATE( NOW( ) ) - INTERVAL 7 DAY';
    #prepare query
    $row2 = $this->conn->prepare($queryApp);
    #execute query 
    $row2->execute();
    $stmt_app = $row2->get_result();
    $rowApp = $stmt_app->fetch_all(MYSQLI_ASSOC);
    #Video
    $queryVideo='SELECT SUM(adx.revenue) AS gross_adx_video, SUM(adx.ad_impressions) AS imp_adx_video FROM '.$this->table_name3.' adx JOIN '.$this->table_pub.' p ON adx.adx_p_name = p.pub_acc_name WHERE date > DATE( NOW( ) ) - INTERVAL 7 DAY';
    #prepare query
    $row3 = $this->conn->prepare($queryVideo);
    #execute query 
    $row3->execute();
    $stmt_video = $row3->get_result();
    $rowVideo = $stmt_video->fetch_all(MYSQLI_ASSOC);
    $result_total = array_merge($rowDisp,$rowApp,$rowVideo);

    $gross_adx_display =0;
    $net_adx_display =0;
    $gross_hb = 0;
    $net_hb = 0;
    $gross_adx_app =0;
    $net_adx_app =0;
    $gross_adx_video =0;
    $net_adx_video =0;
    $gross_adsense =0;
    $gross_direct =0;
    $fetched_team_name=0;
    $total_net_salerep=0;
    foreach ($result_total as $value_total) {
       if(isset($value_total['gross_adx_display']) && !empty($value_total['gross_adx_display'])){
           $gross_adx_display += $value_total['gross_adx_display'];
       }
       if(isset($value_total['imp_adx_display']) && !empty($value_total['imp_adx_display'])){
           $imp_adx_display += $value_total['imp_adx_display'];
       }
       if(isset($value_total['gross_adx_app']) && !empty($value_total['gross_adx_app'])){
           $gross_adx_app += $value_total['gross_adx_app'];
       }
       if(isset($value_total['imp_adx_app']) && !empty($value_total['imp_adx_app'])){
       $imp_adx_app += $value_total['imp_adx_app'];
       }
       if(isset($value_total['gross_adx_video']) && !empty($value_total['gross_adx_video'])){
       $gross_adx_video += $value_total['gross_adx_video'];
       }
       if(isset($value_total['imp_adx_video']) && !empty($value_total['imp_adx_video'])){
       $imp_adx_video += $value_total['imp_adx_video'];
       }
       }
       //Total Adx Revenue
       $total_adx_revenue = round($gross_adx_display+$gross_adx_app+$gross_adx_video,2);
       $imp_adx = round($imp_adx_display+$imp_adx_app+$imp_adx_video,2);
       //Total Adx ECPM
       $total_adx_ecpm = number_format((($total_adx_revenue/$imp_adx)*1000),2);
       //Total AdSense Revenue
   $command_adsense_gross = new MongoDB\Driver\Command([
       'aggregate' => 'adsense_daywise',
       'pipeline' => [
       ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate,]]],
       ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense' => ['$sum' => '$earnings']]],
       ],
       'cursor' => new stdClass,
   ]);
   
   $adsense_gross = $this->connMongoDb->executeCommand("adsense_db",$command_adsense_gross);
   foreach($adsense_gross as $value_adsense) { 
       $gross_adsense += ($value_adsense->gross_adsense);
   
   }
   //Total AdSense Impressions
   $command_adsense_gross_imp = new MongoDB\Driver\Command([
       'aggregate' => 'adsense_daywise',
       'pipeline' => [
       ['$match'=>['date'=>['$gte' => $this->strtdate ,'$lte'=> $this->enddate,]]],
       ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'gross_adsense_imp' => ['$sum' => '$impressions']]],
       ],
       'cursor' => new stdClass,
   ]);
   
   $adsense_gross_imp = $this->connMongoDb->executeCommand("adsense_db",$command_adsense_gross_imp);
   
  
   foreach($adsense_gross_imp as $value_adsense_imp) { 
       $gross_adsense_imp += ($value_adsense_imp->gross_adsense_imp);
   
   }
   //Total AdSense ECPM
   $total_adsense_ecpm = number_format((($gross_adsense/$gross_adsense_imp)*1000),2);
   return json_encode(array(
       'Total Adx Revenue'=>array(number_format($total_adx_revenue)), 
       'Total Adx ECPM'=>array($total_adx_ecpm),  
       'Total Adsense Revenue'=>array(number_format($gross_adsense)), 
       'Total Adsense ECPM'=>array($total_adsense_ecpm),
   ));
}
#Top 30 publishers of adx
public function Top30AdxPublisher(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    $Date = "2021-11-05";
    $queryDisplay="SELECT CONCAT(pm.pub_fname,' ',pm.pub_lname) as name, adx.child_net_code, SUM(adx.ad_exch_covg) as disp_coverage,  SUM(adx.ad_request) as disp_adx_request, SUM(adx.ad_exch_impression) as disp_adx_imp, SUM(adx.ad_exch_ecpm) as disp_ecpmx, SUM(adx.ad_exch_revenue) as disp_revenue FROM mcm_ad_exch_report as adx  JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code  ORDER BY CAST(adx.ad_exch_revenue AS DECIMAL(10,2)) DESC";
    // echo $queryDisplay;die;
    #prepare query
    $row1 = $this->conn->prepare($queryDisplay);
    #execute query 
    $row1->execute();
    $stmt_disp = $row1->get_result();
    $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    #App
    $queryApp="SELECT CONCAT(pm.pub_fname,' ',pm.pub_lname) as name,adx.child_net_code, SUM(adx.ad_exch_covg) as app_coverage,  SUM(adx.ad_request) as app_adx_request, SUM(adx.ad_exch_impression) as app_adx_imp, SUM(adx.ad_exch_ecpm) as app_ecpmx, SUM(adx.ad_exch_revenue) as app_revenue FROM mcm_ad_exch_app_report as adx  JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where  adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code  ORDER BY CAST(adx.ad_exch_revenue AS DECIMAL(10,2)) DESC";
    #prepare query
    $row2 = $this->conn->prepare($queryApp);
    #execute query 
    $row2->execute();
    $stmt_app = $row2->get_result();
    $rowApp = $stmt_app->fetch_all(MYSQLI_ASSOC);
   
    #Video
    $queryVideo="SELECT CONCAT(pm.pub_fname,' ',pm.pub_lname) as name, adx.child_net_code, SUM(adx.ad_exch_covg) as video_coverage, SUM(adx.ad_request) as video_adx_request, SUM(adx.ad_exch_impression) as video_adx_imp, SUM(adx.ad_exch_ecpm)as video_ecpmx, SUM(adx.ad_exch_revenue) as video_revenue FROM mcm_ad_exch_video_report as adx JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code ORDER BY CAST(adx.ad_exch_revenue AS DECIMAL(10,2)) DESC";
    #prepare query
    $row3 = $this->conn->prepare($queryVideo);
    #execute query 
    $row3->execute();
    $stmt_video = $row3->get_result();
    $rowVideo = $stmt_video->fetch_all(MYSQLI_ASSOC);
  
    $result_total = array_merge($rowDisp,$rowApp,$rowVideo);
    // print_r($result_total);die;
    $gross_display =0;
    $gross_app =0;
    $gross_video = 0;
    $net_display = 0;
    $net_app =0;
    $net_video =0;
    $gross_adx_video =0;
    $net_adx_video =0;
    $gross_adsense =0;
    $gross_direct =0;
    $top_adx = array();
    foreach ($result_total as $value_total) {
        $publisher_name = $value_total['name'];
            #Gross
            if(isset($value_total['disp_coverage'])){
            $gross_display = $value_total['disp_coverage'];
            }
            if(isset($value_total['app_coverage'])){
            $gross_app = $value_total['app_coverage'];
            }
            if(isset($value_total['video_coverage'])){
            $gross_video = $value_total['video_coverage'];
            }
            #Net
            if(isset($value_total['disp_adx_request'])){
            $net_display = $value_total['disp_adx_request'];
            }
            if(isset($value_total['app_adx_request'])){
            $net_app = $value_total['app_adx_request'];
            }
            if(isset($value_total['video_adx_request'])){
            $net_video = $value_total['video_adx_request'];
            }
            
            #Net
            if(isset($value_total['disp_adx_imp'])){
            $net_display1 = $value_total['disp_adx_imp'];
            }
            if(isset($value_total['app_adx_imp'])){
            $net_app1 = $value_total['app_adx_imp'];
            }
            if(isset($value_total['video_adx_imp'])){
            $net_video1 = $value_total['video_adx_imp'];
            }
            #Net
            if(isset($value_total['disp_ecpmx'])){
            $net_display2 = $value_total['disp_ecpmx'];
            }
            if(isset($value_total['app_ecpmx'])){
            $net_app2 = $value_total['app_ecpmx'];
            }
            if(isset($value_total['video_ecpmx'])){
            $net_video2 = $value_total['video_ecpmx'];
            }
             #Net
             if(isset($value_total['disp_revenue'])){
            $net_display3 = $value_total['disp_revenue'];
            }
            if(isset($value_total['app_revenue'])){
            $net_app3 = $value_total['app_revenue'];
            }
            if(isset($value_total['video_revenue'])){
            $net_video3 = $value_total['video_revenue'];
            }


            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;
            $top_adx[$publisher_name]['net1'] = 0;
            $top_adx[$publisher_name]['net2'] = 0;
            $top_adx[$publisher_name]['net3'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_display+$gross_app+$gross_video);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_display+$net_app+$net_video);
            }
            if(isset($top_adx[$publisher_name]['net1'])){
            $top_adx[$publisher_name]['net1'] += ($net_display1+$net_app1+$net_video1);
            }
            if(isset($top_adx[$publisher_name]['net2'])){
                $top_adx[$publisher_name]['net2'] += ($net_display2+$net_app2+$net_video2);
            }
            if(isset($top_adx[$publisher_name]['net3'])){
                $top_adx[$publisher_name]['net3'] += ($net_display3+$net_app3+$net_video3);
            }
        }

    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top30adxpub = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        if($i<=30){
            $top30adxpub[$i]['Publisher Name'] = $top_key;
            $top30adxpub[$i]['Coverage'] = $top_value['gross']; 
            $top30adxpub[$i]['Ad request'] = $top_value['net']; 
            $top30adxpub[$i]['Ad impression'] = $top_value['net1']; 
            $top30adxpub[$i]['ecpm'] = number_format($top_value['net2'],2); 
            $top30adxpub[$i]['Estimated Revenue'] = number_format($top_value['net3'],2);
        $i++;  
        }
    } 
    $json_response = json_encode($top30adxpub);
    return $json_response;
}
#Top 10 Movers of Adx
public function Top10AdxMovers(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    $Date = "2021-09-08";
    $previousDate= date('Y-m-d', strtotime("-2 day", strtotime($Date)));  
    $CurrentDate= date('Y-m-d', strtotime("-1 day", strtotime($Date)));
    $preQ_disp = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as disp_adimr, SUM(adx.ad_exch_revenue) as disp_revenue, SUM(adx.ad_request) as disp_request,SUM(adx.ad_exch_ecpm) as disp_ecpmx , SUM(adx.ad_exch_covg) as disp_covg FROM mcm_ad_exch_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$previousDate."' GROUP BY adx.child_net_code";
    $preR_disp = $this->conn->prepare($preQ_disp);
    #execute query 
    $preR_disp->execute();
    $stmt_disp = $preR_disp->get_result();
    $rowP_disp = $stmt_disp->fetch_all(MYSQLI_ASSOC);

    $preQ1 = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as app_adimr, SUM(adx.ad_exch_revenue) as app_revenue, SUM(adx.ad_request) as app_request,SUM(adx.ad_exch_ecpm) as app_ecpmx , SUM(adx.ad_exch_covg) as app_covg FROM mcm_ad_exch_app_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$previousDate."' GROUP BY adx.child_net_code";
    $preR1 = $this->conn->prepare($preQ1);
    #execute query 
    $preR1->execute();
    $stmt_disp1 = $preR1->get_result();
    $rowP1 = $stmt_disp1->fetch_all(MYSQLI_ASSOC);

    $preQ2 = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as video_adimr, SUM(adx.ad_exch_revenue) as video_revenue, SUM(adx.ad_request) as video_request,SUM(adx.ad_exch_ecpm) as video_ecpmx , SUM(adx.ad_exch_covg) as video_covg FROM mcm_ad_exch_video_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$previousDate."' GROUP BY adx.child_net_code";
    $preR2 = $this->conn->prepare($preQ2);
    #execute query 
    $preR2->execute();
    $stmt_disp2 = $preR2->get_result();
    $rowP2 = $stmt_disp2->fetch_all(MYSQLI_ASSOC);

    $result_total = array_merge($rowP_disp,$rowP1,$rowP2);
    $top_adx = array();
    foreach ($result_total as $value_total) {
        $publisher_name = $value_total['name'];
            #Gross
            if(isset($value_total['disp_adimr'])){
            $gross_display = $value_total['disp_adimr'];
            }
            if(isset($value_total['app_adimr'])){
            $gross_app = $value_total['app_adimr'];
            }
            if(isset($value_total['video_adimr'])){
            $gross_video = $value_total['video_adimr'];
            }
            #Net
            if(isset($value_total['disp_revenue'])){
            $net_display = $value_total['disp_revenue'];
            }
            if(isset($value_total['app_revenue'])){
            $net_app = $value_total['app_revenue'];
            }
            if(isset($value_total['video_revenue'])){
            $net_video = $value_total['video_revenue'];
            }
            
            #Net
            if(isset($value_total['disp_request'])){
            $net_display1 = $value_total['disp_request'];
            }
            if(isset($value_total['app_request'])){
            $net_app1 = $value_total['app_request'];
            }
            if(isset($value_total['video_request'])){
            $net_video1 = $value_total['video_request'];
            }
            #Net
            if(isset($value_total['disp_ecpmx'])){
            $net_display2 = $value_total['disp_ecpmx'];
            }
            if(isset($value_total['app_ecpmx'])){
            $net_app2 = $value_total['app_ecpmx'];
            }
            if(isset($value_total['video_ecpmx'])){
            $net_video2 = $value_total['video_ecpmx'];
            }
             #Net
             if(isset($value_total['disp_covg'])){
            $net_display3 = $value_total['disp_covg'];
            }
            if(isset($value_total['app_covg'])){
            $net_app3 = $value_total['app_covg'];
            }
            if(isset($value_total['video_covg'])){
            $net_video3 = $value_total['video_covg'];
            }


            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;
            $top_adx[$publisher_name]['net1'] = 0;
            $top_adx[$publisher_name]['net2'] = 0;
            $top_adx[$publisher_name]['net3'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_display+$gross_app+$gross_video);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_display+$net_app+$net_video);
            }
            if(isset($top_adx[$publisher_name]['net1'])){
            $top_adx[$publisher_name]['net1'] += ($net_display1+$net_app1+$net_video1);
            }
            if(isset($top_adx[$publisher_name]['net2'])){
                $top_adx[$publisher_name]['net2'] += ($net_display2+$net_app2+$net_video2);
            }
            if(isset($top_adx[$publisher_name]['net3'])){
                $top_adx[$publisher_name]['net3'] += ($net_display3+$net_app3+$net_video3);
            }
        }

        
    $currQ = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as disp_adimr, SUM(adx.ad_exch_revenue) as disp_revenue, SUM(adx.ad_request) as disp_request,SUM(adx.ad_exch_ecpm) as disp_ecpmx , SUM(adx.ad_exch_covg) as disp_covg FROM mcm_ad_exch_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$CurrentDate."' GROUP BY adx.child_net_code";
    $currR = $this->conn->prepare($currQ);
    #execute query 
    $currR->execute();
    $stmt_disp = $currR->get_result();
    $rowC = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    $currQ1 = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as app_adimr, SUM(adx.ad_exch_revenue) as app_revenue, SUM(adx.ad_request) as app_request,SUM(adx.ad_exch_ecpm) as app_ecpmx , SUM(adx.ad_exch_covg) as app_covg FROM mcm_ad_exch_app_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$CurrentDate."' GROUP BY adx.child_net_code";
    $currR1 = $this->conn->prepare($preQ1);
    #execute query 
    $currR1->execute();
    $stmt_disp1 = $currR1->get_result();
    $rowC1 = $stmt_disp1->fetch_all(MYSQLI_ASSOC);
    $currQ2 = "SELECT CONCAT(pm.pub_first_name,' ',pm.pub_last_name) as name, SUM(adx.ad_exch_impression) as video_adimr, SUM(adx.ad_exch_revenue) as video_revenue, SUM(adx.ad_request) as video_request,SUM(adx.ad_exch_ecpm) as video_ecpmx , SUM(adx.ad_exch_covg) as video_covg FROM mcm_ad_exch_video_report as adx JOIN publisher_master_new as pm ON (adx.child_net_code=pm.childNetworkCode) where adx.ad_exch_date='".$CurrentDate."' GROUP BY adx.child_net_code";
    $currR2 = $this->conn->prepare($currQ2);
    #execute query 
    
    $currR2->execute();
    $stmt_disp2 = $currR2->get_result();
    $rowC2 = $stmt_disp2->fetch_all(MYSQLI_ASSOC);
    $result_total1 = array_merge($rowC,$rowC1,$rowC2);
    $top_adx1 = array();
    foreach ($result_total1 as $value_total1) {
        $publisher_name1 = $value_total1['name'];
            #Gross
            if(isset($value_total1['disp_adimr'])){
            $gross_display4 = $value_total1['disp_adimr'];
            }
            if(isset($value_total1['app_adimr'])){
            $gross_app4 = $value_total1['app_adimr'];
            }
            if(isset($value_total1['video_adimr'])){
            $gross_video4 = $value_total1['video_adimr'];
            }
            #Net
            if(isset($value_total1['disp_revenue'])){
            $net_display5 = $value_total1['disp_revenue'];
            }
            if(isset($value_total1['app_revenue'])){
            $net_app5 = $value_total1['app_revenue'];
            }
            if(isset($value_total1['video_revenue'])){
            $net_video5 = $value_total1['video_revenue'];
            }
            
            #Net
            if(isset($value_total1['disp_request'])){
            $net_display6 = $value_total1['disp_request'];
            }
            if(isset($value_total1['app_request'])){
            $net_app6 = $value_total1['app_request'];
            }
            if(isset($value_total1['video_request'])){
            $net_video6 = $value_total1['video_request'];
            }
            #Net
            if(isset($value_total1['disp_ecpmx'])){
            $net_display7 = $value_total1['disp_ecpmx'];
            }
            if(isset($value_total1['app_ecpmx'])){
            $net_app7 = $value_total1['app_ecpmx'];
            }
            if(isset($value_total1['video_ecpmx'])){
            $net_video7 = $value_total1['video_ecpmx'];
            }
             #Net
             if(isset($value_total1['disp_covg'])){
            $net_display8 = $value_total1['disp_covg'];
            }
            if(isset($value_total1['app_covg'])){
            $net_app8 = $value_total1['app_covg'];
            }
            if(isset($value_total1['video_covg'])){
            $net_video8 = $value_total1['video_covg'];
            }


            $top_adx1[$publisher_name1]['gross1'] = 0;
            $top_adx1[$publisher_name1]['net4'] = 0;
            $top_adx1[$publisher_name1]['net5'] = 0;
            $top_adx1[$publisher_name1]['net6'] = 0;
            $top_adx1[$publisher_name1]['net7'] = 0;
            

            #TOP ADX PUB CODE
            if(isset($top_adx1[$publisher_name1]['gross1'])){
                $top_adx1[$publisher_name1]['gross1'] += ($gross_display4+$gross_app4+$gross_video4);
            }
            if(isset($top_adx1[$publisher_name1]['net4'])){
            $top_adx1[$publisher_name1]['net4'] += ($net_display5+$net_app5+$net_video5);
            }
            if(isset($top_adx1[$publisher_name1]['net5'])){
            $top_adx1[$publisher_name1]['net5'] += ($net_display6+$net_app6+$net_video6);
            }
            if(isset($top_adx1[$publisher_name1]['net6'])){
                $top_adx1[$publisher_name1]['net6'] += ($net_display7+$net_app7+$net_video7);
            }
            if(isset($top_adx1[$publisher_name1]['net7'])){
                $top_adx1[$publisher_name1]['net7'] += ($net_display8+$net_app8+$net_video8);
            }
        }
        

        $arrTop =array(); //impression
        $arrDown =array();

        $arrTopR =array(); //revenue
        $arrDownR =array();
        $arrTopP =array(); //PAGE
        $arrDownP =array();
    foreach ($top_adx as $value) {
	  
        foreach ($top_adx1 as  $val) {
            
            
            if($publisher_name1 == $publisher_name){
                
           if ($value['gross'] > 1) {
            
                   $new = $val['gross1'] - $value['gross'];
                   $newP = $new/ $value['gross']*100;
                   $newRev = $val['net4'] - $value['net'];
                   $newReve = $newRev/$value['net']*100;
                   $newPage = $val['net5'] - $value['net1'];
                   $newPageV = $newPage/$value['net1']*100;
                  
  
          if($new > 0){
              if($new >= 50000){
            $arrTop[$value['name']]['name'] = $publisher_name;
            $arrTop[$value['name']]['Impression'] = number_format($new,2);
            $arrTop[$value['name']]['Imp_vari'] = number_format($newP,2); //percent
            $arrTop[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
            $arrTop[$value['name']]['covg'] = number_format($val['net7'],2); //covg
              }
             }
           else{
            
               if($new <= -50000){
                   
            $arrDown[$value['name']]['name'] = $publisher_name;
            $arrDown[$value['name']]['Impression'] = number_format($new,2);
            $arrDown[$value['name']]['Imp_vari'] = number_format($newP,2); //percent
            $arrDown[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
            $arrDown[$value['name']]['covg'] = number_format($val['net7'],2); //covg
             }
  
           } 
           
           if($newRev > 0){
               if($newRev >= 10){
               $arrTopR[$value['name']]['name'] = $publisher_name;
                $arrTopR[$value['name']]['Revenue'] = number_format($newRev,2);
               $arrTopR[$value['name']]['rev_vari'] = number_format($newReve,2);
               $arrTopR[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
               $arrTopR[$value['name']]['covg'] = number_format($val['net7'],2); //covg
             }
           }else{
               
               if($newRev <= -10){
                $arrDownR[$value['name']]['name'] = $publisher_name;
                $arrDownR[$value['name']]['Revenue'] = number_format($newRev,2);
               $arrDownR[$value['name']]['rev_vari'] = number_format($newReve,2);
               $arrDownR[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
               $arrDownR[$value['name']]['covg'] = number_format($val['net7'],2); //covg
              }
           }          
          if($newPage > 0){
              if($newPage > 10000){
                $arrTopP[$value['name']]['name'] =$publisher_name;
                $arrTopP[$value['name']]['Page'] = number_format($newPage,2);
               $arrTopP[$value['name']]['page_vari'] = number_format($newPageV,2);
                 $arrTopP[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
               $arrTopP[$value['name']]['covg'] = number_format($val['net7'],2); //covg
           }
          }else{
              if ($newPage <= -10000){
               $arrDownP[$value['name']]['name'] = $publisher_name;
                $arrDownP[$value['name']]['Page'] = number_format($newPage,2);
               $arrDownP[$value['name']]['page_vari'] = number_format($newPageV,2);
                 $arrDownP[$value['name']]['eCPM'] = number_format($val['net6'],2); //ecpm
               $arrDownP[$value['name']]['covg'] = number_format($val['net7'],2); //covg
           }
          }  
          } 
            }
        }
  } 
    function sortByOrder($a, $b) {
    return $b['Imp_vari'] - $a['Imp_vari'] ;
    }

    usort($arrTop, 'sortByOrder');

    function sortByOrder1($a, $b) {
        return $a['Imp_vari'] - $b['Imp_vari'] ;
    }
    
    usort($arrDown, 'sortByOrder1');
    //revenue sorting
    function sortByOrderR($a, $b) {
        return $b['rev_vari'] - $a['rev_vari'] ;
    }
    
    usort($arrTopR, 'sortByOrderR');

    function sortByOrderR1($a, $b) {
        return $a['rev_vari'] - $b['rev_vari'] ;
    }
    
    usort($arrDownR, 'sortByOrderR1');

    //page views sorting
    function sortByOrderP($a, $b) {
        return $b['page_vari'] - $a['page_vari'] ;
    }
   
    usort($arrTopP, 'sortByOrderP');
    function sortByOrderP1($a, $b) {
        return $a['page_vari'] - $b['page_vari'] ;
    }
    
    usort($arrDownP, 'sortByOrderP1');

    $newArrayT = array_slice($arrTop, 0, 5, true);
    $newArrayD = array_slice($arrDown, 0, 5, true);

    $newArrayTR = array_slice($arrTopR, 0, 5, true);
    $newArrayDR = array_slice($arrDownR, 0, 5, true);
    
    $newArrayTP = array_slice($arrTopP, 0, 5, true);
    $newArrayDP = array_slice($arrDownP, 0, 5, true);

    $i = 1;	
    $top5impads = array();
    foreach($newArrayT as $dataT){
        if($i<=5){
            $top5impads[$i]['Name'] = ucwords($dataT['name']);
            $top5impads[$i]['Impression'] = $dataT['Impression'];
            $top5impads[$i]['Imp_vari'] = $dataT['Imp_vari'];
            $top5impads[$i]['eCPM'] = $dataT['eCPM'];
            $top5impads[$i]['covg'] = $dataT['covg'];
            $i++;
        }
    }
    $j = 1;	
    $down5impads = array();
    foreach($newArrayD as $dataD){
        if($j>=5){
        $down5impads[$j]['Name'] = ucwords($dataD['name']);
        $down5impads[$j]['Impression'] = $dataD['Impression'];
        $down5impads[$j]['Imp_vari'] = $dataD['Imp_vari'];
        $down5impads[$j]['eCPM'] = $dataD['eCPM'];
        $down5impads[$j]['covg'] = $dataD['covg'];
    $j++;
        }
    }
    $k = 1;	
        $top5revads = array();
        foreach($newArrayTR as $dataTR){
            if($k<=5){
            $top5revads[$k]['Name'] = ucwords($dataTR['name']);
            $top5revads[$k]['Revenue'] = $dataTR['Revenue'];
            $top5revads[$k]['rev_vari'] = $dataTR['rev_vari'];
            $top5revads[$k]['eCPM'] = $dataTR['eCPM'];
            $top5revads[$k]['covg'] = $dataTR['covg'];
            $k++;
            }
        }
    
        $l = 1;	
        $down5revads = array();
        foreach($newArrayDR as $dataDR){
            if($l<=5){
            $down5revads[$l]['Name'] = ucwords($dataDR['name']);
            $down5revads[$l]['Revenue'] = $dataDR['Revenue'];
            $down5revads[$l]['rev_vari'] = $dataDR['rev_vari'];
            $down5revads[$l]['eCPM'] = $dataDR['eCPM'];
            $down5revads[$l]['covg'] = $dataDR['covg'];
            $l++;
            }
        }
       
    $m = 1;	
        $top5pageviews = array();
        foreach($newArrayTP as $dataTP){
            if($m<=5){
            $top5pageviews[$m]['Name'] = ucwords($dataTP['name']);
            $top5pageviews[$m]['Page'] = $dataTP['Page'];
            $top5pageviews[$m]['page_vari'] = $dataTP['page_vari'];
            $top5pageviews[$m]['eCPM'] = $dataTP['eCPM'];
            $top5pageviews[$m]['covg'] = $dataTP['covg'];
            $m++;
            }
        }
        $n = 1;	
            $down5pageviews = array();
            foreach($newArrayDP as $dataDP){
                if($n<=5){
                $down5pageviews[$n]['Name'] = ucwords($dataDP['name']);
                $down5pageviews[$n]['Page'] = $dataDP['Page'];
                $down5pageviews[$n]['page_vari'] = $dataDP['page_vari'];
                $down5pageviews[$n]['eCPM'] = $dataDP['eCPM'];
                $down5pageviews[$n]['covg'] = $dataDP['covg'];
                $n++;
                }
            }
    // movers end
    return json_encode(array(
        'Impression Top'=>array($top5impads),
        'Impression Bottom'=>array($down5impads),
        'Revenue Top'=>array($top5revads),
        'Revenue Bottom'=>array($down5revads),
        'Page Views Top'=>array($top5pageviews),
        'Page Views Bottom'=>array($down5pageviews),           
    ));
}
#Account Manager
public function AccountManager(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    $Date = "2021-09-07";
    #Account
    $query_accmgr = 'SELECT am.acc_mgr_id as acc_mgr_id, am.acc_mgr_name as acc_mgr_name FROM acc_manager as am  WHERE am.acc_mgr_status="Y" AND am.acc_mgr_id="32" AND am.user_flag IS NULL';
    #prepare query
    $row_accmgr = $this->conn->prepare($query_accmgr);
    #execute query 
    $row_accmgr->execute();
    $stmt_result_accmgr = $row_accmgr->get_result();
    $rowMgr = $stmt_result_accmgr->fetch_all(MYSQLI_ASSOC);
    foreach ($rowMgr as $valueMgr) {
    $query = "select SUM(asr.ad_exch_revenue) as earn,pm.pub_email,pm.pub_acc_name,SUM(asr.ad_request) as adx_adreq, SUM(asr.ad_exch_covg) as covg, SUM(asr.ad_exch_impression) as adimr, SUM(asr.ad_exch_ecpm) as ecpmx from mcm_ad_exch_report as asr JOIN publisher_master_new as pm ON asr.child_net_code = pm.childNetworkCode where asr.ad_exch_date ='".$Date."' and pm.acc_mgr_id='32'"; 
    #prepare query
    $rows = $this->conn->prepare($query);
    #execute query 
    $rows->execute();
    $stmt_result = $rows->get_result();  
    $rowT = $stmt_result->fetch_all(MYSQLI_ASSOC);
    if(isset($rowT)){
        foreach($rowT as $dataTD){         
        $t_adr += $dataTD['adx_adreq'];
        $t_adimr 	+= $dataTD['adimr'];
        $t_adrc 	+= $dataTD['covg'];
        $t_ecpmx 	+= $dataTD['ecpmx'];
        $t_revenue 	+= $dataTD['earn'];
        }
        $maxLoop =count($rowT);
        $acname = ucwords($valueMgr['acc_mgr_name']);
        $t_adrv = number_format($t_adr);
		$t_adimrv = number_format($t_adimr);
		$wt = number_format($t_adrc/$maxLoop,2);
		$vt = number_format($t_ecpmx/$maxLoop ,2);
		$mb = number_format($t_revenue,2);

    }
        $mid ="32";
        
        $todayQ = "select SUM(asr.ad_exch_revenue) as earn,pm.pub_email,pm.pub_acc_name,SUM(asr.ad_request) as adx_adreq, SUM(asr.ad_exch_covg) as covg, SUM(asr.ad_exch_impression) as adimr, SUM(asr.ad_exch_ecpm) as ecpmx from mcm_ad_exch_report as asr JOIN publisher_master_new as pm ON asr.child_net_code = pm.childNetworkCode where asr.ad_exch_date ='".$Date."' and pm.acc_mgr_id='".$mid."'";
        $resultT = $this->conn->prepare($todayQ); 
        $resultT->execute(); 
        $stmt_resultv = $resultT->get_result();
        $rowTT = $stmt_resultv->fetch_all(MYSQLI_ASSOC);
        $weekQ = "select sum(asr.ad_exch_revenue) as earn,pm.pub_email,pm.pub_acc_name,sum(asr.ad_request) as req , sum(asr.ad_exch_covg) as covg,  sum(asr.ad_exch_impression) as adimr, sum(asr.ad_exch_ecpm) as ecpmx from mcm_ad_exch_report as asr JOIN publisher_master_new as pm ON asr.child_net_code = pm.childNetworkCode where asr.ad_exch_date > '".$Date."'- INTERVAL 7 DAY and pm.acc_mgr_id='".$mid."'";
        $resultW = $this->conn->prepare($weekQ); 
        $resultW->execute(); 
        $stmt_resultv = $resultW->get_result();
        $rowW = $stmt_resultv->fetch_all(MYSQLI_ASSOC);
        $e=3;
        $f=4;
        $maxLoop =count($rowTT);
        $maxLoopWK =count($rowW);
        $t_adrWK=0;$t_adimrWK=0;$t_adrcWK=0;$t_ecpmxWK=0;$t_revenueWK=0;
        foreach($rowTT as $dataTD){
           $t_adr += $dataTD['adx_adreq'];
           $t_adimr 	+= $dataTD['adimr'];
           $t_adrc 	+= $dataTD['covg'];
           $t_ecpmx 	+= $dataTD['ecpmx'];
           $t_revenue 	+= $dataTD['earn'];
       }
       //last week
        foreach($rowW as $dataWK){
                $t_adrWK += $dataWK['req']/7;
                $t_adimrWK 	+= $dataWK['adimr']/7;
                $t_adrcWK 	+= $dataWK['covg']/7;
                $t_ecpmxWK 	+= $dataWK['ecpmx']/7;
                $t_revenueWK 	+= $dataWK['earn']/7;
            }

          //growth
            $t_adrG = $t_adr - $t_adrWK;  
            $t_adrGR = $t_adrG/$t_adrWK*100; //request
            
            $t_adimrG = $t_adimr - $t_adimrWK;  
            $t_adimrGR = $t_adimrG/$t_adimrWK*100; //impressions

            $t_adrcG = $t_adrc - $t_adrcWK;  
            $t_adrcGR =$t_adrcG/$t_adrcWK*100; //covg

            $t_ecpmxG = $t_ecpmx - $t_ecpmxWK;  
            $t_ecpmxGR =$t_ecpmxG/$t_ecpmxWK*100; //ecpm

            $t_revenueG = $t_revenue - $t_revenueWK;  
            $t_revenueGR =$t_revenueG/$t_revenueWK*100; //revenue
            #Total
    $i = 1;	
    $all = array();
        foreach($rowT as $dataTD){
            $all[$i]['Account Managers'] = ucwords($acname);
            $all[$i]['Ad Request'] = number_format(round($dataTD['adx_adreq']));
			$all[$i]['Ad Impression'] =	number_format(round($dataTD['adimr']));
			$all[$i]['Coverage'] = number_format($dataTD['covg'],2);
            $all[$i]['eCPM'] = number_format($dataTD['ecpmx'],2);
            $all[$i]['Estimated Revenue'] = number_format($dataTD['earn']);
            $i++;
        }
        #Todays so far
        $j = 1;	
        $todaysofar = array();
        $todaysofar[$j]['Account Managers'] = ucwords($acname); 
        $todaysofar[$j]['Ad Request'] = number_format(round($t_adrWK));
        $todaysofar[$j]['Ad Impression'] = number_format(round($t_adimrWK));
        $todaysofar[$j]['Coverage'] = number_format($t_adrcWK/$maxLoopWK,2);
        $todaysofar[$j]['eCPM'] = number_format($t_ecpmxWK/$maxLoopWK ,2);
        $todaysofar[$j]['Estimated Revenue'] = number_format($t_revenueWK,2);
        $i++;
       #Last 7 days average
        $k = 1;	
        $last7days = array();
        foreach($rowW as $dataWK){
            $last7days[$k]['Account Managers'] = ucwords($acname);
            $last7days[$k]['Ad Request'] = number_format(round($dataWK['req']));
            $last7days[$k]['Ad Impression'] = number_format(round($dataWK['adimr']));
            $last7days[$k]['Coverage'] = number_format($dataWK['covg'],2);
            $last7days[$k]['eCPM'] = number_format($dataWK['ecpmx'],2);
            $last7days[$k]['Estimated Revenue'] = number_format($dataWK['earn']);
            $k++;
        }
       #growth
        $l = 1;	
        $growth = array();
        $growth[$l]['Account Managers'] = ucwords($acname);
        $growth[$l]['Ad Request'] = number_format(round($t_adrGR));
		$growth[$l]['Ad Impression'] = number_format(round($t_adimrGR));
		$growth[$l]['Coverage'] = number_format($t_adrcGR/2,2);
        $growth[$l]['eCPM'] = number_format($t_ecpmxGR/2 ,2);
        $growth[$l]['Estimated Revenue'] = number_format($t_revenueGR,2);
        $l++;
       }
        return json_encode(array(
            'Total'=>array($all),
            'Todays so far'=>array($todaysofar),
            'Last 7 Days Average'=>array($last7days),
            'Growth'=>array($growth),          
        ));
}
#Current Month Inactive adx publishers
public function InactiveAdxpub(){
    $i=0; 	$where=''; $trData=[];
    $month = date("m");
    $inactiveQ ="select pm.pub_acc_name as name, pm.pub_org_website as web,DATE_FORMAT(ud.inactive_date_adx,'%Y-%m-%d') as date from publisher_master_old as pm join user_details as ud on ud.email=pm.pub_email where ud.pub_adx_status=0 AND pm.pub_adx_partner_id!='' AND inactive_date_adx IS NOT NULL AND Month(ud.inactive_date_adx)='".$month."'";
    $inAll = $this->conn->prepare($inactiveQ);
    $inAll->execute();
    $stmt_resultadx = $inAll->get_result();
    $rowIN = $stmt_resultadx->fetch_all(MYSQLI_ASSOC);
    foreach($rowIN as $dataIN){
        $getrevenue = 'select this_month, previous_month_last from dashboard_headings where adx_p_name="'.$dataIN['name'].'" and type="content" AND MONTH(date)='.$month.'';
        $revenueIn = $this->conn->prepare($getrevenue);
        $revenueIn->execute();
        $stmt_revenueIn = $revenueIn->get_result();
        $rowRev = $stmt_revenueIn->fetch_all(MYSQLI_ASSOC);

        $getrevenueA = 'select this_month, previous_month_last from dashboard_headings where adx_p_name="'.$dataIN['name'].'" and type="app" AND MONTH(date)='.$month.'';
    
        $revenueInA = $this->conn->prepare($getrevenueA);
        $revenueInA->execute();
        $stmt_revenueInA = $revenueInA->get_result();
        $rowRevA = $stmt_revenueInA->fetch_all(MYSQLI_ASSOC);

         $getrevenueV = 'select this_month, previous_month_last from dashboard_headings where adx_p_name="'.$dataIN['name'].'" and type="video" AND MONTH(date)='.$month.'';
    
        $revenueInV = $this->conn->prepare($getrevenueV);
        $revenueInV->execute();
        $stmt_revenueInV = $revenueInV->get_result();
        $rowRevV = $stmt_revenueInV->fetch_all(MYSQLI_ASSOC);
        $sum_LastmonthA = ($rowRev[0]['this_month']+$rowRevA[0]['this_month']+$rowRevV[0]['this_month']);
        $sum_Last3monthA = ($rowRev[0]['previous_month_last']+$rowRevA[0]['previous_month_last']+$rowRevV[0]['previous_month_last']);
        $trData[$i]['Publisher Name'] = $dataIN['name'];
        $trData[$i]['Web']=$dataIN['web'];
        $trData[$i]['Revenue 30 Days']=$sum_LastmonthA;
        $trData[$i]['Revenue 90 Days']=$sum_Last3monthA;
        $trData[$i]['Date']=$dataIN['date'];
        $i++;
    }
   
    $json_response = json_encode($trData);
    return $json_response;

}
#Top 10 Movers of Adsense
public function Top10adsenseMovers(){
    $pubId ="select pm.	pub_uniq_id as id from publisher_master_old as pm join user_details as ud on ud.email=pm.pub_email where ud.pub_adsense_status=1 AND pm.pub_adsense_partner_id!=''";
    $pubIds = $this->conn->prepare($pubId);
    $pubIds->execute();
    $stmt_resultadsense = $pubIds->get_result();
    $pubIdresult = $stmt_resultadsense->fetch_all(MYSQLI_ASSOC);
    $adsenseCurr= date('Y-m-d', strtotime("-2 day"));  
    $adsensePre= date('Y-m-d', strtotime("-3 day"));
    $resultTop20 = array();
    $resultPre = array();
    foreach ($pubIdresult as $pubVal) {
        $dbId = $pubVal['id'];
        $command_lplist = new MongoDB\Driver\Command([
            'aggregate' => 'adsense_daywise',
            'pipeline' => [
                ['$match'=>['date'=>['$eq' =>$adsenseCurr]]],
                ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_earning' => ['$sum' => '$earnings']]],
            
                    ],
            'cursor' => new stdClass,
        ]);
        
        $cursor_lplist = $this->connMongoDb->executeCommand($dbId,$command_lplist);
        
        foreach ($cursor_lplist as $val) 
            {
        
                
                $resultTop20[]=array(
                'id'=>substr($val->_id->pubid,3),
                'adreq'=>$val->totalad_requests,
                'adimp'=>$val->totalad_imp,
                'admadr'=>$val->totalmatchad_requests,
                'adearn'=>$val->total_earning		
                );
            }
        
            //previous date
        $command_lplist1 = new MongoDB\Driver\Command([
            'aggregate' => 'adsense_daywise',
            'pipeline' => [
                ['$match'=>['date'=>['$eq' =>$adsensePre]]],
                ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_earning' => ['$sum' => '$earnings']]],
                    ],
            'cursor' => new stdClass,
        ]);
        
        $cursor_lplist1 = $this->connMongoDb->executeCommand($dbId,$command_lplist1);
        
        foreach ($cursor_lplist1 as $valPre) 
            {
        
                
                $resultPre[]=array(
                'id'=>substr($valPre->_id->pubid,3),
                'adreq'=>$valPre->totalad_requests,
                'adimp'=>$valPre->totalad_imp,
                'admadr'=>$valPre->totalmatchad_requests,
                'adearn'=>$valPre->total_earning		
                );
            }	
        }
        //AdSense Movers
        $arrTopAdS =array(); //impression
        $arrDownAdS =array();
        $arrTopRAdS =array(); //revenue
        $arrDownRAdS =array();
        $arrTopPAdS =array(); //PAGE
        $arrDownPAdS =array();
        foreach ($resultPre as $valuePre) {
                                  	  
            foreach ($resultTop20 as  $valCurr) {
                                                 
     
               if($valCurr['id'] == $valuePre['id']){
                   
              if ($valuePre['adimp'] > 1) {
                          
                      $newAdS = $valCurr['adimp'] - $valuePre['adimp'];
                      $newPAdS = $newAdS/$valuePre['adimp']*100;
                      $newRevAdS = $valCurr['adearn'] - $valuePre['adearn'];
                      $newReveAdS = $newRevAdS/$valuePre['adearn']*100;
                      $newPageAdS = $valCurr['adreq'] - $valuePre['adreq'];
                      $newPageVAdS = $newPageAdS/$valuePre['adreq']*100;
     
             if($newAdS > 0){
                 if($newAdS >= 5000){
               $arrTopAdS[$valuePre['id']]['id'] = $valuePre['id'];
               $arrTopAdS[$valuePre['id']]['Impression'] = $newAdS;
               $arrTopAdS[$valuePre['id']]['Imp_vari'] = $newPAdS; //percent
               $arrTopAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*1000);//ecpm
               $arrTopAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
                 }
                }
              else{
                  if($newAdS <= -5000){
               $arrDownAdS[$valuePre['id']]['id'] = $valuePre['id'];
               $arrDownAdS[$valuePre['id']]['Impression'] = $newAdS;
               $arrDownAdS[$valuePre['id']]['Imp_vari'] = $newPAdS; //percent
               $arrDownAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*1000); //ecpm
               $arrDownAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
                }
     
              } 
              if($newRevAdS > 0){
                  if($newRevAdS >= 10){
                  $arrTopRAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrTopRAdS[$valuePre['id']]['Revenue'] = $newRevAdS;
                  $arrTopRAdS[$valuePre['id']]['rev_vari'] = $newReveAdS;
                  $arrTopRAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*1000); //ecpm
                  $arrTopRAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
                }
              }else{
                  if($newRevAdS <= -10){
                   $arrDownRAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrDownRAdS[$valuePre['id']]['Revenue'] = $newRevAdS;
                  $arrDownRAdS[$valuePre['id']]['rev_vari'] = $newReveAdS;
                  $arrDownRAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*1000); //ecpm
                  $arrDownRAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
                 }
              } 
             if($newPageAdS > 0){
                 if($newPageAdS > 1000){
                   $arrTopPAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrTopPAdS[$valuePre['id']]['Page'] = $newPageAdS;
                  $arrTopPAdS[$valuePre['id']]['page_vari'] = $newPageVAdS;
                    $arrTopPAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*1000); //ecpm
                  $arrTopPAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
              }
             }else{
                 if ($newPageAdS <= -1000){
                  $arrDownPAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrDownPAdS[$valuePre['id']]['Page'] = $newPageAdS;
                  $arrDownPAdS[$valuePre['id']]['page_vari'] = $newPageVAdS;
                    $arrDownPAdS[$valuePre['id']]['eCPM'] = ($valCurr['adearn']/$valCurr['adimp']*100); //ecpm
                  $arrDownPAdS[$valuePre['id']]['covg'] = ($valCurr['admadr']*100/$valCurr['adreq']); //covg
              }
             }  
             } 
               }
           }
                                            
     
        }  //loop end
        function sortByOrderAds($a, $b) 
        {
        return $b['Imp_vari'] - $a['Imp_vari'] ;
                                            
        }

        usort($arrTopAdS, 'sortByOrderAds');

        function sortByOrder1Ads($a, $b) {
            return $a['Imp_vari'] - $b['Imp_vari'] ;
        }

        usort($arrDownAdS, 'sortByOrder1Ads');
        //revenue sorting
        function sortByOrderRAds($a, $b) {
            return $b['rev_vari'] - $a['rev_vari'] ;
        }

        usort($arrTopRAdS, 'sortByOrderRAds');

        function sortByOrderR1Ads($a, $b) {
            return $a['rev_vari'] - $b['rev_vari'] ;
        }

        usort($arrDownRAdS, 'sortByOrderR1Ads');

        //page views sorting
        function sortByOrderPAds($a, $b) {
            return $b['page_vari'] - $a['page_vari'] ;
        }

        usort($arrTopPAdS, 'sortByOrderPAds');
        function sortByOrderP1Ads($a, $b) {
            return $a['page_vari'] - $b['page_vari'] ;
        }

        usort($arrDownPAdS, 'sortByOrderP1Ads');

        $newArrayTAds = array_slice($arrTopAdS, 0, 5, true); //impression
        $newArrayDAds = array_slice($arrDownAdS, 0, 5, true);

        $newArrayTRAds = array_slice($arrTopRAdS, 0, 5, true);  //revenue
        $newArrayDRAds = array_slice($arrDownRAdS, 0, 5, true);

        $newArrayTPAds = array_slice($arrTopPAdS, 0, 5, true); //page views
        $newArrayDPAds = array_slice($arrDownPAdS, 0, 5, true);
        function array_sort($array, $on, $order=SORT_DESC){

            $new_array = array();
            $sortable_array = array();

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            if ($k2 == $on) {
                                $sortable_array[$k] = $v2;
                            }
                        }
                    } else {
                        $sortable_array[$k] = $v;
                    }
                }

                switch ($order) {
                    case SORT_ASC:
                        asort($sortable_array);
                        break;
                    case SORT_DESC:
                        arsort($sortable_array);
                        break;
                }

                foreach ($sortable_array as $k => $v) {
                    $new_array[$k] = $array[$k];
                }
            }

            return $new_array;
        }
    $resultTop20 =array_sort($resultTop20, 'adearn', SORT_DESC);
    $resultTop20 = array_slice($resultTop20, 0, 30);
    $i = 1;	
    $top5imp = array();
    foreach($newArrayTAds as $dataTAds){
        if($i<=5){
            $top5imp[$i]['Publisher Id'] = ucwords($dataTAds['id']);
            $top5imp[$i]['Impression'] = number_format($dataTAds['Impression']);
            $top5imp[$i]['Variation Impression'] = number_format($dataTAds['Imp_vari'],2);
            $top5imp[$i]['eCPM'] = number_format($dataTAds['eCPM'],2);
            $top5imp[$i]['Coverage'] = number_format($dataTAds['covg'],2);
            $i++;
        }
    }
    $j = 1;	
    $down5imp = array();
    foreach($newArrayDAds as $dataDAds){
        if($j<=5){
            $down5imp[$j]['Publisher Id'] = ucwords($dataDAds['id']);
            $down5imp[$j]['Impression'] = number_format($dataDAds['Impression']);
            $down5imp[$j]['Variation Impression'] = number_format($dataDAds['Imp_vari'],2);
            $down5imp[$j]['eCPM'] = number_format($dataDAds['eCPM'],2);
            $down5imp[$j]['Coverage'] = number_format($dataDAds['covg'],2); 
            $j++;
        }
    }
    $k = 1;	
    $top5rev = array();
    foreach($newArrayTRAds as $dataTRAds){
        if($k<=5){
            $top5rev[$k]['Publisher Id' ]= ucwords($dataTRAds['id']);							
            $top5rev[$k]['Revenue'] = number_format($dataTRAds['Revenue'],2);
            $top5rev[$k]['Variation Revenue'] = number_format($dataTRAds['rev_vari'],2);
            $top5rev[$k]['eCPM'] = number_format($dataTRAds['eCPM'],2); 
            $top5rev[$k]['Coverage'] = number_format($dataTRAds['covg'],2);
            $k++;
        }
    }
    $l = 1;	
    $down5rev = array();
    foreach($newArrayDRAds as $dataDRAds){
        if($l<=5){
            $down5rev[$l]['Publisher Id' ] = ucwords($dataDRAds['id']);						
            $down5rev[$l]['Revenue' ] = number_format($dataDRAds['Revenue'],2);
            $down5rev[$l]['Variation Revenue' ] = number_format($dataDRAds['rev_vari'],2);
            $down5rev[$l]['eCPM' ] = number_format($dataDRAds['eCPM'],2);
            $down5rev[$l]['Coverage' ] = number_format($dataDRAds['covg'],2); 
        $l++;
    }
    }
    $m = 1;	
    $top5pageview = array();
    foreach($newArrayTPAds as $dataTPAds){
        if($m<=5){
            $top5pageview[$m]['Publisher Id' ] = ucwords($dataTPAds['id']);						
            $top5pageview[$m]['Page Views ' ] = number_format($dataTPAds['Page']);
            $top5pageview[$m]['Page Views Variation' ] = number_format($dataTPAds['page_vari'],2); 
            $top5pageview[$m]['eCPM' ] = number_format($dataTPAds['eCPM'],2); 
            $top5pageview[$m]['Coverage' ] = number_format($dataTPAds['covg'],2); 
            $m++;
        }
    }
    $n = 1;	
    $down5pageview = array();
    foreach($newArrayDPAds as $dataDPAds){
        if($n<=5){
            $down5pageview[$n]['Publisher Id'] = ucwords($dataDPAds['id']); 							
            $down5pageview[$n]['Page Views '] = number_format($dataDPAds['Page']);
            $down5pageview[$n]['Page Views Variation'] = number_format($dataDPAds['page_vari'],2);
            $down5pageview[$n]['eCPM'] = number_format($dataDPAds['eCPM'],2); 
            $down5pageview[$n]['Coverage'] = number_format($dataDPAds['covg'],2); 
        $n++;
        }
    }
    return json_encode(array(
        'Impression Top'=>array($top5imp),
        'Impression Down'=>array($down5imp),
        'Revenue Top'=>array($top5rev),
        'Revenue Down'=>array($down5rev),
        'Page Views Top'=>array($top5pageview),
        'Page Views Down'=>array($down5pageview),           
    ));
}
#Top 30 Publishers of Adsense
public function Top30adsensepub(){
    $pubId ="select pm.	pub_uniq_id as id from publisher_master_old as pm join user_details as ud on ud.email=pm.pub_email where ud.pub_adsense_status=1 AND pm.pub_adsense_partner_id!=''";
    $pubIds = $this->conn->prepare($pubId);
    $pubIds->execute();
    $stmt_resultadsense = $pubIds->get_result();
    $pubIdresult = $stmt_resultadsense->fetch_all(MYSQLI_ASSOC);
    $adsenseCurr= date('Y-m-d', strtotime("-2 day"));  
    $resultTop20 = array();
    foreach ($pubIdresult as $pubVal) {
        $dbId = $pubVal['id'];
        $command_lplist = new MongoDB\Driver\Command([
            'aggregate' => 'adsense_daywise',
            'pipeline' => [
                ['$match'=>['date'=>['$eq' =>$adsenseCurr]]],
                ['$group' => ['_id'=>['pubid' => '$ad_client_id'],'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_earning' => ['$sum' => '$earnings']]],
            
                    ],
            'cursor' => new stdClass,
        ]);
        
        $cursor_lplist = $this->connMongoDb->executeCommand($dbId,$command_lplist);
        
        foreach ($cursor_lplist as $val) 
            {
        
                
                $resultTop20[]=array(
                'id'=>substr($val->_id->pubid,3),
                'adreq'=>$val->totalad_requests,
                'adimp'=>$val->totalad_imp,
                'admadr'=>$val->totalmatchad_requests,
                'adearn'=>$val->total_earning		
                );
            }
        }
        function array_sort($array, $on, $order=SORT_DESC){

            $new_array = array();
            $sortable_array = array();

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            if ($k2 == $on) {
                                $sortable_array[$k] = $v2;
                            }
                        }
                    } else {
                        $sortable_array[$k] = $v;
                    }
                }

                switch ($order) {
                    case SORT_ASC:
                        asort($sortable_array);
                        break;
                    case SORT_DESC:
                        arsort($sortable_array);
                        break;
                }

                foreach ($sortable_array as $k => $v) {
                    $new_array[$k] = $array[$k];
                }
            }

            return $new_array;
        }
    $resultTop20 =array_sort($resultTop20, 'adearn', SORT_DESC);
    $resultTop20 = array_slice($resultTop20, 0, 30);
    $i = 1;	
    $top30adense = array();
    foreach($resultTop20 as $dataTop){
        if($i<=30){
            $top30adense[$i]['Publisher Pub Id'] = ucwords($dataTop['id']);
            $top30adense[$i]['Ad Request'] = number_format($dataTop['adreq']);
            $top30adense[$i]['Ad Impression'] = number_format($dataTop['adimp']);
            $top30adense[$i]['Coverage'] = number_format($dataTop['admadr']*100/$dataTop['adreq'],2);
            $top30adense[$i]['eCPM'] = number_format($dataTop['adearn']/$dataTop['adimp']*1000,2);
            $top30adense[$i]['Estimated Revenue'] = number_format($dataTop['adearn'],2);
            $i++;
        }
    }
    $json_response = json_encode($top30adense);
    return $json_response;
}
#API For adx tabular data
public function Adxdata(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    $Date = "2021-11-05";
    $queryDisplay="SELECT CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as name, adx.child_net_code, SUM(adx.ad_exch_covg) as disp_coverage,  SUM(adx.ad_request) as disp_adx_request, SUM(adx.ad_exch_impression) as disp_adx_imp, SUM(adx.ad_exch_ecpm) as disp_ecpmx, SUM(adx.ad_exch_revenue) as disp_revenue, SUM(adx.match_request) as disp_match_request, ROUND((SUM(adx.ad_exch_clicks)/SUM(adx.ad_exch_impression)),2) as disp_CTR FROM mcm_ad_exch_report as adx  JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code  ORDER BY adx.ad_exch_revenue DESC";
//    echo $queryDisplay;die;
    #prepare query
    $row1 = $this->conn->prepare($queryDisplay);
    #execute query 
    $row1->execute();
    $stmt_disp = $row1->get_result();
    $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    echo "<pre>";print_r($rowDisp);die;
    #App
    $queryApp="SELECT CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as name,adx.child_net_code, SUM(adx.ad_exch_covg) as app_coverage,  SUM(adx.ad_request) as app_adx_request, SUM(adx.ad_exch_impression) as app_adx_imp, SUM(adx.ad_exch_ecpm) as app_ecpmx, SUM(adx.ad_exch_revenue) as app_revenue, SUM(adx.match_request) as app_match_request, ROUND((SUM(adx.ad_exch_clicks)/SUM(adx.ad_exch_impression)),2) as app_CTR FROM mcm_ad_exch_app_report as adx  JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where  adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code  ORDER BY adx.ad_exch_revenue DESC";
    
    #prepare query
    $row2 = $this->conn->prepare($queryApp);
    #execute query 
    $row2->execute();
    $stmt_app = $row2->get_result();
    $rowApp = $stmt_app->fetch_all(MYSQLI_ASSOC);
   
    #Video
    $queryVideo="SELECT CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as name, adx.child_net_code, SUM(adx.ad_exch_covg) as video_coverage, SUM(adx.ad_request) as video_adx_request, SUM(adx.ad_exch_impression) as video_adx_imp, SUM(adx.ad_exch_ecpm)as video_ecpmx, SUM(adx.ad_exch_revenue) as video_revenue, SUM(adx.match_request) as video_match_request, ROUND((SUM(adx.ad_exch_clicks)/SUM(adx.ad_exch_impression)),2) as video_CTR FROM mcm_ad_exch_video_report as adx JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code ORDER BY adx.ad_exch_revenue DESC";
    #prepare query
    $row3 = $this->conn->prepare($queryVideo);
    #execute query 
    $row3->execute();
    $stmt_video = $row3->get_result();
    $rowVideo = $stmt_video->fetch_all(MYSQLI_ASSOC);
    $result_total = array_merge($rowDisp,$rowApp,$rowVideo);
   
    // print_r($result_total['disp_match_request']);die;
    $gross_display =0;
    $gross_app =0;
    $gross_video = 0;
    $net_display = 0;
    $net_app =0;
    $net_video =0;
    $gross_adx_video =0;
    $net_adx_video =0;
    $gross_adsense =0;
    $gross_direct =0;
    $net_display1=0;
    $net_video1=0;
    $net_app1=0;
    $net_display2=0;
    $net_video2=0;
    $net_app2=0;
    $net_display3=0;
    $net_video3=0;
    $net_app3=0;
    $net_display4=0;
    $net_video4=0;
    $net_app4=0;
    $net_display5=0;
    $net_video5=0;
    $net_app5=0;
    $top_adx = array();
   
    foreach ($result_total as $value_total) {
        $publisher_name = $value_total['name'];
        
            #Gross
            if(isset($value_total['disp_coverage'])){
            $gross_display = $value_total['disp_coverage'];
            }
            if(isset($value_total['app_coverage'])){
            $gross_app = $value_total['app_coverage'];
            }
            if(isset($value_total['video_coverage'])){
            $gross_video = $value_total['video_coverage'];
            }
           
            // #Net
            if(isset($value_total['disp_adx_request'])){
            $net_display = $value_total['disp_adx_request'];
            }
            if(isset($value_total['app_adx_request'])){
            $net_app = $value_total['app_adx_request'];
            }
            if(isset($value_total['video_adx_request'])){
            $net_video = $value_total['video_adx_request'];
            }
           
            // #Net
            if(isset($value_total['disp_adx_imp'])){
            $net_display1 = $value_total['disp_adx_imp'];
            }
            if(isset($value_total['app_adx_imp'])){
            $net_app1 = $value_total['app_adx_imp'];
            }
            if(isset($value_total['video_adx_imp'])){
            $net_video1 = $value_total['video_adx_imp'];
            }
            // #Net
            if(isset($value_total['disp_ecpmx'])){
            $net_display2 = $value_total['disp_ecpmx'];
            }
            if(isset($value_total['app_ecpmx'])){
            $net_app2 = $value_total['app_ecpmx'];
            }
            if(isset($value_total['video_ecpmx'])){
            $net_video2 = $value_total['video_ecpmx'];
            }
            //  #Net
             if(isset($value_total['disp_revenue'])){
            $net_display3 = $value_total['disp_revenue'];
            }
            if(isset($value_total['app_revenue'])){
            $net_app3 = $value_total['app_revenue'];
            }
            if(isset($value_total['video_revenue'])){
            $net_video3 = $value_total['video_revenue'];
            }
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;
            $top_adx[$publisher_name]['net1'] = 0;
            $top_adx[$publisher_name]['net2'] = 0;
            $top_adx[$publisher_name]['net3'] = 0;
            $top_adx[$publisher_name]['net4'] = 0;
            $top_adx[$publisher_name]['net5'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_display+$gross_app+$gross_video);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_display+$net_app+$net_video);
            }
            if(isset($top_adx[$publisher_name]['net1'])){
            $top_adx[$publisher_name]['net1'] += ($net_display1+$net_app1+$net_video1);
            }
            if(isset($top_adx[$publisher_name]['net2'])){
                $top_adx[$publisher_name]['net2'] += ($net_display2+$net_app2+$net_video2);
            }
            if(isset($top_adx[$publisher_name]['net3'])){
                $top_adx[$publisher_name]['net3'] += ($net_display3+$net_app3+$net_video3);
            }
            if(isset($top_adx[$publisher_name]['net4'])){
                $top_adx[$publisher_name]['net4'] += ($value_total['disp_match_request']+$value_total['app_match_request']+$value_total['video_match_request']);
            }
        
            if(isset($top_adx[$publisher_name]['net5'])){
                $top_adx[$publisher_name]['net5'] += ($value_total['disp_CTR']+$value_total['app_CTR']+$value_total['video_CTR']);
            }
        }
    #Reversing Array Revenue Wise
    arsort($top_adx);
    $i=1;
    $top30adxpub = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        // if($i<=30){
            $top30adxpub[$i]['Publisher Name'] = $top_key;
            $top30adxpub[$i]['Coverage'] = $top_value['gross']; 
            $top30adxpub[$i]['Ad request'] = $top_value['net']; 
            $top30adxpub[$i]['Ad impression'] = $top_value['net1']; 
            $top30adxpub[$i]['ecpm'] = number_format($top_value['net2'],2); 
            $top30adxpub[$i]['Estimated Revenue'] = number_format($top_value['net3'],2);
            $top30adxpub[$i]['Match Request'] = number_format($top_value['net4'],2);
            $top30adxpub[$i]['CTR'] = number_format($top_value['net5'],2);
        $i++;  
        // }
    } 
    $json_response = json_encode($top30adxpub);
    return $json_response;
}
#API for search
public function Searchapi(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    // $Date = "2021-09-01";
    $search = $_GET['search'];
    $jo = $_GET['joo'];
    $where = $_GET['wheree'];
    // $host = $_GET['host'];
    $queryDisplay ='SELECT pm.pub_id,pm.pub_uniq_id, CONCAT(IFNULL(pm.pub_fname, "")," ",IFNULL(pm.pub_lname, "")) as pub_name, pm.pub_acc_name, pm.pub_acc_new_name, pm.adx_partner_id,pm.pub_adsense_id, am.manager_name, pm.pub_email, pb.status_bank,pb.aadhaar_card_file ,pb.pan_card_file,pb.incorp_certificate_fille ,pb.cancel_check_file,pb.gst_certificate,pm.created_at,ud.pub_adx_status,ud.pub_adsense_status,pm.child_net_code,CONCAT(IFNULL(pm.pub_fname, "")," ",IFNULL(pm.pub_lname, "")) as mcm_name from publisher_master as pm LEFT JOIN bank_details as pb ON pm.pub_uniq_id = pb.uniq_id JOIN account_manager as am ON am.manager_id = pm.manager_id JOIN users as ud ON pm.pub_email=ud.email '.$jo.' WHERE '.$where.' AND (pm.pub_email LIKE "%'.$search.'%" OR am.manager_name LIKE "%'.$search.'%" OR pm.pub_acc_name LIKE "%'.$search.'%" OR CONCAT(IFNULL(pm.pub_fname, "")," ",IFNULL(pm.pub_lname, "")) LIKE "%'.$search.'%" OR pm.pub_adsense_id LIKE "%'.$search.'%" OR CONCAT(IFNULL(pm.pub_fname, "")," ",IFNULL(pm.pub_lname, "")) LIKE "%'.$search.'%") GROUP by pm.pub_uniq_id'; 
    // echo  $queryDisplay;die;
    #prepare query
    $row1 = $this->conn->prepare($queryDisplay);
    #execute query 
    $row1->execute();
    $stmt_disp = $row1->get_result();
    $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    // echo "<pre>";
    // print_r($rowDisp);die;
    #App
      #Reversing Array Revenue Wise
    arsort($rowDisp);
    $i=1;
    $top30adxpub = array(); 
    foreach($rowDisp as $top_value) {
        // if($i<=30){
            $top30adxpub[$i]['S No'] = $top_value['pub_id'];
            $top30adxpub[$i]['Network Partner'] = $top_value['pub_name']; 
            $top30adxpub[$i]['AdX Account Name'] = $top_value['pub_name']; 
            $top30adxpub[$i]['Account Manager Name'] = $top_value['manager_name']; 
            $top30adxpub[$i]['Network Partner Email-Id'] = $top_value['pub_email']; 
            $top30adxpub[$i]['Network Partner On board Date'] = $top_value['created_at'];
            $top30adxpub[$i]['Document Status'] = $top_value['pub_adx_status'];
        $i++;  
        // }
    } 
    $json_response = json_encode($top30adxpub);
    return $json_response;
}
public function AdxTab(){
    // $Hour = date('G');
    // if($Hour < 16){
    // $Date= date('Y-m-d', strtotime(' -1 day'));
    // }else{ 

    // $Date= date('Y-m-d');
    // }
    $date = "2021-11-05";
    $date = "ad_exch_date > 2021-11-05";
    $query_adx = 'SELECT a.ad_exch_date as date, SUM(a.ad_request) as adr, SUM(a.active_view_impr) as acview, SUM(a.ad_exch_clicks) as clicks, SUM(a.ad_exch_covg) as adrc, SUM(a.match_request)*100/SUM(a.ad_request) as covg, SUM(a.match_request) as madr, SUM(a.ad_exch_impression) as adimr, SUM(a.ad_exch_revenue)/SUM(a.ad_exch_impression)*1000 as ecpmx, SUM(a.ad_exch_revenue) as revenue FROM mcm_ad_exch_report as a JOIN publisher_master as p ON a.child_net_code = p.child_net_code  WHERE '.$date.' group by date order by date DESC';
    // echo $query_adx;die;
    // echo "<pre>";print_r($query_adx);die;
    // $queryDisplay="SELECT CONCAT(IFNULL(pm.pub_fname, ''),' ',IFNULL(pm.pub_lname, '')) as name, adx.child_net_code, SUM(adx.ad_exch_covg) as disp_coverage,  SUM(adx.ad_request) as disp_adx_request, SUM(adx.ad_exch_impression) as disp_adx_imp, SUM(adx.ad_exch_ecpm) as disp_ecpmx, SUM(adx.ad_exch_revenue) as disp_revenue, SUM(adx.match_request) as disp_match_request, ROUND((SUM(adx.ad_exch_clicks)/SUM(adx.ad_exch_impression)),2) as disp_CTR FROM mcm_ad_exch_report as adx  JOIN publisher_master as pm ON (adx.child_net_code=pm.child_net_code) where adx.ad_exch_date='".$Date."' GROUP BY adx.child_net_code  ORDER BY adx.ad_exch_revenue DESC";
   
    #prepare query
    $row1 = $this->conn->prepare($query_adx);
    #execute query 
    $row1->execute();
    $stmt_disp = $row1->get_result();
    $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    // echo "<pre>";print_r($rowDisp);die;
    $t_rowsR = count($rowDisp); $total_rvenue =0; $total_adr=0; $total_adrc=0; $total_ecpm=0; $total_madr=0; $total_adimr=0; $total_adr=0;
    // print_r($result_total['disp_match_request']);die;
    
    $top_adx = array();
   
    foreach ($rowDisp as $value_total) {
        // echo "<pre>";print_r($value_total);
        $date_a = $value_total['date'];
        
            #Gross
            if(isset($value_total['adrc'])){
            $gross_adrc = $value_total['adrc'];
            }
           
            // #Net
            if(isset($value_total['adr'])){
            $gross_req = $value_total['adr'];
            }
           
            // #Net
            if(isset($value_total['adimr'])){
            $gross_imp = $value_total['adimr'];
            }
            
            // #Net
            if(isset($value_total['ecpmx'])){
            $gross_ecpmx = $value_total['ecpmx'];
            }
           
            //  #Net
             if(isset($value_total['revenue'])){
            $gross_revenue = $value_total['revenue'];
            }
            if(isset($value_total['acview'])){
                $gross_acview = $value_total['acview'];
            }
            if(isset($value_total['clicks'])){
                $gross_clicks = $value_total['clicks'];
            }
            if(isset($value_total['covg'])){
                $gross_covg = $value_total['covg'];
            }
            if(isset($value_total['madr'])){
                $gross_madr = $value_total['madr'];
            }
            
            $top_adx[$date_a]['gross'] = 0;
            $top_adx[$date_a]['net'] = 0;
            $top_adx[$date_a]['net1'] = 0;
            $top_adx[$date_a]['net2'] = 0;
            $top_adx[$date_a]['net3'] = 0;
            $top_adx[$date_a]['net4'] = 0;
            $top_adx[$date_a]['net5'] = 0;
            $top_adx[$date_a]['net6'] = 0;
            $top_adx[$date_a]['net7'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$date_a]['gross'])){
            $top_adx[$date_a]['gross'] += ( $gross_adrc);
            }
            if(isset($top_adx[$date_a]['net'])){
            $top_adx[$date_a]['net'] += ($gross_req);
            }
            if(isset($top_adx[$date_a]['net1'])){
            $top_adx[$date_a]['net1'] += ($gross_imp);
            }
            if(isset($top_adx[$date_a]['net2'])){
                $top_adx[$date_a]['net2'] += ($gross_ecpmx);
            }
            if(isset($top_adx[$date_a]['net3'])){
                $top_adx[$date_a]['net3'] += ($gross_revenue);
            }
            if(isset($top_adx[$date_a]['net4'])){
                $top_adx[$date_a]['net4'] += ($gross_acview);
            }
        
            if(isset($top_adx[$date_a]['net5'])){
                $top_adx[$date_a]['net5'] += ($gross_clicks);
            }
            if(isset($top_adx[$date_a]['net6'])){
                $top_adx[$date_a]['net6'] += ($gross_covg);
            }
            if(isset($top_adx[$date_a]['net7'])){
                $top_adx[$date_a]['net7'] += ($gross_madr);
            }
        }
        
    #Reversing Array Revenue Wise
    // echo "<pre>";print_r($top_adx);die;
    // arsort($top_adx);
    
    $i=1;
    $top30adxpub = array(); 
    foreach($top_adx as $top_key=>$top_value) {
        $total_covg 		+= $top_value['gross'];
        $total_req 	+= $top_value['net'];
        $total_imp	+= $top_value['net1'];
        $total_ecpmx		+= $top_value['net2'];
        $total_revenue	+= $top_value['net3'];
        $total_acview		+= $top_value['net4'];
        $total_clicks	+= $top_value['net5'];
        $total_ctr	+= $top_value['net6'];
        $total_madr	+= $top_value['net7'];

        // if($i<=30){
            $top30adxpub[$i]['date'] = $top_key;
            $top30adxpub[$i]['covg'] = $top_value['gross']; 
            $top30adxpub[$i]['req'] = $top_value['net']; 
            $top30adxpub[$i]['imp'] = $top_value['net1']; 
            $top30adxpub[$i]['ecpmx'] = number_format($top_value['net2'],2); 
            $top30adxpub[$i]['revenue'] = number_format($top_value['net3'],2);
            $top30adxpub[$i]['acview'] = number_format($top_value['net4'],2);
            $top30adxpub[$i]['clicks'] = number_format($top_value['net5'],2);
            $top30adxpub[$i]['ctr'] = number_format($top_value['net6'],2);
            $top30adxpub[$i]['madr'] = number_format($top_value['net7'],2);

            
            
        $i++;  
        // }
    } 

            $top30adxpub1['tot_covg'] = number_format($total_covg,2); 
            $top30adxpub1['tot_req'] = number_format($total_req,2); 
            $top30adxpub1['tot_imp'] = number_format($total_imp,2); 
            $top30adxpub1['tot_ecpmx'] =  number_format($total_ecpmx,2); 
            $top30adxpub1['tot_revenue'] = number_format($total_revenue,2);
            $top30adxpub1['tot_acview'] = number_format($total_acview,2);
            $top30adxpub1['tot_clicks'] = number_format($total_clicks,2);
            $top30adxpub1['tot_ctr'] = number_format($total_ctr,2);
            $top30adxpub1['tot_madr'] = number_format($total_madr,2);
    // $top30adxpub[$i]['adrc'] += $top_value['gross']; 
    // $top30adxpub[$i]['req'] += $top_value['net']; 
    // $top30adxpub[$i]['imp'] += $top_value['net1']; 
    // $top30adxpub[$i]['ecpmx'] += number_format($top_value['net2'],2); 
    // $top30adxpub[$i]['revenue'] += number_format($top_value['net3'],2);
    // $top30adxpub[$i]['acview'] += number_format($top_value['net4'],2);
    // $top30adxpub[$i]['clicks'] += number_format($top_value['net5'],2);
    // $top30adxpub[$i]['covg'] += number_format($top_value['net6'],2);
    // $top30adxpub[$i]['madr'] += number_format($top_value['net7'],2);
    // echo "<pre>";print_r($top30adxpub);die;
    return json_encode(array(
        'Adx'=>array($top30adxpub),
        'Total'=>array($top30adxpub1),
                  
    ));
    // $json_response = json_encode($top30adxpub, $top30adxpub1);
    // return $json_response;
}
// public function AdsenseTab(){
//     $command_lplist = new MongoDB\Driver\Command([
//         'aggregate' => 'adsense_daywise',
//         'pipeline' => [
//             ['$match'=>['date'=>['$gte' =>$this->strtdate,'$lte' =>$this->enddate,]]],
//         ['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]],
//         ['$sort'=>['_id'=>-1]]
//         ],
//         'cursor' => new stdClass,
//     ]);
    
//     $cursor_lplist = $this->connMongoDb->executeCommand($this->uniq_id,$command_lplist);
//     echo "<pre>";print_r($cursor_lplist);die;
//      return $cursor_lplist;
// }
}
?>