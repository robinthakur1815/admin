<?php
#Author BY SS
class Cron{
  
    #database connection and table name
    private $conn;
    private $table_name1 = "mcm_ad_exch_report_demo";
    private $table_name2 = "mcm_adtypewise_report_demo";
    private $table_name3 = "mcm_adunitwise_report_demo";
    private $table_name4 = "mcm_domainwise_report_demo";
	private $table_name5 = "mcm_devicecategory_report_demo";
    private $table_name6 = "mcm_countrywise_report_demo";

    private $table_name7 = "mcm_ad_exch_app_report_demo";
    private $table_name8 = "mcm_adtypewise_app_report_demo";
    private $table_name9 = "mcm_adunitwise_app_report_demo";
	private $table_name10 = "mcm_domainwise_app_report_demo";
    private $table_name11 = "mcm_devicecategory_app_report_demo";
    private $table_name12 = "mcm_countrywise_app_report_demo";

    private $table_name13 = "mcm_ad_exch_video_report_demo";
    private $table_name14 = "mcm_adtypewise_video_report_demo";
	private $table_name15 = "mcm_adunitwise_video_report_demo";
    private $table_name16 = "mcm_domainwise_video_report_demo";
    private $table_name17 = "mcm_devicecategory_video_report_demo";
	private $table_name18 = "mcm_countrywise_video_report_demo";
     private $table_master = "publisher_master";
     private $table_mcm_dis = "mcm_domainwise_report";
     private $table_mcm_video = "mcm_domainwise_video_report";
     private $table_mcm_app = "mcm_domainwise_app_report";
    
   
    #constructor with $db as database connection
    public function __construct($db,$dbMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $dbMongoDb;
    }


  #Date Update
    public function updateCron(){
        $last = 'SELECT ad_exch_date FROM `mcm_ad_exch_report_demo` ORDER BY ad_exch_date ASC LIMIT 1';
        $row_last = $this->conn->prepare($last);
        #execute query 
        $row_last->execute();
        $stmt_sub = $row_last->get_result();
        $rowSub = $stmt_sub->fetch_array(MYSQLI_ASSOC);//15 oct
    
        $latest = 'SELECT ad_exch_date FROM `mcm_ad_exch_report_demo` ORDER BY ad_exch_date DESC LIMIT 1';
        $row_latest = $this->conn->prepare($latest);
        #execute query 
        $row_latest->execute();
        $stmt_sub_latest = $row_latest->get_result();
        $rowSub_latest = $stmt_sub_latest->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate = date('Y-m-d');
        $cur_date = "SELECT ad_exch_date FROM mcm_ad_exch_report_demo where ad_exch_date='$cudate'";
        $row_cur = $this->conn->prepare($cur_date);
        #execute query 
        $row_cur->execute();
        $stmt_sub_cur = $row_cur->get_result();
        $rowSub_cur = $stmt_sub_cur->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur)){
        #update query
        $query_update = "UPDATE mcm_ad_exch_report_demo set ad_exch_date=DATE_ADD('$rowSub_latest[ad_exch_date]', INTERVAL 1 DAY) where ad_exch_date='$rowSub[ad_exch_date]'";
        #prepare query statement
        $stmt_date = $this->conn->prepare($query_update);
        if($stmt_date->execute()){
            return true;
            }
        
        }
    
    }
    public function updateCronadtype(){
        $last_adtype = 'SELECT ad_type_date FROM `mcm_adtypewise_report_demo` ORDER BY ad_type_date ASC LIMIT 1';
        $row_last_adtype = $this->conn->prepare($last_adtype);
        #execute query 
        $row_last_adtype->execute();
        $stmt_sub_adtype = $row_last_adtype->get_result();
        $rowSub_adtype = $stmt_sub_adtype->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_adtype = 'SELECT ad_type_date FROM `mcm_adtypewise_report_demo` ORDER BY ad_type_date DESC LIMIT 1';
        $row_latest_adtype = $this->conn->prepare($latest_adtype);
        #execute query 
        $row_latest_adtype->execute();
        $stmt_sub_latest_adtype = $row_latest_adtype->get_result();
        $rowSub_latest_adtype = $stmt_sub_latest_adtype->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_adtype = date('Y-m-d');
        $cur_date_adtype = "SELECT ad_type_date FROM mcm_adtypewise_report_demo where ad_type_date='$cudate_adtype'";
        $row_cur_adtype = $this->conn->prepare($cur_date_adtype);
        #execute query 
        $row_cur_adtype->execute();
        $stmt_sub_cur_adtype = $row_cur_adtype->get_result();
        $rowSub_cur_adtype = $stmt_sub_cur_adtype->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_adtype)){
        #update query
        $query_update_adtype = "UPDATE mcm_adtypewise_report_demo set ad_type_date=DATE_ADD('$rowSub_latest_adtype[ad_type_date]', INTERVAL 1 DAY) where ad_type_date='$rowSub_adtype[ad_type_date]'";
        #prepare query statement
        $stmt_date_adtype = $this->conn->prepare($query_update_adtype);
        if($stmt_date_adtype->execute()){
            return true;
        }

        }
    }
    public function updateCronadunit(){
        $last_adunit = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_report_demo` ORDER BY ad_unitwise_date ASC LIMIT 1';
        $row_last_adunit = $this->conn->prepare($last_adunit);
        #execute query 
        $row_last_adunit->execute();
        $stmt_sub_adunit = $row_last_adunit->get_result();
        $rowSub_adunit = $stmt_sub_adunit->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_adunit = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_report_demo` ORDER BY ad_unitwise_date DESC LIMIT 1';
        $row_latest_adunit = $this->conn->prepare($latest_adunit);
        #execute query 
        $row_latest_adunit->execute();
        $stmt_sub_latest_adunit = $row_latest_adunit->get_result();
        $rowSub_latest_adunit = $stmt_sub_latest_adunit->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_adunit = date('Y-m-d');
        $cur_date_adunit = "SELECT ad_unitwise_date FROM mcm_adunitwise_report_demo where ad_unitwise_date='$cudate_adunit'";
        $row_cur_adunit = $this->conn->prepare($cur_date_adunit);
        #execute query 
        $row_cur_adunit->execute();
        $stmt_sub_cur_adunit = $row_cur_adunit->get_result();
        $rowSub_cur_adunit = $stmt_sub_cur_adunit->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_adunit)){
        #update query
        $query_update_adunit = "UPDATE mcm_adunitwise_report_demo set ad_unitwise_date=DATE_ADD('$rowSub_latest_adunit[ad_unitwise_date]', INTERVAL 1 DAY) where ad_unitwise_date='$rowSub_adunit[ad_unitwise_date]'";
        #prepare query statement
        $stmt_date_adunit = $this->conn->prepare($query_update_adunit);
        if($stmt_date_adunit->execute()){
            return true;
        }

        }
    }
    public function updateCrondomain(){
        $last_domain = 'SELECT ad_domain_date FROM `mcm_domainwise_report_demo` ORDER BY ad_domain_date ASC LIMIT 1';
        $row_last_domain = $this->conn->prepare($last_domain);
        #execute query 
        $row_last_domain->execute();
        $stmt_sub_domain = $row_last_domain->get_result();
        $rowSub_domain = $stmt_sub_domain->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_domain = 'SELECT ad_domain_date FROM `mcm_domainwise_report_demo` ORDER BY ad_domain_date DESC LIMIT 1';
        $row_latest_domain = $this->conn->prepare($latest_domain);
        #execute query 
        $row_latest_domain->execute();
        $stmt_sub_latest_domain = $row_latest_domain->get_result();
        $rowSub_latest_domain = $stmt_sub_latest_domain->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_domain = date('Y-m-d');
        $cur_date_domain = "SELECT ad_domain_date FROM mcm_domainwise_report_demo where ad_domain_date='$cudate_domain'";
        $row_cur_domain = $this->conn->prepare($cur_date_domain);
        #execute query 
        $row_cur_domain->execute();
        $stmt_sub_cur_domain = $row_cur_domain->get_result();
        $rowSub_cur_domain = $stmt_sub_cur_domain->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_domain)){
        #update query
        $query_update_domain = "UPDATE mcm_domainwise_report_demo set ad_domain_date=DATE_ADD('$rowSub_latest_domain[ad_domain_date]', INTERVAL 1 DAY) where ad_domain_date='$rowSub_domain[ad_domain_date]'";
        #prepare query statement
        $stmt_date_domain = $this->conn->prepare($query_update_domain);
        if($stmt_date_domain->execute()){
            return true;
        }

        }
    }
    public function updateCrondevice(){
        $last_device = 'SELECT ad_device_date FROM `mcm_devicecategory_report_demo` ORDER BY ad_device_date ASC LIMIT 1';
        $row_last_device = $this->conn->prepare($last_device);
        #execute query 
        $row_last_device->execute();
        $stmt_sub_device = $row_last_device->get_result();
        $rowSub_device = $stmt_sub_device->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_device = 'SELECT ad_device_date FROM `mcm_devicecategory_report_demo` ORDER BY ad_device_date DESC LIMIT 1';
        $row_latest_device = $this->conn->prepare($latest_device);
        #execute query 
        $row_latest_device->execute();
        $stmt_sub_latest_device = $row_latest_device->get_result();
        $rowSub_latest_device = $stmt_sub_latest_device->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_device = date('Y-m-d');
        $cur_date_device = "SELECT ad_device_date FROM mcm_devicecategory_report_demo where ad_device_date='$cudate_device'";
        $row_cur_device = $this->conn->prepare($cur_date_device);
        #execute query 
        $row_cur_device->execute();
        $stmt_sub_cur_device = $row_cur_device->get_result();
        $rowSub_cur_device = $stmt_sub_cur_device->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_device)){
        #update query
        $query_update_device = "UPDATE mcm_devicecategory_report_demo set ad_device_date=DATE_ADD('$rowSub_latest_device[ad_device_date]', INTERVAL 1 DAY) where ad_device_date='$rowSub_device[ad_device_date]'";
        #prepare query statement
        $stmt_date_device = $this->conn->prepare($query_update_device);
        if($stmt_date_device->execute()){
            return true;
        }

        }
    }
    public function updateCroncoun(){
        $last_coun = 'SELECT ad_coun_date FROM `mcm_countrywise_report_demo` ORDER BY ad_coun_date ASC LIMIT 1';
        $row_last_coun = $this->conn->prepare($last_coun);
        #execute query 
        $row_last_coun->execute();
        $stmt_sub_coun = $row_last_coun->get_result();
        $rowSub_coun = $stmt_sub_coun->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_coun = 'SELECT ad_coun_date FROM `mcm_countrywise_report_demo` ORDER BY ad_coun_date DESC LIMIT 1';
        $row_latest_coun = $this->conn->prepare($latest_coun);
        #execute query 
        $row_latest_coun->execute();
        $stmt_sub_latest_coun = $row_latest_coun->get_result();
        $rowSub_latest_coun = $stmt_sub_latest_coun->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_coun = date('Y-m-d');
        $cur_date_coun = "SELECT ad_coun_date FROM mcm_countrywise_report_demo where ad_coun_date='$cudate_coun'";
        $row_cur_coun = $this->conn->prepare($cur_date_coun);
        #execute query 
        $row_cur_coun->execute();
        $stmt_sub_cur_coun = $row_cur_coun->get_result();
        $rowSub_cur_coun = $stmt_sub_cur_coun->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_coun)){
        #update query
        $query_update_coun = "UPDATE mcm_countrywise_report_demo set ad_coun_date=DATE_ADD('$rowSub_latest_coun[ad_coun_date]', INTERVAL 1 DAY) where ad_coun_date='$rowSub_coun[ad_coun_date]'";
        #prepare query statement
        $stmt_date_coun = $this->conn->prepare($query_update_coun);
        if($stmt_date_coun->execute()){
            return true;
        }

        }
    }
    #App Tables
    public function updateCronappoverview(){
        $last_appoverview = 'SELECT ad_exch_date FROM `mcm_ad_exch_app_report_demo` ORDER BY ad_exch_date ASC LIMIT 1';
        $row_last_appoverview = $this->conn->prepare($last_appoverview);
        #execute query 
        $row_last_appoverview->execute();
        $stmt_sub_appoverview = $row_last_appoverview->get_result();
        $rowSub_appoverview = $stmt_sub_appoverview->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appoverview = 'SELECT ad_exch_date FROM `mcm_ad_exch_app_report_demo` ORDER BY ad_exch_date DESC LIMIT 1';
        $row_latest_appoverview = $this->conn->prepare($latest_appoverview);
        #execute query 
        $row_latest_appoverview->execute();
        $stmt_sub_latest_appoverview = $row_latest_appoverview->get_result();
        $rowSub_latest_appoverview = $stmt_sub_latest_appoverview->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appoverview = date('Y-m-d');
        $cur_date_appoverview = "SELECT ad_exch_date FROM mcm_ad_exch_app_report_demo where ad_exch_date='$cudate_appoverview'";
        $row_cur_appoverview = $this->conn->prepare($cur_date_appoverview);
        #execute query 
        $row_cur_appoverview->execute();
        $stmt_sub_cur_appoverview = $row_cur_appoverview->get_result();
        $rowSub_cur_appoverview = $stmt_sub_cur_appoverview->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appoverview)){
        #update query
        $query_update_appoverview = "UPDATE mcm_ad_exch_app_report_demo set ad_exch_date=DATE_ADD('$rowSub_latest_appoverview[ad_exch_date]', INTERVAL 1 DAY) where ad_exch_date='$rowSub_appoverview[ad_exch_date]'";
        #prepare query statement
        $stmt_date_appoverview = $this->conn->prepare($query_update_appoverview);
        if($stmt_date_appoverview->execute()){
            return true;
        }

        }
    }
    public function updateCronappadtype(){
        $last_appadtype = 'SELECT ad_type_date FROM `mcm_adtypewise_app_report_demo` ORDER BY ad_type_date ASC LIMIT 1';
        $row_last_appadtype = $this->conn->prepare($last_appadtype);
        #execute query 
        $row_last_appadtype->execute();
        $stmt_sub_appadtype = $row_last_appadtype->get_result();
        $rowSub_appadtype = $stmt_sub_appadtype->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appadtype = 'SELECT ad_type_date FROM `mcm_adtypewise_app_report_demo` ORDER BY ad_type_date DESC LIMIT 1';
        $row_latest_appadtype = $this->conn->prepare($latest_appadtype);
        #execute query 
        $row_latest_appadtype->execute();
        $stmt_sub_latest_appadtype = $row_latest_appadtype->get_result();
        $rowSub_latest_appadtype = $stmt_sub_latest_appadtype->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appadtype = date('Y-m-d');
        $cur_date_appadtype = "SELECT ad_type_date FROM mcm_adtypewise_app_report_demo where ad_type_date='$cudate_appadtype'";
        $row_cur_appadtype = $this->conn->prepare($cur_date_appadtype);
        #execute query 
        $row_cur_appadtype->execute();
        $stmt_sub_cur_appadtype = $row_cur_appadtype->get_result();
        $rowSub_cur_appadtype = $stmt_sub_cur_appadtype->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appadtype)){
        #update query
        $query_update_appadtype = "UPDATE mcm_adtypewise_app_report_demo set ad_type_date=DATE_ADD('$rowSub_latest_appadtype[ad_type_date]', INTERVAL 1 DAY) where ad_type_date='$rowSub_appadtype[ad_type_date]'";
        #prepare query statement
        $stmt_date_appadtype = $this->conn->prepare($query_update_appadtype);
        if($stmt_date_appadtype->execute()){
            return true;
        }

        }
    }
    public function updateCronappunitwise(){
        $last_appunitwise = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_app_report_demo` ORDER BY ad_unitwise_date ASC LIMIT 1';
        $row_last_appunitwise = $this->conn->prepare($last_appunitwise);
        #execute query 
        $row_last_appunitwise->execute();
        $stmt_sub_appunitwise = $row_last_appunitwise->get_result();
        $rowSub_appunitwise = $stmt_sub_appunitwise->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appunitwise = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_app_report_demo` ORDER BY ad_unitwise_date DESC LIMIT 1';
        $row_latest_appunitwise = $this->conn->prepare($latest_appunitwise);
        #execute query 
        $row_latest_appunitwise->execute();
        $stmt_sub_latest_appunitwise = $row_latest_appunitwise->get_result();
        $rowSub_latest_appunitwise = $stmt_sub_latest_appunitwise->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appunitwise = date('Y-m-d');
        $cur_date_appunitwise = "SELECT ad_unitwise_date FROM mcm_adunitwise_app_report_demo where ad_unitwise_date='$cudate_appunitwise'";
        $row_cur_appunitwise = $this->conn->prepare($cur_date_appunitwise);
        #execute query 
        $row_cur_appunitwise->execute();
        $stmt_sub_cur_appunitwise = $row_cur_appunitwise->get_result();
        $rowSub_cur_appunitwise = $stmt_sub_cur_appunitwise->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appunitwise)){
        #update query
        $query_update_appunitwise = "UPDATE mcm_adunitwise_app_report_demo set ad_unitwise_date=DATE_ADD('$rowSub_latest_appunitwise[ad_unitwise_date]', INTERVAL 1 DAY) where ad_unitwise_date='$rowSub_appunitwise[ad_unitwise_date]'";
        #prepare query statement
        $stmt_date_appunitwise = $this->conn->prepare($query_update_appunitwise);
        if($stmt_date_appunitwise->execute()){
            return true;
        }

        }
    }
    public function updateCronappdomain(){
        $last_appdomain = 'SELECT ad_domain_date FROM `mcm_domainwise_app_report_demo` ORDER BY ad_domain_date ASC LIMIT 1';
        $row_last_appdomain = $this->conn->prepare($last_appdomain);
        #execute query 
        $row_last_appdomain->execute();
        $stmt_sub_appdomain = $row_last_appdomain->get_result();
        $rowSub_appdomain = $stmt_sub_appdomain->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appdomain = 'SELECT ad_domain_date FROM `mcm_domainwise_app_report_demo` ORDER BY ad_domain_date DESC LIMIT 1';
        $row_latest_appdomain = $this->conn->prepare($latest_appdomain);
        #execute query 
        $row_latest_appdomain->execute();
        $stmt_sub_latest_appdomain = $row_latest_appdomain->get_result();
        $rowSub_latest_appdomain = $stmt_sub_latest_appdomain->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appdomain = date('Y-m-d');
        $cur_date_appdomain = "SELECT ad_domain_date FROM mcm_domainwise_app_report_demo where ad_domain_date='$cudate_appdomain'";
        $row_cur_appdomain = $this->conn->prepare($cur_date_appdomain);
        #execute query 
        $row_cur_appdomain->execute();
        $stmt_sub_cur_appdomain = $row_cur_appdomain->get_result();
        $rowSub_cur_appdomain = $stmt_sub_cur_appdomain->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appdomain)){
        #update query
        $query_update_appdomain = "UPDATE mcm_domainwise_app_report_demo set ad_domain_date=DATE_ADD('$rowSub_latest_appdomain[ad_domain_date]', INTERVAL 1 DAY) where ad_domain_date='$rowSub_appdomain[ad_domain_date]'";
        #prepare query statement
        $stmt_date_appdomain = $this->conn->prepare($query_update_appdomain);
        if($stmt_date_appdomain->execute()){
            return true;
        }

        }
    }
    public function updateCronappdevice(){
        $last_appdevice = 'SELECT ad_device_date FROM `mcm_devicecategory_app_report_demo` ORDER BY ad_device_date ASC LIMIT 1';
        $row_last_appdevice = $this->conn->prepare($last_appdevice);
        #execute query 
        $row_last_appdevice->execute();
        $stmt_sub_appdevice = $row_last_appdevice->get_result();
        $rowSub_appdevice = $stmt_sub_appdevice->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appdevice = 'SELECT ad_device_date FROM `mcm_devicecategory_app_report_demo` ORDER BY ad_device_date DESC LIMIT 1';
        $row_latest_appdevice = $this->conn->prepare($latest_appdevice);
        #execute query 
        $row_latest_appdevice->execute();
        $stmt_sub_latest_appdevice = $row_latest_appdevice->get_result();
        $rowSub_latest_appdevice = $stmt_sub_latest_appdevice->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appdevice = date('Y-m-d');
        $cur_date_appdevice = "SELECT ad_device_date FROM mcm_devicecategory_app_report_demo where ad_device_date='$cudate_appdevice'";
        $row_cur_appdevice = $this->conn->prepare($cur_date_appdevice);
        #execute query 
        $row_cur_appdevice->execute();
        $stmt_sub_cur_appdevice = $row_cur_appdevice->get_result();
        $rowSub_cur_appdevice = $stmt_sub_cur_appdevice->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appdevice)){
        #update query
        $query_update_appdevice = "UPDATE mcm_devicecategory_app_report_demo set ad_device_date=DATE_ADD('$rowSub_latest_appdevice[ad_device_date]', INTERVAL 1 DAY) where ad_device_date='$rowSub_appdevice[ad_device_date]'";
        #prepare query statement
        $stmt_date_appdevice = $this->conn->prepare($query_update_appdevice);
        if($stmt_date_appdevice->execute()){
            return true;
        }

        }
    }
    public function updateCronappcoun(){
        $last_appcoun = 'SELECT ad_coun_date FROM `mcm_countrywise_app_report_demo` ORDER BY ad_coun_date ASC LIMIT 1';
        $row_last_appcoun = $this->conn->prepare($last_appcoun);
        #execute query 
        $row_last_appcoun->execute();
        $stmt_sub_appcoun = $row_last_appcoun->get_result();
        $rowSub_appcoun = $stmt_sub_appcoun->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_appcoun = 'SELECT ad_coun_date FROM `mcm_countrywise_app_report_demo` ORDER BY ad_coun_date DESC LIMIT 1';
        $row_latest_appcoun = $this->conn->prepare($latest_appcoun);
        #execute query 
        $row_latest_appcoun->execute();
        $stmt_sub_latest_appcoun = $row_latest_appcoun->get_result();
        $rowSub_latest_appcoun = $stmt_sub_latest_appcoun->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_appcoun = date('Y-m-d');
        $cur_date_appcoun = "SELECT ad_coun_date FROM mcm_countrywise_app_report_demo where ad_coun_date='$cudate_appcoun'";
        $row_cur_appcoun = $this->conn->prepare($cur_date_appcoun);
        #execute query 
        $row_cur_appcoun->execute();
        $stmt_sub_cur_appcoun = $row_cur_appcoun->get_result();
        $rowSub_cur_appcoun = $stmt_sub_cur_appcoun->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_appcoun)){
        #update query
        $query_update_appcoun = "UPDATE mcm_countrywise_app_report_demo set ad_coun_date=DATE_ADD('$rowSub_latest_appcoun[ad_coun_date]', INTERVAL 1 DAY) where ad_coun_date='$rowSub_appcoun[ad_coun_date]'";
        #prepare query statement
        $stmt_date_appcoun = $this->conn->prepare($query_update_appcoun);
        if($stmt_date_appcoun->execute()){
            return true;
        }

        }
    }
    #video tables
    public function updateCronvideoverview(){
        $last_videoverview = 'SELECT ad_exch_date FROM `mcm_ad_exch_video_report_demo` ORDER BY ad_exch_date ASC LIMIT 1';
        $row_last_videoverview = $this->conn->prepare($last_videoverview);
        #execute query 
        $row_last_videoverview->execute();
        $stmt_sub_videoverview = $row_last_videoverview->get_result();
        $rowSub_videoverview = $stmt_sub_videoverview->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videoverview = 'SELECT ad_exch_date FROM `mcm_ad_exch_video_report_demo` ORDER BY ad_exch_date DESC LIMIT 1';
        $row_latest_videoverview = $this->conn->prepare($latest_videoverview);
        #execute query 
        $row_latest_videoverview->execute();
        $stmt_sub_latest_videoverview = $row_latest_videoverview->get_result();
        $rowSub_latest_videoverview = $stmt_sub_latest_videoverview->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videoverview = date('Y-m-d');
        $cur_date_videoverview = "SELECT ad_exch_date FROM mcm_ad_exch_video_report_demo where ad_exch_date='$cudate_videoverview'";
        $row_cur_videoverview = $this->conn->prepare($cur_date_videoverview);
        #execute query 
        $row_cur_videoverview->execute();
        $stmt_sub_cur_videoverview = $row_cur_videoverview->get_result();
        $rowSub_cur_videoverview = $stmt_sub_cur_videoverview->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videoverview)){
        #update query
        $query_update_videoverview = "UPDATE mcm_ad_exch_video_report_demo set ad_exch_date=DATE_ADD('$rowSub_latest_videoverview[ad_exch_date]', INTERVAL 1 DAY) where ad_exch_date='$rowSub_videoverview[ad_exch_date]'";
        #prepare query statement
        $stmt_date_videoverview = $this->conn->prepare($query_update_videoverview);
        if($stmt_date_videoverview->execute()){
            return true;
        }

        }
    }
    public function updateCronvideoadtype(){
        $last_videoadtype = 'SELECT ad_type_date FROM `mcm_adtypewise_video_report_demo` ORDER BY ad_type_date ASC LIMIT 1';
        $row_last_videoadtype = $this->conn->prepare($last_videoadtype);
        #execute query 
        $row_last_videoadtype->execute();
        $stmt_sub_videoadtype = $row_last_videoadtype->get_result();
        $rowSub_videoadtype = $stmt_sub_videoadtype->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videoadtype = 'SELECT ad_type_date FROM `mcm_adtypewise_video_report_demo` ORDER BY ad_type_date DESC LIMIT 1';
        $row_latest_videoadtype = $this->conn->prepare($latest_videoadtype);
        #execute query 
        $row_latest_videoadtype->execute();
        $stmt_sub_latest_videoadtype = $row_latest_videoadtype->get_result();
        $rowSub_latest_videoadtype = $stmt_sub_latest_videoadtype->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videoadtype = date('Y-m-d');
        $cur_date_videoadtype = "SELECT ad_type_date FROM mcm_adtypewise_video_report_demo where ad_type_date='$cudate_videoadtype'";
        $row_cur_videoadtype = $this->conn->prepare($cur_date_videoadtype);
        #execute query 
        $row_cur_videoadtype->execute();
        $stmt_sub_cur_videoadtype = $row_cur_videoadtype->get_result();
        $rowSub_cur_videoadtype = $stmt_sub_cur_videoadtype->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videoadtype)){
        #update query
        $query_update_videoadtype = "UPDATE mcm_adtypewise_video_report_demo set ad_type_date=DATE_ADD('$rowSub_latest_videoadtype[ad_type_date]', INTERVAL 1 DAY) where ad_type_date='$rowSub_videoadtype[ad_type_date]'";
        #prepare query statement
        $stmt_date_videoadtype = $this->conn->prepare($query_update_videoadtype);
        if($stmt_date_videoadtype->execute()){
            return true;
        }

        }
    }
    public function updateCronvideounitwise(){
        $last_videounitwise = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_video_report_demo` ORDER BY ad_unitwise_date ASC LIMIT 1';
        $row_last_videounitwise = $this->conn->prepare($last_videounitwise);
        #execute query 
        $row_last_videounitwise->execute();
        $stmt_sub_videounitwise = $row_last_videounitwise->get_result();
        $rowSub_videounitwise = $stmt_sub_videounitwise->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videounitwise = 'SELECT ad_unitwise_date FROM `mcm_adunitwise_video_report_demo` ORDER BY ad_unitwise_date DESC LIMIT 1';
        $row_latest_videounitwise = $this->conn->prepare($latest_videounitwise);
        #execute query 
        $row_latest_videounitwise->execute();
        $stmt_sub_latest_videounitwise = $row_latest_videounitwise->get_result();
        $rowSub_latest_videounitwise = $stmt_sub_latest_videounitwise->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videounitwise = date('Y-m-d');
        $cur_date_videounitwise = "SELECT ad_unitwise_date FROM mcm_adunitwise_video_report_demo where ad_unitwise_date='$cudate_videounitwise'";
        $row_cur_videounitwise = $this->conn->prepare($cur_date_videounitwise);
        #execute query 
        $row_cur_videounitwise->execute();
        $stmt_sub_cur_videounitwise = $row_cur_videounitwise->get_result();
        $rowSub_cur_videounitwise = $stmt_sub_cur_videounitwise->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videounitwise)){
        #update query
        $query_update_videounitwise = "UPDATE mcm_adunitwise_video_report_demo set ad_unitwise_date=DATE_ADD('$rowSub_latest_videounitwise[ad_unitwise_date]', INTERVAL 1 DAY) where ad_unitwise_date='$rowSub_videounitwise[ad_unitwise_date]'";
        #prepare query statement
        $stmt_date_videounitwise = $this->conn->prepare($query_update_videounitwise);
        if($stmt_date_videounitwise->execute()){
            return true;
        }

        }
    }
    public function updateCronvideodomain(){
        $last_videodomain = 'SELECT ad_domain_date FROM `mcm_domainwise_video_report_demo` ORDER BY ad_domain_date ASC LIMIT 1';
        $row_last_videodomain = $this->conn->prepare($last_videodomain);
        #execute query 
        $row_last_videodomain->execute();
        $stmt_sub_videodomain = $row_last_videodomain->get_result();
        $rowSub_videodomain = $stmt_sub_videodomain->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videodomain = 'SELECT ad_domain_date FROM `mcm_domainwise_video_report_demo` ORDER BY ad_domain_date DESC LIMIT 1';
        $row_latest_videodomain = $this->conn->prepare($latest_videodomain);
        #execute query 
        $row_latest_videodomain->execute();
        $stmt_sub_latest_videodomain = $row_latest_videodomain->get_result();
        $rowSub_latest_videodomain = $stmt_sub_latest_videodomain->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videodomain = date('Y-m-d');
        $cur_date_videodomain = "SELECT ad_domain_date FROM mcm_domainwise_video_report_demo where ad_domain_date='$cudate_videodomain'";
        $row_cur_videodomain = $this->conn->prepare($cur_date_videodomain);
        #execute query 
        $row_cur_videodomain->execute();
        $stmt_sub_cur_videodomain = $row_cur_videodomain->get_result();
        $rowSub_cur_videodomain = $stmt_sub_cur_videodomain->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videodomain)){
        #update query
        $query_update_videodomain = "UPDATE mcm_domainwise_video_report_demo set ad_domain_date=DATE_ADD('$rowSub_latest_videodomain[ad_domain_date]', INTERVAL 1 DAY) where ad_domain_date='$rowSub_videodomain[ad_domain_date]'";
        #prepare query statement
        $stmt_date_videodomain = $this->conn->prepare($query_update_videodomain);
        if($stmt_date_videodomain->execute()){
            return true;
        }

        }
    }
    public function updateCronvideodevice(){
        $last_videodevice = 'SELECT ad_device_date FROM `mcm_devicecategory_video_report_demo` ORDER BY ad_device_date ASC LIMIT 1';
        $row_last_videodevice = $this->conn->prepare($last_videodevice);
        #execute query 
        $row_last_videodevice->execute();
        $stmt_sub_videodevice = $row_last_videodevice->get_result();
        $rowSub_videodevice = $stmt_sub_videodevice->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videodevice = 'SELECT ad_device_date FROM `mcm_devicecategory_video_report_demo` ORDER BY ad_device_date DESC LIMIT 1';
        $row_latest_videodevice = $this->conn->prepare($latest_videodevice);
        #execute query 
        $row_latest_videodevice->execute();
        $stmt_sub_latest_videodevice = $row_latest_videodevice->get_result();
        $rowSub_latest_videodevice = $stmt_sub_latest_videodevice->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videodevice = date('Y-m-d');
        $cur_date_videodevice = "SELECT ad_device_date FROM mcm_devicecategory_video_report_demo where ad_device_date='$cudate_videodevice'";
        $row_cur_videodevice = $this->conn->prepare($cur_date_videodevice);
        #execute query 
        $row_cur_videodevice->execute();
        $stmt_sub_cur_videodevice = $row_cur_videodevice->get_result();
        $rowSub_cur_videodevice = $stmt_sub_cur_videodevice->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videodevice)){
        #update query
        $query_update_videodevice = "UPDATE mcm_devicecategory_video_report_demo set ad_device_date=DATE_ADD('$rowSub_latest_videodevice[ad_device_date]', INTERVAL 1 DAY) where ad_device_date='$rowSub_videodevice[ad_device_date]'";
        #prepare query statement
        $stmt_date_videodevice = $this->conn->prepare($query_update_videodevice);
        if($stmt_date_videodevice->execute()){
            return true;
        }

        }
    }
    public function updateCronvideocoun(){
        $last_videocoun = 'SELECT ad_coun_date FROM `mcm_countrywise_video_report_demo` ORDER BY ad_coun_date ASC LIMIT 1';
        $row_last_videocoun = $this->conn->prepare($last_videocoun);
        #execute query 
        $row_last_videocoun->execute();
        $stmt_sub_videocoun = $row_last_videocoun->get_result();
        $rowSub_videocoun = $stmt_sub_videocoun->fetch_array(MYSQLI_ASSOC);//15 oct

        $latest_videocoun = 'SELECT ad_coun_date FROM `mcm_countrywise_video_report_demo` ORDER BY ad_coun_date DESC LIMIT 1';
        $row_latest_videocoun = $this->conn->prepare($latest_videocoun);
        #execute query 
        $row_latest_videocoun->execute();
        $stmt_sub_latest_videocoun = $row_latest_videocoun->get_result();
        $rowSub_latest_videocoun = $stmt_sub_latest_videocoun->fetch_array(MYSQLI_ASSOC); //13 jan

        $cudate_videocoun = date('Y-m-d');
        $cur_date_videocoun = "SELECT ad_coun_date FROM mcm_countrywise_video_report_demo where ad_coun_date='$cudate_videocoun'";
        $row_cur_videocoun = $this->conn->prepare($cur_date_videocoun);
        #execute query 
        $row_cur_videocoun->execute();
        $stmt_sub_cur_videocoun = $row_cur_videocoun->get_result();
        $rowSub_cur_videocoun = $stmt_sub_cur_videocoun->fetch_array(MYSQLI_ASSOC); //17 jan
        if(empty($rowSub_cur_videocoun)){
        #update query
        $query_update_videocoun = "UPDATE mcm_countrywise_video_report_demo set ad_coun_date=DATE_ADD('$rowSub_latest_videocoun[ad_coun_date]', INTERVAL 1 DAY) where ad_coun_date='$rowSub_videocoun[ad_coun_date]'";
        #prepare query statement
        $stmt_date_videocoun = $this->conn->prepare($query_update_videocoun);
        if($stmt_date_videocoun->execute()){
            return true;
        }

        }
    }
     #this month and last month header revnue
    public function thisMonth(){
        #this month earnings
       $queryFetch = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_display_share FROM '.$this->table_mcm_dis.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.ad_domain_date >= (LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 MONTH) AND ar.ad_domain_date <  (LAST_DAY(NOW()) + INTERVAL 1 DAY) group by ar.child_net_code';

       $row = $this->conn->prepare($queryFetch);
       $row->execute();
       $stmt_result = $row->get_result();
       $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
     
        foreach($resp as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_dis"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_display_share'] !=0){
                   $cmsShare = $value['pub_display_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set this_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_dis"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                if($value['pub_display_share'] !=0){
                   $cmsShare = $value['pub_display_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(this_month, pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_dis")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }

        return true;
    }
    
     #last month update
    public function lastMonth(){
        #last month earnings
       $queryFetchL = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_display_share FROM '.$this->table_mcm_dis.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE YEAR(ar.ad_domain_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
            AND MONTH(ar.ad_domain_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) group by ar.child_net_code';

       $rowL = $this->conn->prepare($queryFetchL);
       $rowL->execute();
       $stmt_resultL = $rowL->get_result();
       $respL = $stmt_resultL->fetch_all(MYSQLI_ASSOC);

        foreach($respL as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_dis"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_display_share'] !=0){
                   $cmsShare = $value['pub_display_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set last_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_dis"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                 if($value['pub_display_share'] !=0){
                   $cmsShare = $value['pub_display_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(last_month,pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_dis")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }
           return true;
    }
    #video header revenue
    public function thisMonthvid(){
        #this month earnings
       $queryFetch = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_video_share FROM '.$this->table_mcm_video.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.ad_domain_date >= (LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 MONTH) AND ar.ad_domain_date <  (LAST_DAY(NOW()) + INTERVAL 1 DAY) group by ar.child_net_code';

       $row = $this->conn->prepare($queryFetch);
       $row->execute();
       $stmt_result = $row->get_result();
       $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
     
        foreach($resp as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_vid"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_video_share'] !=0){
                   $cmsShare = $value['pub_video_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set this_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_vid"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                if($value['pub_video_share'] !=0){
                   $cmsShare = $value['pub_video_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(this_month, pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_vid")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }

        return true;
    }
    
     #last month update
    public function lastMonthvid(){
        #last month earnings
       $queryFetchL = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_video_share FROM '.$this->table_mcm_video.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE YEAR(ar.ad_domain_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
            AND MONTH(ar.ad_domain_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) group by ar.child_net_code';

       $rowL = $this->conn->prepare($queryFetchL);
       $rowL->execute();
       $stmt_resultL = $rowL->get_result();
       $respL = $stmt_resultL->fetch_all(MYSQLI_ASSOC);

        foreach($respL as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_vid"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_video_share'] !=0){
                   $cmsShare = $value['pub_video_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set last_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_vid"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                 if($value['pub_video_share'] !=0){
                   $cmsShare = $value['pub_video_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(last_month,pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_vid")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }
           return true;
    }
    #App header revenue
    public function thisMonthapp(){
        #this month earnings
       $queryFetch = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_app_share FROM '.$this->table_mcm_app.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.ad_domain_date >= (LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 MONTH) AND ar.ad_domain_date <  (LAST_DAY(NOW()) + INTERVAL 1 DAY) group by ar.child_net_code';

       $row = $this->conn->prepare($queryFetch);
       $row->execute();
       $stmt_result = $row->get_result();
       $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
     
        foreach($resp as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_app"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_app_share'] !=0){
                   $cmsShare = $value['pub_app_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set this_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_app"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                if($value['pub_app_share'] !=0){
                   $cmsShare = $value['pub_app_share']/100;}else{$cmsShare = 15/100;}
                $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(this_month, pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_app")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }

        return true;
    }
    
     #last month update
    public function lastMonthapp(){
        #last month earnings
       $queryFetchL = 'SELECT sum(ar.mcm_earnings) as revenue,pm.pub_uniq_id,pm.pub_app_share FROM '.$this->table_mcm_app.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE YEAR(ar.ad_domain_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
            AND MONTH(ar.ad_domain_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) group by ar.child_net_code';

       $rowL = $this->conn->prepare($queryFetchL);
       $rowL->execute();
       $stmt_resultL = $rowL->get_result();
       $respL = $stmt_resultL->fetch_all(MYSQLI_ASSOC);

        foreach($respL as $value){
                $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_app"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 if($value['pub_app_share'] !=0){
                   $cmsShare = $value['pub_app_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                 $queryPubUp = 'update header_revenue set last_month = '.$revenue.' where pub_uniq_id="'.$value['pub_uniq_id'].'" AND type="mcm_app"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                 if($value['pub_app_share'] !=0){
                   $cmsShare = $value['pub_app_share']/100;}else{$cmsShare = 15/100;}
                   $revenue = round($value['revenue']-($value['revenue']*$cmsShare),2);
                $queryPubUp = 'INSERT INTO header_revenue(last_month,pub_uniq_id,type) VALUES ('.$revenue.',"'.$value['pub_uniq_id'].'","mcm_app")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
           }
           return true;
    }

    #Adsense this month earnings
    public function adsThisMonth(){
        $db_1month = date('Y-m-01');
        $Hour = date('G');
        if($Hour > 14){
              $db_date_now = date('Y-m-d', strtotime("- 1 days"));
            
            }else{
               $db_date_now = date('Y-m-d', strtotime("- 2 days"));
           
            }
            $command_lplistlastmonth = new MongoDB\Driver\Command([
                'aggregate' => 'adsense_daywise',
                'pipeline' => [
                    ['$match'=>['date'=>['$gte' =>$db_1month,'$lte' =>$db_date_now]]],
                                            ['$group' => ['_id'=>[
                            'clientid' => '$ad_client_id'
                        ],'total_earning' => ['$sum' => '$earnings']]]
                ],
                'cursor' => new stdClass,
                ]);


                $cursor_lplistlastmonth  = $this->connMongoDb->executeCommand('adsense_db',$command_lplistlastmonth);
                foreach ($cursor_lplistlastmonth as $val) 
                {

                    
                     
                    $lastmonth[$val->_id->clientid]=($val->total_earning > 0 ? $val->total_earning : 0 );
                    
                }

              
      foreach($lastmonth as $key => $valAds){

        $adsenseId = str_replace("ca-","",$key);

         $queryFetchId = 'SELECT distinct pub_uniq_id FROM `publisher_master` where pub_adsense_id ="'.trim($adsenseId).'"';

        $rowId = $this->conn->prepare($queryFetchId);
        $rowId->execute();
        $stmt_resultId = $rowId->get_result();
        $respId = $stmt_resultId->fetch_array(MYSQLI_ASSOC);
     
         $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$respId['pub_uniq_id'].'" AND type="adsense"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 $queryPubUp = 'update header_revenue set this_month = '.$valAds.' where pub_uniq_id="'.$respId['pub_uniq_id'].'" AND type="adsense"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                $queryPubUp = 'INSERT INTO header_revenue(this_month,pub_uniq_id,type) VALUES ('.$valAds.', "'.$respId['pub_uniq_id'].'","adsense")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
         
        } //main id loop end   
             
          return true;
    }

#Adsense last month earnings
    public function adsLastMonth(){
        $lastmonth_first = date('Y-m-d', strtotime("first day of -1 month"));
        $lastmonth_last = date('Y-m-d', strtotime("last day of -1 month"));
            $command_lplistlastmonth = new MongoDB\Driver\Command([
                'aggregate' => 'adsense_daywise',
                'pipeline' => [
                    ['$match'=>['date'=>['$gte' =>$lastmonth_first,'$lte' =>$lastmonth_last]]],
                                            ['$group' => ['_id'=>[
                            'clientid' => '$ad_client_id'
                        ],'total_earning' => ['$sum' => '$earnings']]]
                ],
                'cursor' => new stdClass,
                ]);


                $cursor_lplistlastmonth  = $this->connMongoDb->executeCommand('adsense_db',$command_lplistlastmonth);
                foreach ($cursor_lplistlastmonth as $val) 
                {
                  $lastmonth[$val->_id->clientid]=($val->total_earning > 0 ? $val->total_earning : 0 );
                    
                }

              
      foreach($lastmonth as $key => $valAds){

        $adsenseId = str_replace("ca-","",$key);

         $queryFetchId = 'SELECT distinct pub_uniq_id FROM `publisher_master` where pub_adsense_id ="'.trim($adsenseId).'"';

        $rowId = $this->conn->prepare($queryFetchId);
        $rowId->execute();
        $stmt_resultId = $rowId->get_result();
        $respId = $stmt_resultId->fetch_array(MYSQLI_ASSOC);
     
         $query = 'SELECT pub_uniq_id FROM header_revenue where pub_uniq_id="'.$respId['pub_uniq_id'].'" AND type="adsense"';

               $rowRe = $this->conn->prepare($query);
               $rowRe->execute();
               $stmt_resultRe = $rowRe->get_result();
               $rows = $stmt_resultRe->num_rows;
               if($rows > 0){
                 $queryPubUp = 'update header_revenue set last_month = '.$valAds.' where pub_uniq_id="'.$respId['pub_uniq_id'].'" AND type="adsense"';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }else{
                $queryPubUp = 'INSERT INTO header_revenue(last_month,pub_uniq_id,type) VALUES ('.$valAds.', "'.$respId['pub_uniq_id'].'","adsense")';
                    #prepare query
                    $stmt_pubU = $this->conn->prepare($queryPubUp);
                    #execute query
                    $stmt_pubU->execute();
               }
         
        } //main id loop end   
             
          return true;
    }
    public function adsenseDaywiseDemo(){
      $uniq_id ='UNIQ_DEMO_2019.adsense_daywise';
      $multi = false;
      $result = $this->demoCommon($uniq_id,$multi);
      return true;  
     }

   public function adsenseTypewiseDemo(){
      $uniq_id ='UNIQ_DEMO_2019.adsense_adtypewise';
      $multi = true;
      $result = $this->demoCommon($uniq_id,$multi);
      return true;  
   }
  public function adsenseDomainwiseDemo(){
      $uniq_id ='UNIQ_DEMO_2019.adsense_domainwise';
      $multi = true;
      $result = $this->demoCommon($uniq_id,$multi);
      return true;
   }

   public function adsenseDevicewiseDemo(){
      $uniq_id ='UNIQ_DEMO_2019.adsense_devicewise';
      $multi = true;
      $result = $this->demoCommon($uniq_id,$multi);
      return true;
   }
   public function hbDemo(){

    $uniq_id ='UNIQ_DEMO_2019.header_bidder';
    $multi = false;
    $result = $this->demoHbCommon($uniq_id,$multi);
    return true;
   }
   public function hbGeoDemo(){

    $uniq_id ='UNIQ_DEMO_2019.countrywise_hb';
    $multi = true;
    $result = $this->demoHbCommon($uniq_id,$multi);
    return true;    
   }
   public function directDealDemo(){

    $uniq_id ='UNIQ_DEMO_2019.DFP_directdeal';
    $multi = false;
    $result = $this->demoCommon($uniq_id,$multi);
    return true;    
   }
    public function directDealGeoDemo(){

    $uniq_id ='UNIQ_DEMO_2019.DFP_directdeal_countrywise';
    $multi = true;
    $result = $this->demoCommon($uniq_id,$multi);
    return true;    
   }
   public function demoCommon($uniq_id,$multi){
     
    $filter = [];
        $options = [
            "projection" => ["_id" => 1,"date" =>1],
            'sort' => ['date' => 1],
            'limit' => 1,

        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor_lplist = $this->connMongoDb->executeQuery($uniq_id, $query);
        $first_date =  $cursor_lplist->toArray();

         #latest date get
         $options1 = [
            "projection" => ["_id" => 1,"date" =>1],
            'sort' => ['date' => -1],
            'limit' => 1,

        ];
        $queryLatest = new MongoDB\Driver\Query($filter, $options1);
        $cursor_latest = $this->connMongoDb->executeQuery($uniq_id, $queryLatest);
        $latest_date =  $cursor_latest->toArray();

        $cudate = date('Y-m-d');
         #curent date check
        $where      = ['date'=>$cudate];
        $queryCur = new MongoDB\Driver\Query($where);
        $result = $this->connMongoDb->executeQuery($uniq_id, $queryCur);
        $cur_date =  $result->toArray();

        if(empty($cur_date)){
             $new_date = date('Y-m-d', strtotime($latest_date[0]->date . ' +1 day'));

            $row_update = new MongoDB\Driver\BulkWrite();
            $row_update->update(
                    ['date' => $first_date[0]->date],
                    ['$set' => ['date' => $new_date]],
                    ['multi' => $multi]
                    
                );
            $resultUpdate = $this->connMongoDb->executeBulkWrite($uniq_id, $row_update);
            if($resultUpdate){
                return true;
            }
          }
   }
   public function demoHbCommon($uniq_id,$multi){
     
    $filter = [];
        $options = [
            "projection" => ["_id" => 1,"DATE" =>1],
            'sort' => ['DATE' => 1],
            'limit' => 1,

        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor_lplist = $this->connMongoDb->executeQuery($uniq_id, $query);
        $first_date =  $cursor_lplist->toArray();
         #latest date get
         $options1 = [
            "projection" => ["_id" => 1,"DATE" =>1],
            'sort' => ['DATE' => -1],
            'limit' => 1,

        ];
        $queryLatest = new MongoDB\Driver\Query($filter, $options1);
        $cursor_latest = $this->connMongoDb->executeQuery($uniq_id, $queryLatest);
        $latest_date =  $cursor_latest->toArray();

        $cudate = date('Y-m-d');
         #curent date check
        $where      = ['DATE'=>$cudate];
        $queryCur = new MongoDB\Driver\Query($where);
        $result = $this->connMongoDb->executeQuery($uniq_id, $queryCur);
        $cur_date =  $result->toArray();

        if(empty($cur_date)){
             $new_date = date('Y-m-d', strtotime($latest_date[0]->DATE . ' +1 day'));

            $row_update = new MongoDB\Driver\BulkWrite();
            $row_update->update(
                    ['DATE' => $first_date[0]->DATE],
                    ['$set' => ['DATE' => $new_date]],
                    ['multi' => $multi]
                    
                );
            $resultUpdate = $this->connMongoDb->executeBulkWrite($uniq_id, $row_update);
            if($resultUpdate){
                return true;
            }
          }
   }

}
?>