<?php
#Author BY SS
class Report{
#database connection and table name
private $conn;
public $connMongoDb;
public $email;
#object properties
public $where;

#constructor with $db as database connection
public function __construct($db,$connMongoDb){
    $this->conn = $db;
    $this->connMongoDb = $connMongoDb;
    $this->strtdate = $strtdate;
    $this->enddate = $enddate;
}
public function sum_params($p=0,$q=0,$r=0,$s=0){
    $counter =0;
    if($p>0){
        $counter++;
    }
    if($q>0){
        $counter++;
    }
    if($r>0){
        $counter++;
    }
    if($s>0){
        $counter++;
    }
    if($counter>0){
        return(($p+$q+$r+$s)/$counter);
    }
    else{
        return 0;
    }
    
}

#overall tab
public function dailyreport(){
    $previous_day = date('Y-m-d',strtotime("-1 days"));
    $p_day =date("d F, Y", strtotime("-1 days"));
    //======================= last month=========================//
    // $start_7_days = date('Y-m-d', strtotime('first day of last month'));
    // $end_7_days = date('Y-m-d', strtotime('last day of last month'));
    //======================= last 7 days=========================//
    $start_7_days = date('Y-m-d', strtotime('-8 days'));
    $end_7_days = date('Y-m-d', strtotime('-2 days'));
    
    $last_month = date("F, Y", strtotime("last month"));
    $count_7_days = 7;
    
    $where = 'ad_exch_date between "'.$start_7_days.'" and "'.$end_7_days.'"';
    
    $alladsense_activePub = "select pub_adsense_id from google_adsense_accounts where status=0 AND  pub_adsense_id!='-'";
    $row1 = $this->conn->prepare($alladsense_activePub);
    #execute query 
    $row1->execute();
    $stmt_mcm = $row1->get_result();
    $rowalladsense_activePub = $stmt_mcm->fetch_all(MYSQLI_ASSOC);
    foreach($rowalladsense_activePub as $adsense_active){
        $adsense_active_ids[] =  $adsense_active['pub_adsense_id'];
    }
    $allPub = "select pm.pub_uniq_id,pm.pub_acc_name,CONCAT(IFNULL(u.f_name, ''),' ',IFNULL(u.l_name, '')) as pub_acc_new_name,pm.child_net_code,ps.service_id,pm.pub_adsense_id, pm.pub_email,pw.web_name,u.f_name, am.manager_name as mngremail,am.manager_id from publisher_master as pm inner join account_manager as am on pm.manager_id = am.manager_id inner join users as u on pm.pub_email = u.email join publishers_website as pw on pw.pub_uniq_id=pm.pub_uniq_id join publisher_services as ps on ps.uniq_id=pm.pub_uniq_id where u.user_flag = 0 AND pm.child_net_code!='' and pm.network_flag!='1' GROUP BY pm.child_net_code ORDER BY pm.child_net_code ASC";
    $resultAll = $this->conn->prepare($allPub);
    #execute query 
    $resultAll->execute();
    $stmt_mcmall = $resultAll->get_result();
    $rowAll = $stmt_mcmall->fetch_all(MYSQLI_ASSOC);
    // echo "<pre>";print_r($rowAll);die;
    if(!empty($rowAll)){
        foreach ($rowAll as $key => $value) {
            // $uniq_id = $value['pub_uniq_id'];
            // $childnetcode = $value['child_net_code'];
            $uniq_id = 'DEEP_090216_153132';
            $childnetcode = '22514901341';
            
            // if($value['pub_acc_new_name'] == NULL || $value['pub_acc_new_name'] ==''){
    
            //     $publisherName = str_replace(" "," ",$value['pub_acc_name']);
            // }else{
            //     $publisherName = str_replace(" "," ",$value['pub_acc_new_name']);
            // }
            $publisherName = 'Deepak Mehra';
            if($value['f_name'] = "Deepak") {
                $name1 = str_replace(".com","",$value['f_name']);
                $name2 = str_replace(".co.in","",$name1);
                $name3 = str_replace(".in","",$name2);
                $name4 = str_replace(".co","",$name3);
                $name5 = str_replace(".org","",$name4);
                $name6 = str_replace(".net","",$name5);
                $name7 = str_replace("https://","",$name6);
                $name8 = str_replace("http://","",$name7);
                $name9 = str_replace("www.","",$name8);
                $name10 = str_replace("www","",$name9);
                $name11 = str_replace("/","",$name10);
                $name12 = str_replace("-","",$name11);
                $name = str_replace("@gmail","",$name12);
            }
            
            else {
                $name1 = str_replace(".com","",$value['web_name']);
                $name2 = str_replace(".co.in","",$name1);
                $name3 = str_replace(".in","",$name2);
                $name4 = str_replace(".co","",$name3);
                $name5 = str_replace(".org","",$name4);
                $name6 = str_replace(".net","",$name5);
                $name7 = str_replace("https://","",$name6);
                $name8 = str_replace("http://","",$name7);
                $name9 = str_replace("www.","",$name8);
                $name10 = str_replace("www","",$name9);
                $name11 = str_replace("/","",$name10);
                $name12 = str_replace("-","",$name11);
                $name = str_replace("@gmail","",$name12);
            }
            $ads_day_adimp = 0;
            $ads_day_cov = 0;
            $ads_day_ecpm = 0;
            $ads_day_rev = 0;
            $ads_month_adimp = 0;
            $ads_month_cov = 0;
            $ads_month_ecpm = 0;
            $ads_month_rev = 0;
            $hb_day_adimp = 0;
            $hb_day_cov = 0;
            $hb_day_ecpm = 0;
            $hb_day_rev = 0;
            $hb_month_adimp = 0;
            $hb_month_cov = 0;
            $hb_month_ecpm = 0;
            $hb_month_rev = 0;
    
    //	Notifications Module
        $where_adsense			= ['date'=>['$gte' => $start_7_days, '$lte' => $end_7_days]];
        $query_adsense = new MongoDB\Driver\Query($where_adsense);
        $result_adsense = $this->connMongoDb->executeQuery($uniq_id.'.adsense_daywise', $query_adsense);
    
        foreach ($result_adsense as $val) 
        {
            $rpmarray[]=array('date'=> $val->date,
            'rpm'=> ($val->earnings/$val->page_views)*1000,
            );
        }
        for($i=1;$i<=7;$i++)
        {
            $finalrpmvalue+=$rpmarray[$i]['rpm'];
        }
            $yesterdayrpm=number_format($rpmarray[0]['rpm'],2);
            $avgrpm=number_format($finalrpmvalue/7,2);
    
    
        $queryforbnkdtl="SELECT child_net_code FROM bank_details where child_net_code='22514901341'";
        $row_bank = $this->conn->prepare($queryforbnkdtl);
        #execute query 
        $row_bank->execute();
        $stmt_mcm_bank = $row_bank->get_result();
        $resultforbnkdtl = $stmt_mcm_bank->fetch_all(MYSQLI_ASSOC);
    //	End of Notifications Module
    if($publisherName!='Nandan Jha' && $publisherName!='AvinashTricks99' && $publisherName!='Prem_glowjobs'){
    //ADX Display data Fetch
    $query_display_Day = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_report WHERE ad_exch_date = '".$previous_day."' and child_net_code = '22514901341'";
    $result_display_Day_today = $this->conn->prepare($query_display_Day);
    #execute query 
    $result_display_Day_today->execute();
    $stmt_disp = $result_display_Day_today->get_result();
    $row_display_Day = $stmt_disp->fetch_all(MYSQLI_ASSOC);
    // echo "<pre>";print_r($row_display_Day);die;
    $query_display_last7days = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_report WHERE ".$where." and child_net_code = '22514901341'";
    $result_display_last7days_7 = $this->conn->prepare($query_display_last7days);
    #execute query 
    $result_display_last7days_7->execute();
    $stmt_disp_7 = $result_display_last7days_7->get_result();
    $row_display_last7days = $stmt_disp_7->fetch_all(MYSQLI_ASSOC);
    // End oF--> ADX Display data Fetch
    
    
    
    //ADX Video data Fetch
    $query_video_Day = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_video_report WHERE ad_exch_date = '".$previous_day."' and child_net_code = '22514901341'";
    // echo  $query_video_Day;die;
    $result_video_Day_today = $this->conn->prepare($query_video_Day);
    #execute query 
    $result_video_Day_today->execute();
    $stmt_video = $result_video_Day_today->get_result();
    $row_video_Day = $stmt_video->fetch_all(MYSQLI_ASSOC);
    
    $query_video_last7days = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_video_report WHERE ".$where." and child_net_code = '22514901341'";
    $result_video_Day_7 = $this->conn->prepare($query_video_last7days);
    #execute query 
    $result_video_Day_7->execute();
    $stmt_video_7 = $result_video_Day_7->get_result();
    $row_video_last7days = $stmt_video_7->fetch_all(MYSQLI_ASSOC);
    
    // End oF--> ADX Video data Fetch
    
    
    
    //ADX App data Fetch
    
    $query_app_Day = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_app_report WHERE ad_exch_date = '".$previous_day."' and child_net_code = '22514901341'";
    $result_app_Day = $this->conn->prepare($query_app_Day);
    #execute query 
    $result_app_Day->execute();
    $stmt_app = $result_app_Day->get_result();
    $row_app_Day = $stmt_app->fetch_all(MYSQLI_ASSOC);
    
    $query_app_last7days = "select SUM(ad_exch_impression) as adimr, SUM(ad_exch_revenue) as earn, SUM(match_request)*100/SUM(ad_request) as covg, SUM(ad_exch_revenue)/SUM(match_request)*1000 as ecpmx, SUM(ad_request) as adreq, SUM(match_request) as matchadreq from mcm_ad_exch_app_report WHERE ".$where." and child_net_code = '22514901341'";
    $result_app_last7days_7 = $this->conn->prepare($query_app_last7days);
    #execute query 
    $result_app_last7days_7->execute();
    $stmt_app_7 = $result_app_last7days_7->get_result();
    $row_app_last7days = $stmt_app_7->fetch_all(MYSQLI_ASSOC);
    
    // End oF--> ADX App data Fetch
    // Share Calculation For Adx Display,video,app
    $q = 'SELECT pub_display_share,pub_app_share,pub_video_share from publisher_master WHERE pub_email="deepak@finder6.com" limit 0,1';
    $q1 = $this->conn->prepare($q);
    #execute query 
    $q1->execute();
    $stmt = $q1->get_result();
    $r1 = $stmt->fetch_all(MYSQLI_ASSOC);
   
    if($r1['pub_display_share'] > 0)	{
        $cms_display_Share = $r1['pub_display_share']/100; 
    }
    else{ 
        $cms_display_Share = 15/100;
    }
    
    if($r1['pub_video_share'] > 0)	{
        $cms_video_Share = $r1['pub_video_share']/100; 
    }
    else{ 
        $cms_video_Share = 15/100;
    }
    
    if($r1['pub_app_share'] > 0)	{
        $cms_app_Share = $r1['pub_app_share']/100; 
    }
    else{ 
        $cms_app_Share = 15/100;
    }
    
    // End oF--> Share Calculation For Adx Display,video,app
    
    
    //ADSENSE data Fetch
    if(in_array($value['pub_adsense_id'],array_values($adsense_active_ids))){
        
    }
    else{
        $adsense_dayfile = $uniq_id.'.adsense_daywise';
        $adsense_clientid = 'ca-'.$value['pub_adsense_id'];
        $where_adsense			= ['date'=>['$gte' => $previous_day, '$lte' => $previous_day], 'ad_client_id'=>['$eq'=>$adsense_clientid]];
        $query_adsense = new MongoDB\Driver\Query($where_adsense);
        $result_adsense = $this->connMongoDb->executeQuery( $adsense_dayfile, $query_adsense);
    
        foreach($result_adsense as $ads) {
            $ads_day_adimp += $ads->impressions;
            $ads_day_cov += ($ads->ad_requests_coverage)*100;
            $ads_day_rev += $ads->earnings;
            $ads_day_adreq += $ads->ad_requests;
            $ads_day_matchadreq += $ads->matched_request;
            $ads_day_ecpm += ($ads->earnings/$ads->matched_request)*1000;
        }
    
        $queryall_adsense = new MongoDB\Driver\Command([
            'aggregate' => 'adsense_daywise',
            'pipeline' => [
                ['$match'=>['date'=>['$gte' =>$start_7_days,'$lte' =>$end_7_days],'ad_client_id'=>['$eq'=>$adsense_clientid]]]
            ],
            'cursor' => new stdClass,
        ]);
        $resultall_adsense = $this->connMongoDb->executeCommand($uniq_id,$queryall_adsense);
    
        foreach($resultall_adsense as $ads_all) {
            $ads_month_adimp += ($ads_all->impressions/$count_7_days);
    
            if($ads_all->ad_requests_coverage >= 2) {
                // this condition is for when in dataset calculated (%)value comes, then it will divide first
                $ad_requests_coverage = $ads_all->ad_requests_coverage/100;
            }
            else{
                $ad_requests_coverage = $ads_all->ad_requests_coverage;
            }
            $ads_month_cov += (($ad_requests_coverage)/$count_7_days)*100;
            $ads_month_rev += ($ads_all->earnings/$count_7_days);
            $ads_month_adreq += ($ads->ad_requests/$count_7_days);
            $ads_month_matchadreq += ($ads->matched_request/$count_7_days);
            $ads_month_ecpm += (($ads_all->earnings/$ads_all->matched_request)*1000)/$count_7_days;
        }
    }
    //End oF--> ADSENSE data Fetch
    
    //HeaderBidder data Fetch
    if($value['service_id']==4)	{
    
        $query_header_bidder = new MongoDB\Driver\Command([
            'aggregate' => 'header_bidder',
            'pipeline' => [
                ['$match'=>['DATE'=>['$gte' =>$previous_day,'$lte' =>$previous_day]]],
                ['$project'=>['TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'=> 1, 'TOTAL_FILL_RATE'=> 1, 'TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM'=> 1, 'TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'=> 1]]
            ],
            'cursor' => new stdClass,
        ]);
        $result_header_bidder = $this->connMongoDb->executeCommand($uniq_id,$query_header_bidder);
    
        foreach($result_header_bidder as $hb)	{
            $hb_day_adimp += 	$hb->TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS;
            $hb_day_cov += 		round($hb->TOTAL_FILL_RATE,2);
            $hb_day_ecpm += 	round($hb->TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM,2);
            $hb_day_rev += 		$hb->TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE;
            $hb_day_adreq += 	round(($hb->TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS/$hb->TOTAL_FILL_RATE)*100,2);
        }
    $hb_day_matchadreq  =  ($hb_day_adreq*$hb_day_cov)/100;
        $queryall_header_bidder = new MongoDB\Driver\Command([
            'aggregate' => 'header_bidder',
            'pipeline' => [
                ['$match'=>['DATE'=>['$gte' =>$start_7_days,'$lte' =>$end_7_days]]],
                ['$project'=>['TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS'=> 1, 'TOTAL_FILL_RATE'=> 1, 'TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM'=> 1, 'TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE'=> 1]]
            ],
            'cursor' => new stdClass,
        ]);
        $resultall_header_bidder = $this->connMongoDb->executeCommand($uniq_id,$queryall_header_bidder);
    
        foreach($resultall_header_bidder as $hb_all)	{
            $hb_month_adimp += 	($hb_all->TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS/$count_7_days);
            $hb_month_cov += 	($hb_all->TOTAL_FILL_RATE/$count_7_days);
            $hb_month_ecpm += 	($hb_all->TOTAL_LINE_ITEM_LEVEL_WITHOUT_CPD_AVERAGE_ECPM/$count_7_days);
            $hb_month_rev += 	($hb_all->TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE/$count_7_days);
            $hb_month_adreq += 	round(($hb_all->TOTAL_LINE_ITEM_LEVEL_IMPRESSIONS/$hb_all->TOTAL_FILL_RATE)*100,2);
        }
        $hb_month_matchadreq  =  ($hb_month_adreq*$hb_month_cov)/100;
    }
    else	{
        $hb_day_adimp = 0;
        $hb_day_cov = 0;
        $hb_day_ecpm = 0;
        $hb_day_rev = 0;
        $hb_day_adreq =0;
        $hb_day_matchadreq = 0;
    
        $hb_month_adimp = 0;
        $hb_month_cov = 0;
        $hb_month_ecpm = 0;
        $hb_month_rev = 0;
        $hb_month_adreq =0;
        $hb_month_matchadreq= 0;
    }
    
    //End oF--> HeaderBidder data Fetch
    
    //Last 7 day Calculation
    
    // Cyber Adx Display
    $display_imp_7day = $row_display_last7days[0]['adimr']/$count_7_days;
    $display_rev_7day = ($row_display_last7days[0]['earn'])/$count_7_days;
    $display_cov_7day = $row_display_last7days[0]['covg'];
    $display_ecpm_7day = $row_display_last7days[0]['ecpmx'];
    
    // Cyber Adx video
    $video_imp_7day = $row_video_last7days[0]['adimr']/$count_7_days;
    $video_rev_7day = ($row_video_last7days[0]['earn']-($row_video_last7days[0]['earn']*$cms_video_Share))/$count_7_days;
    $video_cov_7day = $row_video_last7days[0]['covg'];
    $video_ecpm_7day = $row_video_last7days[0]['ecpmx'];
    
    // Cyber Adx App
    $app_imp_7day = $row_app_last7days[0]['adimr']/$count_7_days;
    $app_rev_7day = ($row_app_last7days[0]['earn']-($row_app_last7days[0]['earn']*$cms_app_Share))/$count_7_days;
    $app_cov_7day = $row_app_last7days[0]['covg'];
    $app_ecpm_7day = $row_app_last7days[0]['ecpmx'];
    
    // Adsense
    $adsense_imp_7day = $ads_month_adimp;
    $adsense_rev_7day = $ads_month_rev;
    $adsense_cov_7day = $ads_month_cov;
    $adsense_ecpm_7day = $ads_month_ecpm;
    
    //CyberAds pro
    $hb_imp_7day = $hb_month_adimp;
    $hb_rev_7day = ($hb_month_rev-($hb_month_rev*$cms_display_Share));
    $hb_cov_7day = $hb_month_cov;
    $hb_ecpm_7day = $hb_month_ecpm;
    
    //End of--Last 7 day Calculation
    
    // Calculation of a Day
    
    $daily_display_imp_with_hb = ($row_display_Day[0]['adimr']+$hb_day_adimp);
    if($hb_day_ecpm > 0) {
        if($row_display_Day[0]['ecpmx'] > 0) {
            $daily_display_ecpm_with_hb = (($row_display_Day[0]['ecpmx'])+($hb_day_ecpm))/2;
        }
        else{
            $daily_display_ecpm_with_hb = ($hb_day_ecpm);
        }
        
    }
    else{
        $daily_display_ecpm_with_hb = $row_display_Day[0]['ecpmx'];
    }
    if($hb_day_cov > 0)	{
        if($row_display_Day[0]['covg'] > 0) {
            $daily_display_cov_with_hb = (($row_display_Day[0]['covg'])+($hb_day_cov))/2;
        }
        else{
            $daily_display_cov_with_hb = ($hb_day_cov);
        }
        
        // $daily_display_cov_with_hb = (($row_display_Day[0]['covg'])+($hb_day_cov))/2;
    }
    else{
        $daily_display_cov_with_hb = $row_display_Day[0]['covg'];
    }
    $daily_display_revenue_with_hb = ($row_display_Day[0]['earn'])+($hb_day_rev-($hb_day_rev*$cms_display_Share));
    
    $daily_display_imp = $row_display_Day[0]['adimr'];
    $daily_display_ecpm = $row_display_Day[0]['ecpmx'];
    $daily_display_cov = $row_display_Day[0]['covg'];
    $daily_display_revenue = ($row_display_Day[0]['earn']);
    
    $daily_video_ecpm = $row_video_Day[0]['ecpmx'];
    $daily_video_imp = $row_video_Day[0]['adimr'];
    $daily_video_cov = $row_video_Day[0]['covg'];
    $daily_video_revenue = ($row_video_Day[0]['earn']-($row_video_Day[0]['earn']*$cms_video_Share));
    
    $daily_app_ecpm = $row_app_Day[0]['ecpmx'];
    $daily_app_imp = $row_app_Day[0]['adimr'];
    $daily_app_cov = $row_app_Day[0]['covg'];
    $daily_app_revenue = ($row_app_Day[0]['earn']-($row_app_Day[0]['earn']*$cms_app_Share));
    
    // End of-- Calculation of day
    
    
    // Table 1
    
    $table1_day_matchadreq = $row_display_Day[0]['matchadreq']+$row_video_Day[0]['matchadreq']+$row_app_Day[0]['matchadreq']+$hb_day_matchadreq;
    $table1_day_adreq = $row_display_Day[0]['adreq']+$row_video_Day[0]['adreq']+$row_app_Day[0]['adreq']+$hb_day_adreq;
    $table1_day_imp = $daily_display_imp_with_hb+$daily_video_imp+$daily_app_imp;
    $table1_day_rev = $daily_display_revenue_with_hb+$daily_video_revenue+$daily_app_revenue;
    if($table1_day_adreq>0){
    $table1_day_cov = (($table1_day_matchadreq/$table1_day_adreq)*100);
    }
    else{
        $table1_day_cov=0;
    }
    $table1_day_ecpm = $this->sum_params($daily_display_ecpm_with_hb,$daily_video_ecpm,$daily_app_ecpm,0);
    // echo "<pre>";print_r($table1_day_ecpm);die;
    $table1_7day_matchadreq = $row_display_last7days[0]['matchadreq']+$row_video_last7days[0]['matchadreq']+$row_app_last7days[0]['matchadreq']+$hb_month_matchadreq;
    $table1_7day_adreq = $row_display_last7days[0]['adreq']+$row_video_last7days[0]['adreq']+$row_app_last7days[0]['adreq']+$hb_month_adreq;
    if($table1_7day_adreq>0){
    $table1_7day_cov = (($table1_7day_matchadreq/$table1_7day_adreq)*100);
    }
    else{
        $table1_7day_cov=0; 
    }
    // echo "<pre>";print_r($table1_7day_cov);die;
    $table1_7day_ecpm = $this->sum_params($display_ecpm_7day,$video_ecpm_7day,$app_ecpm_7day,$hb_ecpm_7day);
    $table1_7day_imp = $display_imp_7day+$video_imp_7day+$app_imp_7day+$hb_imp_7day;
    $table1_7day_rev = $display_rev_7day+$video_rev_7day+$app_rev_7day+$hb_rev_7day;
    
    
    //	Total Line
    if($table1_7day_adreq>0){
    $table1_adx_cov = (($table1_7day_matchadreq/$table1_7day_adreq)*100);
    }
    else{
        $table1_adx_cov=0;  
    }
    $table1_total_cov = $this->sum_params($table1_adx_cov,$adsense_cov_7day,0,0);
    $table1_total_ecpm = $this->sum_params($table1_7day_ecpm,$adsense_ecpm_7day,0,0);
    $table1_total_imp = $table1_7day_imp+$adsense_imp_7day;
    $table1_total_rev = $table1_7day_rev+$adsense_rev_7day;
    
    $total_daily_table1_imp = $table1_day_imp+$ads_day_adimp;
    if($table1_day_adreq>0){
    $total_daily_table1_adx_cov = (($table1_day_matchadreq/$table1_day_adreq)*100);
    }
    else{
        $total_daily_table1_adx_cov=0; 
    }
    $total_daily_table1_cov = $this->sum_params($total_daily_table1_adx_cov,$ads_day_cov,0,0);
    $total_daily_table1_ecpm = $this->sum_params($table1_day_ecpm,$ads_day_ecpm,0,0);
    $total_daily_table1_rev = $table1_day_rev+$ads_day_rev;
    
    // End of Table 1
    
    // Table 3
    
    $table3_total_cov = $this->sum_params($display_cov_7day,$video_cov_7day,$app_cov_7day,0);
    $table3_total_ecpm = $this->sum_params($display_ecpm_7day,$video_ecpm_7day,$app_ecpm_7day,0);
    $table3_total_imp = $display_imp_7day+$video_imp_7day+$app_imp_7day;
    $table3_total_rev = $display_rev_7day+$video_rev_7day+$app_rev_7day;
    
    $total_daily_table3_imp = $daily_display_imp+$daily_video_imp+$daily_app_imp;
    $total_daily_table3_cov = $this->sum_params($daily_display_cov,$daily_video_cov,$daily_app_cov,0);
    $total_daily_table3_ecpm = $this->sum_params($daily_display_ecpm,$daily_video_ecpm,$daily_app_ecpm,0);
    $total_daily_table3_rev = $daily_display_revenue+$daily_video_revenue+$daily_app_revenue;
    
    // End of Table 3
    
    // notification calculation
    if($table1_total_imp>0){
    $imp_percent = ((($total_daily_table1_imp-$table1_total_imp)/$table1_total_imp)*100);
    }
    else{
        $imp_percent=0;
    }
    if($table1_total_ecpm>0){
    $ecpm_percent = ((($total_daily_table1_ecpm-$table1_total_ecpm)/$table1_total_ecpm)*100);
    }
    else{
        $ecpm_percent=0;
    }
    if($table1_total_rev>0){
    $rev_percent = ((($total_daily_table1_rev-$table1_total_rev)/$table1_total_rev)*100);
    }
    else{
        $rev_percent=0;  
    }
    //end of notification calculation
    
    if($total_daily_table1_rev > 0) {
    $mail_body_layout = '<html>
    <head>
      <meta charset="utf-8" />
      <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <meta name="robots" content="noindex,nofollow" />
      <title>auxo ads </title> 
    </head>
    
    <style>
    /*tr:nth-child(even) {
      background-color: aliceblue;
    }*/
    </style>
    
    <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
        <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
          <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
            <tbody>
              <tr style="background: #D6C4FB !important;">
                 <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://auxoads.com/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
                 </td>
    
                <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
                  <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
                </td>-->
              </tr>
            </tbody>
          </table>
    
    
          <!--<table border="0" cellpadding="0" cellspacing="0" style="width: 100%; background: linear-gradient(45deg, #303f9f, #7b1fa2) !important; padding: 40px;" >
            <tbody>
              <tr>
                  <td>
                    <p style="font-size: 18px; line-height: 33px; color: #fff;">Hey<span>&nbsp;</span>'.$name.',</p>
    
                    <p style="font-size: 18px; line-height: 33px; color: #fff;">Here`s your daily revenue report!</p> 
                    </td>
              </tr>
            </tbody>
          </table>-->
    
            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
              <tbody>
                <tr>
                  <td>
                      <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$name.',</p>
    
                    <p style="font-size: 18px; line-height: 33px;">Here`s your daily revenue report!</p> 
    
                    <p style="font-size: 18px; line-height: 33px;">Performance Overview:</p>
    
                    <p style="font-size: 18px; line-height: 33px;">('.$p_day.' vs. Avg. of Last 7 Days)</p>
    
    
                    <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >Insights:
                    </span></u>
                    <ul>';
                    if(date('d') == 15) {
                        if(empty($resultforbnkdtl)) {
                            $mail_body_layout .= '
                            <li style="font-size: 18px; line-height: 33px;">Bank details required for your monthly Auxo Ads payment. Please ignore if you have already filled. </li>';
                        }
                    }
                    if($imp_percent) {
                        if($imp_percent > 0)	{
                        $mail_body_layout .= '
                              <li style="font-size: 18px; line-height: 33px;">Ad impressions increased by '.number_format(str_replace("-","",$imp_percent),2).'% as compared to last 7 days. </li>';
                        }
                        else {
                            $mail_body_layout .= ' </li>
                            <li style="font-size: 18px; line-height: 33px;">Ad impressions decreased by '.number_format(str_replace("-","",$imp_percent),2).'% as compared to last 7 days. </li>';
                        }
                    }
                    if($ecpm_percent) {
                        if($ecpm_percent > 0)	{
                        $mail_body_layout .= '
                        <li style="font-size: 18px; line-height: 33px;">CPM increased by '.str_replace("-","",round($ecpm_percent,2)).'% as compared to last 7 days. </li>';
                        }
                        else {
                        $mail_body_layout .= '
                        <li style="font-size: 18px; line-height: 33px;">CPM decreased by '.str_replace("-","",round($ecpm_percent,2)).'% as compared to last 7 days. </li>';
                        }
                    }
                    if($rev_percent) {
                        if($rev_percent > 0) {
                        $mail_body_layout .= '
                        <li style="font-size: 18px; line-height: 33px;">Revenue increased by '.str_replace("-","",round($rev_percent,2)).'% as compared to last 7 days.  </li>';
                    }
                    else {
                    $mail_body_layout .= '
                    <li style="font-size: 18px; line-height: 33px;">Revenue decreased by '.str_replace("-","",round($rev_percent,2)).'% as compared to last 7 days.  </li>';
                }
            }
            $mail_body_layout .='
                    </ul>
    
                    <!-- tables1 start -->
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;">
                    <tr>
                      <th rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;"></th>
    
                      <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Ad Impression</th>
    
                      <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">CPM</th>
    
                      <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Revenue</th>
                    </tr>
                    <tbody>';
                    if($table1_day_imp > 0) {
                        $mail_body_layout .='
                    <tr>
                      <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">Auxo Ads</td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">'.number_format($table1_day_imp,0).'<span style="color: #8d70fa;">[ '.number_format($table1_7day_imp,0).' ]</span></td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">$'.number_format($table1_day_ecpm,2).'<span style="color: #8d70fa;"> [ $'.number_format($table1_7day_ecpm,2).' ]</span> </td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">';
                      if($table1_day_rev > $table1_7day_rev) {
                          $mail_body_layout .='<span style="color: #8d70fa;">&#8673; $'.number_format($table1_day_rev,2).'';
                        }
                        elseif($table1_day_rev < $table1_7day_rev) {
                            $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($table1_day_rev,2).'';
                        }
                        else{
                            $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format($table1_day_rev,2).'';
                        }
                            $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($table1_7day_rev,2).' ]</span></td>
                    </tr>';
                }
                if($ads_day_adimp > 0) {
                $mail_body_layout .='
                   <tr>
                      <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">AdSense</td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($ads_day_adimp,0).'<span style="color: #8d70fa;">[ '.number_format($adsense_imp_7day,0).' ]</span></td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($ads_day_ecpm,2).' <span style="color: #8d70fa;">[ $'.number_format($adsense_ecpm_7day,2).' ]</span></td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                      if($ads_day_rev > $adsense_rev_7day) {
                          $mail_body_layout .=' <span style="color: #8d70fa;">&#8673; $'.number_format($ads_day_rev,2).'';
                        }
                        elseif($ads_day_rev < $adsense_rev_7day) {
                            $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($ads_day_rev,2).'';
                        }
                        else{
                            $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format($ads_day_rev,2).'';
                        }
                            $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($adsense_rev_7day,2).' ]</span></td>
                    </tr>';
                }
                if($total_daily_table1_rev > 0) {
                    $mail_body_layout .='
                    <tr>
                      <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Total</td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($total_daily_table1_imp,0).'<span style="color: #8d70fa;">[ '.number_format($table1_total_imp,0).' ]</span></td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($total_daily_table1_ecpm,2).'<span style="color: #8d70fa;">[ $'.number_format($table1_total_ecpm,2).' ]</span></td>
    
                      <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                      if($total_daily_table1_rev > $table1_total_rev) {
                          $mail_body_layout .='<span style="color: #8d70fa;">&#8673; $'.number_format($total_daily_table1_rev,2).'';
                        }
                        elseif($total_daily_table1_rev < $table1_total_rev) {
                            $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($total_daily_table1_rev,2).'';
                        }
                        else{
                            $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format($total_daily_table1_rev,2).'';
                        }
                            $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($table1_total_rev,2).' ]</span></td>
                    </tr>';
                }
                $mail_body_layout .='
                    </tbody>
                  </table>';
                  if($hb_day_adimp > 0) {
                      $mail_body_layout .='
                  <!-- tables1 End -->
    
                  <!-- tables2 start -->
                  <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; margin-top: 30px;">
                      <tr>
                        <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Auxo Pro</th>
      
                        <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Ad Impression</th>
      
                        <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">CPM</th>
      
                        <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Revenue</th>
                      </tr>
                      <tr>
                        <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">Display</td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">'.number_format($hb_day_adimp,0).'<span style="color: #8d70fa;">[ '.number_format($hb_imp_7day,0).' ]</span></td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;"> $'.number_format($hb_day_ecpm,2).'<span style="color: #8d70fa;"> [ $'.number_format($display_ecpm_7day,2).' ]</span> </td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">';
                        if((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)) > $hb_rev_7day) {
                            $mail_body_layout .=' <span style="color: #8d70fa;">&#8673; $'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                        elseif((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)) < $hb_rev_7day) {
                            $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                        else{
                            $mail_body_layout .='<span style="color: #8d70fa;"> $'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                            $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($hb_rev_7day,2).' ]</span></td>
                      </tr>
                     <tr>
                        <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Total</td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($hb_day_adimp,0).'<span style="color: #8d70fa;">[ '.number_format($hb_imp_7day,0).' ]</span></td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($hb_day_ecpm,2).' <span style="color: #8d70fa;">[ $'.number_format($hb_ecpm_7day,2).' ]</span></td>
      
                        <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                        if((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)) > $hb_rev_7day) {
                            $mail_body_layout .=' <span style="color: #8d70fa;">&#8673; $'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                        elseif((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)) < $hb_rev_7day) {
                            $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                        else{
                            $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format((($hb_day_rev)-(($hb_day_rev)*$cms_display_Share)),2).'';
                        }
                                                    $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($hb_rev_7day,2).' ]</span></td>
                      </tr>';
                    }
                    
                    $mail_body_layout .= '
                    <!-- tables2 End -->
                    <!-- tables3 start -->
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; margin-top: 30px;">
                        <tr>
                          <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Auxo Network</th>
        
                          <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Ad Impression</th>
        
                          <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">CPM</th>
        
                          <th style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Revenue</th>
                        </tr>';
                        if($daily_display_imp > 0) {
                            $mail_body_layout .='
                        <tr>
                          <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">Display</td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">'.number_format($daily_display_imp,0).'<span style="color: #8d70fa;">[ '.number_format($display_imp_7day,0).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;"> $'.number_format($daily_display_ecpm,2).'<span style="color: #8d70fa;"> [ $'.number_format($display_ecpm_7day,2).' ]</span> </td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif; background-color: aliceblue;">';
                          if($daily_display_revenue > $display_rev_7day) {
                              $mail_body_layout .=' <span style="color: #8d70fa;">&#8673; $'.number_format($daily_display_revenue,2).'';
                          }
                          elseif($daily_display_revenue < $display_rev_7day) {
                              $mail_body_layout .='&#8675; $'.number_format($daily_display_revenue,2).'';
                          }
                          else{
                              $mail_body_layout .='<span style="color: #8d70fa;"> $'.number_format($daily_display_revenue,2).'';
                          }
                          $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($display_rev_7day,2).' ]</span></td>
                        </tr>';
                      }
                      if($daily_video_imp > 0) {
                          $mail_body_layout .='
                       <tr>
                          <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Video</td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($daily_video_imp,0).'<span style="color: #8d70fa;"> [ '.number_format($video_imp_7day,0).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($daily_video_ecpm,2).' <span style="color: #8d70fa;">[ $'.number_format($video_ecpm_7day,2).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                          if($daily_video_revenue > $video_rev_7day) {
                              $mail_body_layout .=' <span style="color: #8d70fa;">&#8673; $'.number_format($daily_video_revenue,2).'';
                          }
                          elseif($daily_video_revenue < $video_rev_7day) {
                              $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($daily_video_revenue,2).'';
                          }
                          else{
                              $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format($daily_video_revenue,2).'';
                          }
                          $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($video_rev_7day,2).' ]</span></td>
                        </tr>';
                      }
                      if($daily_app_imp > 0) {
                          $mail_body_layout .='
                        <tr>
                          <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">App</td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($daily_app_imp,0).'<span style="color: #8d70fa;">[ '.number_format($app_imp_7day,0).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($daily_app_ecpm,0).' <span style="color: #8d70fa;">[ $'.number_format($app_ecpm_7day,2).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                          if($daily_app_revenue > $app_rev_7day) {
                              $mail_body_layout .='<span style="color: #8d70fa;">&#8673; $'.number_format($daily_app_revenue,2).'';
                          }
                          elseif($daily_app_revenue < $app_rev_7day) {
                              $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($daily_app_revenue,2).'';
                          }
                          else{
                              $mail_body_layout .='<span style="color: #8d70fa;">$'.number_format($daily_app_revenue,2).'';
                          }
                          $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($app_rev_7day,2).' ]</span></td>
                        </tr>';
                      }
                      $mail_body_layout .= '
                      <tr>
                          <td rowspan="1" style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">Total</td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">'.number_format($total_daily_table3_imp,0).'<span style="color: #8d70fa;">[ '.number_format($table3_total_imp,0).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">$'.number_format($total_daily_table3_ecpm,2).' <span style="color: #8d70fa;">[ $'.number_format($table3_total_ecpm,2).' ]</span></td>
        
                          <td style="border: 1px solid #e8eef3; text-align: left;  padding: 15px; white-space: inherit!important; width: 100px; font-family: Noto Sans KR , sans-serif;">';
                          if($total_daily_table3_rev > $table3_total_rev) {
                              $mail_body_layout .='<span style="color: #8d70fa;">&#8673; $'.number_format($total_daily_table3_rev,2).'';
                          }
                          elseif($daily_app_revenue < $table3_total_rev) {
                              $mail_body_layout .='<span style="color: #8d70fa;">&#8675; $'.number_format($total_daily_table3_rev,2).'';
                          }
                          else{
                              $mail_body_layout .='<span style="color: #8d70fa;"> $'.number_format($total_daily_table3_rev,2).'';
                          }
                          $mail_body_layout .='<span style="color: #8d70fa;">[ $'.number_format($table3_total_rev,2).' ]</span></td>
                        </tr>
                      </table>
                      <!-- tables3 Ends -->
                  
                    <p style="font-size: 18px; line-height: 33px;"> For further data analysis, please login to your:
                    </p>
    
                    <p>
                        <a href="https://auxoads.com/authentication/login" style="display: inline-block;
                        padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;" target="new">Auxo Ads Dashboard</a>
                    </p>
                    <!--<p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://auxoads.com/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Newsletter</span></a>, or read our <a href="https://auxoads.com/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>-->
                    <br>
                    <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Happy Earnings!</span><br>
                    <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                    </td>
                </tr>
                </tbody>
            </table>
            <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
              <span style="position: relative; top: -5px;">
                Visit us at
                <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://auxoads.com/">auxoads.com</a>
                <br/>
                 <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
                 </span>
              </span>
            </center>
          </center>
      </body>
    </html>';
    include_once('../mailerLib/class.phpmailer.php');
    include_once('../mailerLib/class.smtp.php');
    $mail  = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth   = true;
            $mail->Port       = 587;
            $mail->Host       = '103.76.212.101';    //192.124.249.8
            $mail->Username   = 'noreply@cybermedia.co.in';
            $mail->Password   = 'K6Cx*5G%W8j';
            $mail->AddAddress('srishtis@cybermedia.co.in');
            // $mail->AddAddress($value['pub_email']);
            // $mail->AddCC($value['mngremail']);
            // $mail->AddBcc('srishtis@cybermedia.co.in');
            $mail->SetFrom('noreply@cybermedia.co.in','Auxo Ads');
            $mail->Subject = 'Daily Revenue Report';
            $mail->MsgHTML($mail_body_layout);
    
            if ($mail->Send()) {
                    echo "mailsend"; 
            }
            else {
                echo 'Mailer Error: ' . $mail->ErrorInfo;
                echo '<h2 style="color:red;">Error: Mail Not Send.</h2>';
            } 
            $mail->ClearAddresses();
            $mail->ClearCCs();
            $mail->clearAllRecipients();
    
    }
    else{
        continue;
    }
            }
        }
    }
    
}

}
?>