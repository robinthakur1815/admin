<?php
#Author BY AD
class OBM {

 #database connection and table name
    private $conn;
    private $table_services="publisher_services";
    private $table_user="users";
    private $table_master="publisher_master";
    private $table_web="publishers_website";
    private $table_app="publishers_app";
    

    #object properties
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
    public $pub_uniq_id;
    public $pub_email;
    public $domain_id;
    public $app_id;
    public $vertical;
    public $vertical2;
    public $app_status;
   
    

    
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    // public function getMemCache(){
    //   $memtest = new Memcached();
    //   $memtest->addServer("localhost", 11211);
    //   return $memtest;
    // }
    
    #get overview publisher data
    public function dashboardData(){
      
      $queryFetch = 'SELECT sum(IF(service_id=2,1,0)) as adx,sum(IF(service_id=3,1,0)) as adsense,sum(IF(service_id=5,1,0)) as cyberads,sum(IF(service_id=7,1,0)) as keyinsights,sum(IF(service_id=8,1,0)) as traffic FROM '.$this->table_services.' WHERE status=1';
      $queryActive = 'SELECT sum(IF(user_flag=0,1,0)) as active,sum(IF(user_flag=1,1,0)) as inactive FROM '.$this->table_user.'';
      $querytabular = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_email,date(pm.created_at) as onboarddate,GROUP_CONCAT( DISTINCT s.service_name) as services,pm.pub_display_share,pm.pub_video_share,pm.pub_app_share,pm.pub_adsense_share,pm.pub_cyberads_share,pm.pub_keyins_share,pm.mcm_status  FROM '.$this->table_master.' as pm inner join publisher_services ps ON ps.uniq_id = pm.pub_uniq_id INNER join services s ON s.serv_id = ps.service_id WHERE  ps.status=1 and s.serv_id NOT IN (1,4,6,9) AND pm.mcm_status="Approved" GROUP by ps.uniq_id';


      $row = $this->conn->prepare($queryFetch);
      $active = $this->conn->prepare($queryActive);
      $pubdash = $this->conn->prepare($querytabular);
      
      $row->execute();
      $stmt_result = $row->get_result();
      $resp1 = $stmt_result->fetch_array(MYSQLI_ASSOC);

      $active->execute();
      $stmt_result2 = $active->get_result();
      $resp2 = $stmt_result2->fetch_array(MYSQLI_ASSOC);
      
      $pubdash->execute();
      $stmt_result3 = $pubdash->get_result();
      $resp3 = $stmt_result3->fetch_all(MYSQLI_ASSOC);
     

      return array($resp1,$resp2,$resp3);
    
      

    }
      
     public function dashboardDataServ(){

      $querytabular = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_email,date(pm.created_at) as onboarddate, s.service_name as services,pm.pub_display_share,pm.pub_video_share,pm.pub_app_share,pm.pub_adsense_share,pm.pub_cyberads_share,pm.pub_keyins_share  FROM '.$this->table_master.' as pm inner join publisher_services ps ON ps.uniq_id = pm.pub_uniq_id INNER join services s ON s.serv_id = ps.service_id WHERE  s.serv_id ='.$this->serv_id.' and ps.status=1';
      $pubdash = $this->conn->prepare($querytabular);
      $pubdash->execute();
      $stmt_result3 = $pubdash->get_result();
      $resp3 = $stmt_result3->fetch_all(MYSQLI_ASSOC);
       return $resp3;
     } 

     public function adManagerData(){
     $querytabular = 'SELECT CONCAT(pub_fname," ",IFNULL(pub_lname,"")) as name,pub_email,date(created_at) as onboarddate,company_id, child_net_code,mcm_status,mcm_nonmcm_status,pub_id FROM '.$this->table_master.'';
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
        $queryForm = 'SELECT pm.pub_email,u.contact,pm.pub_type,pm.pub_org_type,pm.pub_company, pm.total_domain_mang,pm.edit_team_size,pm.direct_sale,pm.adx_for_display,pm.adx_for_video,pm.adx_for_app,pm.pub_display_share,pm.pub_video_share,pm.pub_app_share,pm.pub_adsense_id,pm.pub_adsense_share,pm.pub_analytics_id,pm.remark,pm.pub_uniq_id,pm.sal_id,pm.channel_id,pm.email_status FROM '.$this->table_master.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email WHERE pm.pub_id='.$this->pub_id.'';
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

     public function salesTeam(){
          $querySales = 'SELECT sal_id,sal_name from sales_team where sal_status="Y"';
          $salesR = $this->conn->prepare($querySales);
          $salesR->execute();
          $stmt_result_sales = $salesR->get_result();
          $respSal = $stmt_result_sales->fetch_all(MYSQLI_ASSOC);
          return $respSal;
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

       $queryPubUp = 'UPDATE '.$this->table_master.' SET `pub_type`="'.$this->pub_type.'",`pub_org_type`="'.$this->org_type.'",`pub_company`="'.$this->org_name.'",`total_domain_mang`="'.$this->domain_managed.'",`edit_team_size`="'.$this->team_size.'",`direct_sale`="'.$this->direct_sales.'",`adx_for_display`="'.$this->adx_for_display.'",`adx_for_video`="'.$this->adx_for_video.'",`adx_for_app`="'.$this->adx_for_app.'",`pub_display_share`="'.$this->display_share.'",`pub_video_share`="'.$this->video_share.'",`pub_app_share`="'.$this->app_share.'",`pub_adsense_id`="'.$this->adsense_id.'",`pub_adsense_share`="'.$this->adsense_share.'",`pub_analytics_id`="'.$this->analytics_id.'",`sal_id`="'.$this->sales_id.'",`channel_id`="'.$this->channel_id.'",`remark`="'.$this->remark.'" WHERE pub_id="'.$this->pub_id.'"';

        #prepare query
        $stmt_pubU = $this->conn->prepare($queryPubUp);
        #execute query
        $stmt_pubU->execute();


        #Refer details
        if($this->refer == "Y"){
            $queryRe = 'SELECT pub_uniq_id FROM '.$this->table_master.' WHERE pub_email="'.$this->refer_email.'"';
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
          $queryDomain = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pub_email,date(pm.created_at) as onboarddate,pm.company_id,pm.child_net_code,pw.site_id,pw.web_name,pw.site_id,pw.web_status,pw.id as domain_id,pm.mcm_nonmcm_status,pm.pub_id from '.$this->table_web.' as pw join '.$this->table_master.' as pm on pm.pub_id=pw.pub_id';
          $domain = $this->conn->prepare($queryDomain);
          $domain->execute();
          $stmt_result_domain = $domain->get_result();
          $respDomain = $stmt_result_domain->fetch_all(MYSQLI_ASSOC);
          return $respDomain;

     }
   
   #Get AD manager Domain form data
      public function adDomainData(){
        $queryForm = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_email,pm.pub_type,pm.pub_org_type,pm.pub_company,web.web_name,web.web_traffic_source,web.web_primary_geo,web.web_inventory_qty,web.email_status,web.vertical,web.vertical2,web.web_analtics_id,u.contact,web.email_status,pm.mcm_nonmcm_status FROM '.$this->table_master.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email inner join '.$this->table_web.' as web on pm.pub_id=web.pub_id WHERE pm.pub_id='.$this->pub_id.' and web.id='.$this->domain_id.'';
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
           $subject="Auxo Update - Your domain is ready for monetization"; 
           $html = '<html><head>
                                <meta charset="utf-8" />
                                            <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                                            <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                                            <meta name="viewport" content="width=device-width, initial-scale=1" />
                                            <meta name="robots" content="noindex,nofollow" />
                                            <title>Auxo Update - Your domain is ready for monetization</title> 
                                            </head>
                                            <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
                                            <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
                                            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
                                            <tbody>
                                            <tr style="background: #D6C4FB !important;">
                                            <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
                                            </td>
                                            <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
                                            <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
                                            </td>-->
                                            </tr>
                                            </tbody>
                                            </table>
                                                <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;border: 5px solid #f2f2f0;">
                                                    <tbody>
                                                    <tr>
                                                        <td>
                                                        <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->pub_name.',</p>
                                            
                                                        <p style="font-size: 18px; line-height: 33px;">Congratulations! Your domain '.$this->web_name.' has been approved for GAM 360 monetization.</p>

                                                        <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >We will coordinate with you on the below Starting Step : </span></u>
                                                        <ul>
                                                            <li style="font-size: 18px; line-height: 33px;">Activate GPT tags that initiate monetization via GAM360.</li>
                                                            <li style="font-size: 18px; line-height: 33px;">Integrate our code in the footer that enables better tagging.</li>
                                                            <li style="font-size: 18px; line-height: 33px;">Set up your domain\'s programmatic demand stack.</li>
                                                        </ul>

                                                        <p style="font-size: 18px; line-height: 33px;">We will get things up and running shortly.</p>

                                                        <p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://www.safe.cybermediaservices.net/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Ads Newsletter</span></a>, or read our <a href="https://blog-safe.cybermediaservices.net/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>
                                                        <br>
                                            
                                                        <!--<p>
                                                        <a href="javascript: void(0);" style="display: inline-block;
                                                        padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
                                                        </p>-->
                                                            <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Stay Safe & Happy Earnings!</span><br>
                                                            <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                                                        </td>
                                                        </tr>
                                                    </tbody>
                                                    </table>
                                            
                                                <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">  
                                                <span style="position: relative; top: -5px;">
                                                    Visit us at
                                                    <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
                                                    <br/>
                                                    <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
                                                    </span>
                                                </span>
                                                </center>
                                            </center>
                                            </body>
                                            </html>'; 
               
               $mailer = $this->mailPub($html,$subject,$this->pub_email);
        }
       }else{
        if($this->email_status == "N"){
            $subject="Welcome to Auxo Ads! Your domain is ready for monetization!";
            $html = '<html><head>
                                        <meta charset="utf-8" />
                                        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
                                        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                                        <meta name="viewport" content="width=device-width, initial-scale=1" />
                                        <meta name="robots" content="noindex,nofollow" />
                                        <title>Welcome to Auxo Ads! Your domain is ready for monetization</title> 
                                        </head>
                                        <body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
                                        <center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
                                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
                                        <tbody>
                                        <tr style="background: #D6C4FB !important;">
                                        <td style="padding: 10px!important; color: #000; text-align: center;">  <img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
                                        </td>
                                        <!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
                                        <p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
                                        </td>-->
                                        </tr>
                                        </tbody>
                                        </table>
                                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff;border: 5px solid #f2f2f0;">
                                        <tbody>
                                        <tr>
                                        <td>
                                        <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.$this->pub_name.',</p>
                                        <p style="font-size: 18px; line-height: 33px;">Domain : '.$this->web_name.' has been successfully on-boarded! </p>
                                        <u><span style="font-size: 18px; line-height: 33px; font-family: Noto Sans KR , sans-serif; margin-bottom: 20px;" >Our team will coordinate with you on the below starting step :</span></u>
                                        <ul>
                                        <li style="font-size: 18px; line-height: 33px;">Set up your custom ad stack that powers your programmatic monetization.</li>
                                        <li style="font-size: 18px; line-height: 33px;">Integrate our one-line code that enables faster ads.txt.</li>
                                        <li style="font-size: 18px; line-height: 33px;">Integrate our CDP solution that powers your data analytics.</li>
                                        </ul>
                                        <p style="font-size: 18px; line-height: 33px;">Furthermore, we will also provide a list of recommendations to get your optimizations started.</p>
                                        <p style="padding: 0; margin: 0; font-family: arial; font-size: 18px; line-height:25px; text-decoration:none;">Subscribe to <a href="https://www.safe.cybermediaservices.net/#newsletter" target="new"> <span style="color:#8d70fa;"> Auxo Ads Newsletter</span></a>, or read our <a href="https://blog-safe.cybermediaservices.net/" target="new"> <span style="color:#8d70fa;"> Blog. </span> </a></p>
                                        <br>
                                        <!--<p>
                                        <a href="javascript: void(0);" style="display: inline-block;padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
                                        </p>-->
                                        <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Stay Safe & Happy Earnings!</span><br>
                                        <span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                                        </td>
                                        </tr>
                                        </tbody>
                                        </table>
                                        <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">  
                                        <span style="position: relative; top: -5px;">Visit us at
                                        <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
                                            <br/>
                                            <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
                                            </span>
                                            </span>
                                            </center>
                                            </center>
                                            </body>
                                            </html>';

               $mailer = $this->mailPub($html,$subject,$this->pub_email);
        }
       } 
       
        return true;
      }

    #update App status
     public function appStatusData(){
       
        $queryAppUp = 'UPDATE '.$this->table_app.' SET `status`="'.$this->app_status.'" WHERE id='.$this->app_id.'';

        #prepare query
        $stmt_AppU = $this->conn->prepare($queryAppUp);
        #execute query
        if($stmt_AppU->execute()){

          return true; 
        }else{
            return false;
        }
     } 
      #get ad manager app listing
     public function adManagerAppData(){
          $queryApp = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pub_email,date(pm.created_at) as onboarddate,pm.company_id,pm.child_net_code,pw.app_name,pw.status,pw.id as app_id,pm.pub_id,pm.child_net_code from '.$this->table_app.' as pw join '.$this->table_master.' as pm on pm.pub_id=pw.pub_id';
          $app = $this->conn->prepare($queryApp);
          $app->execute();
          $stmt_result_app = $app->get_result();
          $respApp = $stmt_result_app->fetch_all(MYSQLI_ASSOC);
          return $respApp;

     }
     #Get AD manager APP form data
      public function adAppData(){
        $queryForm = 'SELECT pm.pub_email,pm.pub_type,pm.pub_org_type,pm.pub_company,app.app_name,app.traffic_source,app.primary_geo,app.inventory_qty,app.vertical,app.vertical2,u.contact FROM '.$this->table_master.' as pm left join '.$this->table_user.' as u on pm.pub_email=u.email inner join '.$this->table_app.' as app on pm.pub_id=app.pub_id WHERE pm.pub_id='.$this->pub_id.' and app.id='.$this->app_id.'';
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


     #send mail
  public function mailPub($html,$subject,$email){
    
    include_once('../mailerLib/class.phpmailer.php');
    $body_bank = $html;
            $mail  = new PHPMailer();
            $mail->IsSMTP();  
            $mail->Host       = "103.76.212.101";
            $mail->SMTPDebug  = 1; 
            $mail->SMTPAuth   = true;   
            $mail->Username   = 'noreply@cybermedia.co.in';
            $mail->Password   = 'K6Cx*5G%W8j';
            $mail->Port = "587";
            $mail->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
            $mail->Subject = $subject;           
            
            $mail->addAddress('abhijeet.kumar@cybermedia.co.in');
            //$mail->addAddress('ankurdu@cybermedia.co.in');
            $mail->isHTML(true);
            $mail->Body = $body_bank;
            if($mail->Send()){
              
                return true;
             }else{
                return false;
             }
  }

 }
?>