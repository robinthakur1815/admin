<?php

class TopAdxRev{

	#database connection and table name
    private $conn;
    private $table_name1 = "adx_standard_report_notagwise";
    private $table_name2 = "adx_standardapp_report";
    private $table_name3 = "adx_vedio_overview";
    private $table_pub = "publisher_master_old";

    #object properties
    public $strtdate;
    public $enddate; 

    #constructor with $db as database connection
    public function  __construct($db,$strtdate,$enddate){
        $this->conn = $db;
        $this->strtdate = $strtdate;
        $this->enddate = $enddate;
    }


    #Get Top 15 Adx Contributor 
    public function TopAdxContributor(){
        #Display
    	$queryDisplay='SELECT adx.adx_p_name, SUM(adx.adx_earnings) AS gross_adx_display, SUM(adx.adx_revenue) AS net_adx_display, pm.acc_mgr_id FROM '.$this->table_name1.' adx LEFT JOIN '.$this->table_pub.' pm ON (adx.adx_p_name=pm.pub_acc_name) OR (adx.adx_p_name=pm.pub_acc_new_name) WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
     	#prepare query
        $row1 = $this->conn->prepare($queryDisplay);
        #execute query 
        $row1->execute();
        #return $row;
        $stmt_disp = $row1->get_result();
        $rowDisp = $stmt_disp->fetch_all(MYSQLI_ASSOC);

        #App
        $queryApp='SELECT adx.adx_p_name, SUM(adx.adx_earnings) AS gross_adx_app, (SUM(adx.adx_earnings)*15/100) AS net_adx_app, pm.acc_mgr_id FROM '.$this->table_name2.' adx LEFT JOIN '.$this->table_pub.' pm ON (adx.adx_p_name=pm.pub_acc_name) OR (adx.adx_p_name=pm.pub_acc_new_name) WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
    	#prepare query
        $row2 = $this->conn->prepare($queryApp);
        #execute query 
        $row2->execute();
        #return $row;
        $stmt_app = $row2->get_result();
        $rowApp = $stmt_app->fetch_all(MYSQLI_ASSOC);

        #Video
        $queryVideo='SELECT adx.adx_p_name, SUM(adx.revenue) AS gross_adx_video, (SUM(adx.revenue)*pm.pub_afv_share/100) AS net_adx_video, pm.acc_mgr_id FROM '.$this->table_name3.' adx LEFT JOIN '.$this->table_pub.' pm ON (adx.adx_p_name=pm.pub_acc_name) OR (adx.adx_p_name=pm.pub_acc_new_name) WHERE adx.date BETWEEN "'.$this->strtdate.'" AND "'.$this->enddate.'" GROUP BY adx.adx_p_name';
    	#prepare query
        $row3 = $this->conn->prepare($queryVideo);
        #execute query 
        $row3->execute();
        #return $row;
        $stmt_video = $row3->get_result();
        $rowVideo = $stmt_video->fetch_all(MYSQLI_ASSOC);

        $result_total = array_merge($rowDisp,$rowApp,$rowVideo);

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
            if($value_total['acc_mgr_id']!='14' && $value_total['acc_mgr_id']!='17' && $value_total['acc_mgr_id']!='23' && $value_total['acc_mgr_id']!='24' && $value_total['acc_mgr_id']!='31' && $value_total['acc_mgr_id']!='33' && $value_total['acc_mgr_id']!='34' && $value_total['acc_mgr_id']!='35' && $value_total['acc_mgr_id']!='36' && $value_total['acc_mgr_id']!='37' && $value_total['acc_mgr_id']!='38' && $value_total['acc_mgr_id']!='39' && $value_total['acc_mgr_id']!='40' && $value_total['acc_mgr_id']!='41') {

            $publisher_name = $value_total['adx_p_name'];
                #Gross
                if(isset($value_total['gross_adx_display'])){
                $gross_display = $value_total['gross_adx_display'];
                }
                if(isset($value_total['gross_adx_app'])){
                $gross_app = $value_total['gross_adx_app'];
                }
                if(isset($value_total['gross_adx_video'])){
                $gross_video = $value_total['gross_adx_video'];
                }
                #Net
                if(isset($value_total['net_adx_display'])){
                $net_display = $value_total['net_adx_display'];
                }
                if(isset($value_total['net_adx_app'])){
                $net_app = $value_total['net_adx_app'];
                }
                if(isset($value_total['net_adx_video'])){
                $net_video = $value_total['net_adx_video'];
                }
            $top_adx[$publisher_name]['gross'] = 0;
            $top_adx[$publisher_name]['net'] = 0;

            #TOP ADX PUB CODE
            if(isset($top_adx[$publisher_name]['gross'])){
            $top_adx[$publisher_name]['gross'] += ($gross_display+$gross_app+$gross_video);
            }
            if(isset($top_adx[$publisher_name]['net'])){
            $top_adx[$publisher_name]['net'] += ($net_display+$net_app+$net_video);
            }
            }

    	}
            #Reversing Array Revenue Wise
            arsort($top_adx);
            $i=1;
            $top_pub = array(); 
            foreach($top_adx as $top_key=>$top_value) {
                if($i<=15){
                $top_pub[$i]['pub_name'] = $top_key; 
                $top_pub[$i]['topline'] = number_format($top_value['gross'], 2); 
                $top_pub[$i]['bottomline'] = number_format($top_value['net'], 2);
                $i++;  
                }
            } 
            $json_response = json_encode($top_pub);
            echo $json_response;
		  

    }

}

?>