<?php
#Author BY AD
class Accmgr {

 #database connection and table name
    private $conn;
    private $table_user = "users";
    private $table_master = "publisher_master";
    private $table_mcm = "mcm_childnetcode_report";
    private $table_mcmadtype = "mcm_adtypewise_report";
    private $table_mcmadtypevideo = "mcm_adtypewise_video_report";
    private $table_mcmadtypeapp = "mcm_adtypewise_app_report";
    private $table_web = "publishers_website";
    private $table_app = "publishers_app";
    private $table_acc_mgr = "account_manager";
	private $table_vt = "website_vertical_details";
	private $table_bank = "bank_details";
	
	
	
	
	
    #object properties
    public $manager_id;
    public $date;
    public $pub_id;
    public $where;
    
	public $uniq_id;
    public $token;
	public $parner_uniq_id;
	public $pub_adsense_id;
	public $pub_display_share;
	public $pub_video_share;
	public $pub_app_share;
	
	
    #constructor with $db as database connection
    public function __construct($db,$dbMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $dbMongoDb;
    }
    // public function getMemCache(){
    //   $memtest = new Memcached();
    //   $memtest->addServer("localhost", 11211);
    //   return $memtest;
    // }
    
    #get count account manager publisher
    public function countPublisher(){
       $queryFetch = 'SELECT count(*) as totalAcc from '.$this->table_master.' WHERE child_net_code !="" AND child_net_code IS NOT NULL AND manager_id = '.$this->manager_id.'';
       $row = $this->conn->prepare($queryFetch);
       $row->execute();
       $stmt_result = $row->get_result();
       $resp3 = $stmt_result->fetch_array(MYSQLI_ASSOC);
       return $resp3; 
    }

    #get overview publisher top data for account manager
    public function topData(){

        #previous week unfilled
       $queryFetchUn = 'SELECT sum(ar.unfilled) as unfilled FROM mcm_childnetcode_report_unfilled as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.date >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND date < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND pm.manager_id = '.$this->manager_id.'';
       #previous week earnings
       $queryFetch = 'SELECT sum(ar.mcm_cnc_impr) as adimr, sum(ar.mcm_cnc_revenue) as revenue FROM '.$this->table_mcm.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.date >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND date < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND pm.manager_id = '.$this->manager_id.'';
      #curent week earnings 
      $queryCur = 'SELECT ar.mcm_cnc_revenue as revenue FROM '.$this->table_mcm.' as ar JOIN '.$this->table_master.' as pm ON pm.child_net_code = ar.child_net_code WHERE ar.date >= curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND date < curdate() - INTERVAL DAYOFWEEK(curdate())-11 DAY AND pm.manager_id = '.$this->manager_id.'';



      $row = $this->conn->prepare($queryFetch);
      $rowUn = $this->conn->prepare($queryFetchUn);
      $curWeek = $this->conn->prepare($queryCur);
      
      $rowUn->execute();
      $stmt_resultUn = $rowUn->get_result();
      $respUn = $stmt_resultUn->fetch_array(MYSQLI_ASSOC);
      
      $row->execute();
      $stmt_result = $row->get_result();
      $resp1 = $stmt_result->fetch_array(MYSQLI_ASSOC);

      $curWeek->execute();
      $stmt_result2 = $curWeek->get_result();
      $resp2 = $stmt_result2->fetch_array(MYSQLI_ASSOC);

      
       return array($resp1,$resp2,$respUn);
    
    }
    #fill rate
    public function fillRate(){
        
     #today display 
     $queryDisplay = 'SELECT  sum(dis.mcm_adreq) as adr,  sum(dis.mcm_impression) as adimr  FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND dis.ad_type_date < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND pm.manager_id = '.$this->manager_id.'';
      #today video 
      $queryVideo = 'SELECT  sum(dis.mcm_adreq) as adr, sum(dis.mcm_impression) as adimr FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND dis.ad_type_date < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND pm.manager_id = '.$this->manager_id.'';

       #app   
       $queryApp = 'SELECT sum(dis.mcm_adreq) as adr, sum(dis.mcm_impression) as adimr FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND dis.ad_type_date < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY AND pm.manager_id = '.$this->manager_id.'';


      $dis = $this->conn->prepare($queryDisplay);
      $video = $this->conn->prepare($queryVideo);
      $app = $this->conn->prepare($queryApp);
      
      $dis->execute();
      $stmt_result = $dis->get_result();
      $resp1 = $stmt_result->fetch_array(MYSQLI_ASSOC);

      $video->execute();
      $stmt_result2 = $video->get_result();
      $resp2 = $stmt_result2->fetch_array(MYSQLI_ASSOC);
      
      $app->execute();
      $stmt_result3 = $app->get_result();
      $resp3 = $stmt_result3->fetch_array(MYSQLI_ASSOC);


      $ad_request = $resp1['adr'] + $resp2['adr'] + $resp3['adr']; 
      $ad_imp = $resp1['adimr'] + $resp2['adimr'] + $resp3['adimr']; 
       
      $fillrate  = $ad_request > 0 ? number_format($ad_imp['adimr']/$ad_request*100,1) : '0.0';
      
     return $fillrate;

    }  
     #get adx reports
    public function adxReports(){
        
     #today display 
     $queryDisplay = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.'';
      #today video 
      $queryVideo = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.'';

       #app   
       $queryApp = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.'';


      $dis = $this->conn->prepare($queryDisplay);
      $video = $this->conn->prepare($queryVideo);
      $app = $this->conn->prepare($queryApp);
      
      $dis->execute();
      $stmt_result = $dis->get_result();
      $resp1 = $stmt_result->fetch_array(MYSQLI_ASSOC);

      $video->execute();
      $stmt_result2 = $video->get_result();
      $resp2 = $stmt_result2->fetch_array(MYSQLI_ASSOC);
      
      $app->execute();
      $stmt_result3 = $app->get_result();
      $resp3 = $stmt_result3->fetch_array(MYSQLI_ASSOC);


      $ad_request = $resp1['adr'] + $resp2['adr'] + $resp3['adr']; 
      $ad_imp = $resp1['adimr'] + $resp2['adimr'] + $resp3['adimr']; 
      $ad_match = $resp1['madr'] + $resp2['madr'] + $resp3['madr']; 
      $covg  = $ad_request > 0 ? number_format($ad_match*100/$ad_request,1):'0.0';
      $revenue = $resp1['revenue'] + $resp2['revenue'] + $resp3['revenue'];
      $ecpm  = $ad_imp > 0 ? number_format($revenue/$ad_imp*1000,2):'0.00';
    
      $arr = array("request"=>number_format($ad_request),"impression"=>number_format($ad_imp),"covg"=>$covg,"ecpm"=>$ecpm,"revenue"=>number_format($revenue,2));
     
      return $arr;

    }

    #get Top 20 Adx Publishers
    public function top20AdxData(){
        $queryDisplay = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue,CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.' group by dis.child_net_code ORDER BY CAST(sum(dis.mcm_earnings) AS DECIMAL(10,2)) DESC limit 0,20';
        $queryVideo = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue,CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.' group by dis.child_net_code ORDER BY CAST(sum(dis.mcm_earnings) AS DECIMAL(10,2)) DESC limit 0,20'; 
        
        $queryApp = 'SELECT sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue,CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$this->date.'" AND pm.manager_id = '.$this->manager_id.' group by dis.child_net_code ORDER BY CAST(sum(dis.mcm_earnings) AS DECIMAL(10,2)) DESC limit 0,20'; 
          $dis = $this->conn->prepare($queryDisplay);
          $video = $this->conn->prepare($queryVideo);
          $app = $this->conn->prepare($queryApp);
          
          $dis->execute();
          $stmt_result = $dis->get_result();
          $resp1 = $stmt_result->fetch_all(MYSQLI_ASSOC);

          $video->execute();
          $stmt_result2 = $video->get_result();
          $resp2 = $stmt_result2->fetch_all(MYSQLI_ASSOC);
          
          $app->execute();
          $stmt_result3 = $app->get_result();
          $resp3 = $stmt_result3->fetch_all(MYSQLI_ASSOC);

       $arr_com = array_merge($resp1,$resp2,$resp3);

       $resultTop20 = $this->array_sort($arr_com, 'revenue', SORT_DESC);
       $resultTop20 = array_slice($resultTop20, 0, 20);
     $top_array = array();      
     foreach($resultTop20 as $key => $value){

        @$top_array[$key]['name'] = $value['name'];
        @$top_array[$key]['adr'] = number_format($value['adr']);
        @$top_array[$key]['adimr'] = number_format($value['adimr']);
        @$top_array[$key]['covg'] = $value['madr'] > 0 ? number_format(($value['madr']*100)/$value['adr'],1) :'0.00';
        @$top_array[$key]['ecpm'] = $value['adimr'] > 0 ? number_format($value['revenue']/$value['adimr']*1000,2) : '0.00';
        @$top_array[$key]['revenue'] = number_format($value['revenue'],2);
     
     }
     $arr =array();
     foreach($top_array as $val){
        $arr[] = $val;
     } 
     
      return $arr;
       
       
    }
  #Get inactive adx publishers
    public function inactiveAdxData(){
         
        $query ='SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,IFNULL(web.web_name,"") as website,DATE_FORMAT(u.inactive_adx,"%e %b, %Y") as date  FROM ' . $this->table_master . ' as pm JOIN '.$this->table_user.' as u ON pm.pub_uniq_id = u.uniq_id left join (SELECT * from publishers_website as web1 ORDER BY web1.pub_id LIMIT 1) as web on pm.pub_id=web.pub_id where u.pub_adx_status=0 AND pm.manager_id = '.$this->manager_id.' and pm.child_net_code !="" AND pm.child_net_code IS NOT NULL AND u.inactive_adx IS NOT NULL';
        $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);


        foreach($resp as $ky =>$val){

          $rev30 = $this->last30Days($val['child_net_code']); 
          $rev90 = $this->last90Days($val['child_net_code']); 
          $resp[$ky]['earning30'] = $rev30['revenue'];
          $resp[$ky]['earning90'] = $rev90['revenue'];
            
        }
          return $resp;
    }
   #get last 30 days revenue
    public function last30Days($child_netcode){
         $query ='SELECT sum(mcm_cnc_revenue) as revenue FROM '.$this->table_mcm.' WHERE DATE(date) >= DATE(NOW() - INTERVAL 30 DAY) AND child_net_code = "'.$child_netcode.'"';
          $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
          return $resp;
    }
    #get last 90 days revenue
    public function last90Days($child_netcode){
         $query ='SELECT sum(mcm_cnc_revenue) as revenue FROM '.$this->table_mcm.' WHERE DATE(date) >= DATE(NOW() - INTERVAL 90 DAY) AND child_net_code = "'.$child_netcode.'"';
          $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
          return $resp;
    }
    #get ADX Movers
   public function moversAdxData(){
        $previousDate = date('Y-m-d', strtotime("-2 day", strtotime($this->date)));  
        $CurrentDate = date('Y-m-d', strtotime("-1 day", strtotime($this->date)));
        $preData = $this->preMovers($previousDate,$this->manager_id);
        $curData = $this->curMovers($CurrentDate,$this->manager_id);
        $impTop =array(); //impression
        $impDown =array();

        $revTop =array(); //revenue
        $revDown =array();
        $pageTop =array(); //PAGE
        $pageDown =array();
        foreach ($preData as $value) {

                 foreach ($curData as  $val) {


                    if($val['name'] == $value['name']){

                       if ($value['adimr'] > 1) {

                    $new = $val['adimr'] - $value['adimr'];
                    $newP = $new/$value['adimr']*100;
                    $newRev = $val['revenue'] - $value['revenue'];
                    $newReve = $newRev/$value['revenue']*100;
                    $newPage = $val['request'] - $value['request'];
                    $newPageV = $newPage/$value['request']*100;

                    if($new > 0){
                    if($new >= 5000)
                      {
                            $impTop[$value['name']]['name'] = $value['name'];
                            $impTop[$value['name']]['Impression'] = number_format($new);
                            $impTop[$value['name']]['Imp_vari'] = number_format($newP,2); //percent
                            $impTop[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                            $impTop[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                      }
                    }
                    else{
                    if($new <= -5000)
                      {
                            $impDown[$value['name']]['name'] = $value['name'];
                            $impDown[$value['name']]['Impression'] = number_format($new);
                            $impDown[$value['name']]['Imp_vari'] = number_format($newP,2); //percent
                            $impDown[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                            $impDown[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                      }

                    } 
                    if($newRev > 0)
                    {
                     if($newRev >= 10)
                       {
                            $revTop[$value['name']]['name'] = $value['name'];
                            $revTop[$value['name']]['Revenue'] = number_format($newRev,2);
                            $revTop[$value['name']]['rev_vari'] = number_format($newReve,2);
                            $revTop[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                            $revTop[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                      }
                    }else
                    {
                      if($newRev <= -10)
                       {
                            $revDown[$value['name']]['name'] = $value['name'];
                            $revDown[$value['name']]['Revenue'] = number_format($newRev,2);
                            $revDown[$value['name']]['rev_vari'] = number_format($newReve,2);
                            $revDown[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                            $revDown[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                        }
                    } 
                    if($newPage > 0){
                            if($newPage > 1000)
                            {
                                $pageTop[$value['name']]['name'] = $value['name'];
                                $pageTop[$value['name']]['Page'] = number_format($newPage);
                                $pageTop[$value['name']]['page_vari'] = number_format($newPageV,2);
                                $pageTop[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                                $pageTop[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                            }
                    }
                    else{
                       if ($newPage <= -1000)
                           {
                                $pageDown[$value['name']]['name'] = $value['name'];
                                $pageDown[$value['name']]['Page'] = number_format($newPage);
                                $pageDown[$value['name']]['page_vari'] = number_format($newPageV,2);
                                $pageDown[$value['name']]['eCPM'] = number_format($val['revenue']/$val['adimr']*1000,2); //ecpm
                                $pageDown[$value['name']]['covg'] = number_format($val['madr']*100/$val['request'],2); //covg
                           }
                        }  
                     } 
                    }
                 }

                }  //loop end

                //sorting
    function sortByOrder($a, $b) {
    return $b['Imp_vari'] - $a['Imp_vari'] ;
    }

    usort($impTop, 'sortByOrder');

    function sortByOrder1($a, $b) {
    return $a['Imp_vari'] - $b['Imp_vari'] ;
    }

    usort($impDown, 'sortByOrder1');
    //revenue sorting
    function sortByOrderR($a, $b) {
    return $b['rev_vari'] - $a['rev_vari'] ;
    }

    usort($revTop, 'sortByOrderR');

    function sortByOrderR1($a, $b) {
    return $a['rev_vari'] - $b['rev_vari'] ;
    }

    usort($revDown, 'sortByOrderR1');

    //page views sorting
    function sortByOrderP($a, $b) {
    return $b['page_vari'] - $a['page_vari'] ;
    }

    usort($pageTop, 'sortByOrderP');
    function sortByOrderP1($a, $b) {
    return $a['page_vari'] - $b['page_vari'] ;
    }

    usort($pageDown, 'sortByOrderP1');

    $newArrayT = array_slice($impTop, 0, 5, true);
    $newArrayD = array_slice($impDown, 0, 5, true);

    $newArrayTR = array_slice($revTop, 0, 5, true);
    $newArrayDR = array_slice($revDown, 0, 5, true);

    $newArrayTP = array_slice($pageTop, 0, 5, true);
    $newArrayDP = array_slice($pageDown, 0, 5, true);

     return array(
        'Impression Top'=>$newArrayT,
        'Impression Bottom'=>$newArrayD,
        'Revenue Top'=>$newArrayTR,
        'Revenue Bottom'=>$newArrayDR,
        'Page Views Top'=>$newArrayTP,
        'Page Views Bottom'=>$newArrayDP           
    );

   }
   #get movers previous day
   public function preMovers($previousDate,$manager_id){

     #for display
     $preQ_disp = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$previousDate.'" AND pm.manager_id = '.$manager_id.'';
    $preR_disp = $this->conn->prepare($preQ_disp);
    #execute query 
    $preR_disp->execute();
    $stmt_disp = $preR_disp->get_result();
    $rowP_disp = $stmt_disp->fetch_all(MYSQLI_ASSOC);

    #video
    $preQ1 = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$previousDate.'" AND pm.manager_id = '.$manager_id.'';
    $video = $this->conn->prepare($preQ1);
    #execute query 
    $video->execute();
    $stmt_disp1 = $video->get_result();
    $rowVideo = $stmt_disp1->fetch_all(MYSQLI_ASSOC);
   
    #app
    $preQ2 = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$previousDate.'" AND pm.manager_id = '.$manager_id.'';
    $app = $this->conn->prepare($preQ2);
    #execute query 
    $app->execute();
    $stmt_disp2 = $app->get_result();
    $rowApp = $stmt_disp2->fetch_all(MYSQLI_ASSOC);

    #merge array for display, video and app
    $result_total = array_merge($rowP_disp,$rowVideo,$rowApp);
    $adxPrevious = array();
    foreach ($result_total as $value_total) {
        $publisher_name = $value_total['name'];
        @$adxPrevious[$publisher_name]['name'] = $value_total['name'];
        @$adxPrevious[$publisher_name]['adimr'] += $value_total['adimr'];
        @$adxPrevious[$publisher_name]['request'] += $value_total['adr'];
        @$adxPrevious[$publisher_name]['revenue'] += $value_total['revenue'];
        @$adxPrevious[$publisher_name]['madr'] += $value_total['madr'];
    }
   
    return $adxPrevious;
   }
  #get movers cur day
   public function curMovers($CurrentDate,$manager_id){

     #for display
     $preQ_disp = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$CurrentDate.'" AND pm.manager_id = '.$manager_id.'';
    $preR_disp = $this->conn->prepare($preQ_disp);
    #execute query 
    $preR_disp->execute();
    $stmt_disp = $preR_disp->get_result();
    $rowP_disp = $stmt_disp->fetch_all(MYSQLI_ASSOC);

    #video
    $preQ1 = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$CurrentDate.'" AND pm.manager_id = '.$manager_id.'';
    $video = $this->conn->prepare($preQ1);
    #execute query 
    $video->execute();
    $stmt_disp1 = $video->get_result();
    $rowVideo = $stmt_disp1->fetch_all(MYSQLI_ASSOC);
   
    #app
    $preQ2 = 'SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where dis.ad_type_date = "'.$CurrentDate.'" AND pm.manager_id = '.$manager_id.'';
    $app = $this->conn->prepare($preQ2);
    #execute query 
    $app->execute();
    $stmt_disp2 = $app->get_result();
    $rowApp = $stmt_disp2->fetch_all(MYSQLI_ASSOC);

    #merge array for display, video and app
    $result_total = array_merge($rowP_disp,$rowVideo,$rowApp);
    $adxPrevious = array();
    foreach ($result_total as $value_total) {
        $publisher_name = $value_total['name'];
        @$adxPrevious[$publisher_name]['name'] = $value_total['name'];
        @$adxPrevious[$publisher_name]['adimr'] += $value_total['adimr'];
        @$adxPrevious[$publisher_name]['request'] += $value_total['adr'];
        @$adxPrevious[$publisher_name]['revenue'] += $value_total['revenue'];
        @$adxPrevious[$publisher_name]['madr'] += $value_total['madr'];
    }

    return $adxPrevious;
   }
    #get Adsense id active
   public function adsenseUniqid($id){
    $pubId ='select CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id as id from '.$this->table_master.' as pm join '.$this->table_user.' as ud on ud.uniq_id=pm.pub_uniq_id where ud.pub_adsense_status=1 AND pm.pub_adsense_id!="" AND pm.pub_adsense_id IS NOT NULL AND pm.manager_id='.$id.'';
    $pubIds = $this->conn->prepare($pubId);
    $pubIds->execute();
    $stmt_resultadsense = $pubIds->get_result();
    $pubIdresult = $stmt_resultadsense->fetch_all(MYSQLI_ASSOC);
    return $pubIdresult;
   }
    #get Top 20 Adsense Publishers
    public function top20AdsData(){
        
    $pubIdresult = $this->adsenseUniqid($this->manager_id);
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
                'name'=>substr($val->_id->pubid,3),
                'adr'=>$val->totalad_requests,
                'adimr'=>$val->totalad_imp,
                'madr'=>$val->totalmatchad_requests,
                'revenue'=>$val->total_earning       
                );
            }
        }

        $resultTop20 = $this->array_sort($resultTop20, 'revenue', SORT_DESC);
        $resultTop20 = array_slice($resultTop20, 0, 20);

        foreach($resultTop20 as $key => $value){

        @$top_array[$key]['name'] = $value['name'];
        @$top_array[$key]['adr'] = number_format($value['adr']);
        @$top_array[$key]['adimr'] = number_format($value['adimr']);
        @$top_array[$key]['covg'] = $value['madr'] > 0 ? number_format(($value['madr']*100)/$value['adr'],1) :'0.00';
        @$top_array[$key]['ecpm'] = $value['adimr'] > 0 ? number_format($value['revenue']/$value['adimr']*1000,2) : '0.00';
        @$top_array[$key]['revenue'] = number_format($value['revenue'],2);
     
         }
         foreach($top_array as $val){
            $arr[] = $val;
         } 
         
          return $arr;
        
    }

     #Get inactive adsense publishers
    public function inactiveAdsData(){

        $query ='SELECT CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name,pm.pub_uniq_id,DATE_FORMAT(u.inactive_adx,"%e %b, %Y") as date,web.web_name  FROM ' . $this->table_master . ' as pm JOIN '.$this->table_user.' as u ON pm.pub_uniq_id = u.uniq_id left join (SELECT * from publishers_website as web1 ORDER BY web1.pub_id LIMIT 1) as web on pm.pub_id=web.pub_id where u.pub_adsense_status=0 AND pm.manager_id = '.$this->manager_id.' and pm.pub_adsense_id !="" AND pm.pub_adsense_id IS NOT NULL AND inactive_adsense IS NOT NULL';
        $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);


        foreach($resp as $ky =>$val){

          $rev30 = $this->adslast30Days($val['pub_uniq_id']); 
          $rev90 = $this->adslast90Days($val['pub_uniq_id']); 
          $resp[$ky]['earning30'] = $rev30[0]->total_earning;
          $resp[$ky]['earning90'] = $rev90[0]->total_earning;
            
        }
          return $resp;
    }
   #get last 30 days adsense revenue
    public function adslast30Days($pub_uniq_id){

        $adsenseCurr = date('Y-m-d', strtotime("-2 day"));
        $L30daysDate = date('Y-m-d',  strtotime($adsenseCurr.'- 30 days'));
         #query
      $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_daywise',
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$L30daysDate,'$lte' =>$adsenseCurr]]],
        ['$group' => ['_id' => NULL,'total_earning' => ['$sum' => '$earnings']]],
        ['$sort'=>['_id'=>-1]]
        ],
        'cursor' => new stdClass,
    ]);
    
    $cursor_lplist = $this->connMongoDb->executeCommand($pub_uniq_id,$command_lplist);
    $resultEarn = $cursor_lplist->toArray();
          
     return $resultEarn;
    }
    #get last 90 days adsense revenue
    public function adslast90Days($pub_uniq_id){
         $adsenseCurr = date('Y-m-d', strtotime("-2 day"));
        $L90daysDate = date('Y-m-d',  strtotime($adsenseCurr.'- 90 days'));
         #query
      $command_lplist = new MongoDB\Driver\Command([
        'aggregate' => 'adsense_daywise',
        'pipeline' => [
            ['$match'=>['date'=>['$gte' =>$L90daysDate,'$lte' =>$adsenseCurr]]],
        ['$group' => ['_id' => NULL,'total_earning' => ['$sum' => '$earnings']]],
        ['$sort'=>['_id'=>-1]]
        ],
        'cursor' => new stdClass,
    ]);
    
    $cursor_lplist = $this->connMongoDb->executeCommand($pub_uniq_id,$command_lplist);
    $resultEarn = $cursor_lplist->toArray();
           
     return $resultEarn;
    }
   #get top 10 adsense movers
    public function moversAdsData(){
    $pubIdresult = $this->adsenseUniqid($this->manager_id);
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
               $arrTopAdS[$valuePre['id']]['Impression'] = number_format($newAdS);
               $arrTopAdS[$valuePre['id']]['Imp_vari'] = number_format($newPAdS,2); //percent
               $arrTopAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2);//ecpm
               $arrTopAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
                 }
                }
              else{
                  if($newAdS <= -5000){
               $arrDownAdS[$valuePre['id']]['id'] = $valuePre['id'];
               $arrDownAdS[$valuePre['id']]['Impression'] = number_format($newAdS);
               $arrDownAdS[$valuePre['id']]['Imp_vari'] = number_format($newPAdS,2); //percent
               $arrDownAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2); //ecpm
               $arrDownAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
                }
     
              } 
              if($newRevAdS > 0){
                  if($newRevAdS >= 10){
                  $arrTopRAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrTopRAdS[$valuePre['id']]['Revenue'] = number_format($newRevAdS,2);
                  $arrTopRAdS[$valuePre['id']]['rev_vari'] = number_format($newReveAdS);
                  $arrTopRAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2); //ecpm
                  $arrTopRAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
                }
              }else{
                  if($newRevAdS <= -10){
                   $arrDownRAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrDownRAdS[$valuePre['id']]['Revenue'] = number_format($newRevAdS,2);
                  $arrDownRAdS[$valuePre['id']]['rev_vari'] = number_format($newReveAdS,2);
                  $arrDownRAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2); //ecpm
                  $arrDownRAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
                 }
              } 
             if($newPageAdS > 0){
                 if($newPageAdS > 1000){
                   $arrTopPAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrTopPAdS[$valuePre['id']]['Page'] = number_format($newPageAdS);
                  $arrTopPAdS[$valuePre['id']]['page_vari'] = number_format($newPageVAdS,2);
                    $arrTopPAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2); //ecpm
                  $arrTopPAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
              }
             }else{
                 if ($newPageAdS <= -1000){
                  $arrDownPAdS[$valuePre['id']]['id'] = $valuePre['id'];
                   $arrDownPAdS[$valuePre['id']]['Page'] = number_format($newPageAdS);
                  $arrDownPAdS[$valuePre['id']]['page_vari'] = number_format($newPageVAdS,2);
                    $arrDownPAdS[$valuePre['id']]['eCPM'] = number_format($valCurr['adearn']/$valCurr['adimp']*1000,2); //ecpm
                  $arrDownPAdS[$valuePre['id']]['covg'] = number_format($valCurr['admadr']*100/$valCurr['adreq'],2); //covg
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
        
    
    return array(
        'Impression Top'=>$newArrayTAds,
        'Impression Down'=>$newArrayDAds,
        'Revenue Top'=>$newArrayTRAds,
        'Revenue Down'=>$newArrayDRAds,
        'Page Views Top'=>$newArrayTPAds,
        'Page Views Down'=>$newArrayDPAds           
    );
}
 #get performance without filter data
    public function topPerData(){
        $start = date('Y-m-d',strtotime('-7 days'));
        $end = date('Y-m-d', strtotime("-1 day"));
         #Date Array
        while (strtotime($start) <= strtotime($end))
        {
         
         $date_arr[] = date('Y-m-j', strtotime($start));
         $start = date ("Y-m-j", strtotime("+1 day", strtotime($start)));
        } 
        
         $query ='SELECT  dis.ad_type_date as date,sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtype . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where DATE(dis.ad_type_date) >= DATE(NOW() - INTERVAL 7 DAY) AND pm.manager_id = '.$this->manager_id.' GROUP by dis.ad_type_date';
          $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);

         $queryVideo ='SELECT  dis.ad_type_date as date,sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypevideo . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where DATE(dis.ad_type_date) >= DATE(NOW() - INTERVAL 7 DAY) AND pm.manager_id = '.$this->manager_id.' GROUP by dis.ad_type_date';
          $repVid = $this->conn->prepare($queryVideo);
          
          $repVid->execute();
          $stmt_result1 = $repVid->get_result();
          $respVid = $stmt_result1->fetch_all(MYSQLI_ASSOC);

          $queryApp ='SELECT  dis.ad_type_date as date,sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM ' . $this->table_mcmadtypeapp . ' as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where DATE(dis.ad_type_date) >= DATE(NOW() - INTERVAL 7 DAY) AND pm.manager_id = '.$this->manager_id.' GROUP by dis.ad_type_date';
          $repApp = $this->conn->prepare($queryVideo);
          
          $repApp->execute();
          $stmt_result2 = $repApp->get_result();
          $respApp = $stmt_result2->fetch_all(MYSQLI_ASSOC);
          // $Adx_revenue = $resp['revenue']+$respVid['revenue']+$respApp['revenue'];
          // $Adx_imp = $resp['adimr']+$respVid['adimr']+$respApp['adimr'];
     $arrMrge  = array_merge($resp,$respVid,$respApp);
      $total_array = array();
      $Adx_revenue = $Adx_imp = 0;          
      foreach($arrMrge as $value){

        $total_array[$value['date']]['date'] = date('j M, Y', strtotime($value['date']));
        $total_array[$value['date']]['adimr']+=$value['adimr'];
        $total_array[$value['date']]['adx_revenue']+=number_format($value['revenue'],2);
          $Adx_revenue += $value['revenue'];
          $Adx_imp += $value['adimr'];
      }
      $adxArr = array();
      $adxArr = $this->get_sum_index($total_array,$date_arr);
        
           
        #adsense
       $pubIdresult = $this->adsenseUniqid($this->manager_id);
       $total_ads = array();
       $ads_revenue =$ads_imp=0;
   foreach ($pubIdresult as $pubVal) {
        $dbId = $pubVal['id'];   
        $adsenseCurr = date('Y-m-d', strtotime("-2 day"));
        $L7daysDate = date('Y-m-d',  strtotime($adsenseCurr.'- 6 days'));
         #query
          $command_lplist = new MongoDB\Driver\Command([
            'aggregate' => 'adsense_daywise',
            'pipeline' => [
                ['$match'=>['date'=>['$gte' =>$L7daysDate,'$lte' =>$adsenseCurr]]],
            ['$group' => ['_id' => ['date' => '$date'],'totalad_imp' => ['$sum' => '$impressions'],'total_earning' => ['$sum' => '$earnings']]],
            ['$sort'=>['_id'=>-1]]
            ],
            'cursor' => new stdClass,
        ]);
        
        $cursor_lplist = $this->connMongoDb->executeCommand($dbId,$command_lplist);
        $resultAds = $cursor_lplist->toArray(); 
     
        foreach ($resultAds as $val) 
            {
                $dateindex = $val->_id->date;
             
              @$total_ads[$dateindex]['date'] = date('j M, Y', strtotime($val->_id->date));
              @$total_ads[$dateindex]['adimr']+= $val->totalad_imp;
              @$total_ads[$dateindex]['ads_revenue']+= number_format($val->total_earning,2);  
              $ads_revenue += $val->total_earning;
              $ads_imp += $val->totalad_imp;
            }
        }
        $adsArr = array();
        $adsArr = $this->get_sum_index_ads($total_ads,$date_arr);
        $finalArr = array();
        foreach($adxArr as $ky => $valDate){
            $date = $valDate['date'];
            $finalArr[$date]['date'] = $date;
            $finalArr[$date]['adx_earn'] = number_format($valDate['adx_revenue'],2);
            $finalArr[$date]['ads_earn'] = number_format($adsArr[$ky]['ads_revenue'],2);
            $finalArr[$date]['total_imp'] = number_format($valDate['adimr'] + $adsArr[$ky]['adimr']);
            $finalArr1[$date]['total_imp'] = $valDate['adimr'] + $adsArr[$ky]['adimr'];
            $finalArr1[$date]['est_revenue'] = $valDate['adx_revenue'] + $adsArr[$ky]['ads_revenue'];
            $finalArr[$date]['est_revenue'] = number_format($valDate['adx_revenue'] + $adsArr[$ky]['ads_revenue'],2);
            $finalArr[$date]['total_ecpm'] = $finalArr1[$date]['total_imp'] > 0 ? number_format($finalArr1[$date]['est_revenue']/$finalArr1[$date]['total_imp']*1000,2):'0.00';

        }
         $arrDate = array();
         foreach($finalArr as $val){
                $arrDate[] = $val;
             } 
      
         $total_imp = $Adx_imp + $ads_imp;
         $total_rev = $Adx_revenue + $ads_revenue;
         $total_ecpm = $total_imp > 0 ? number_format($total_rev/$total_imp*1000,2):'0.00';
          
          $arrDate[] =array("date"=>"Total","adx_earn"=>number_format($Adx_revenue,2),"ads_earn"=>number_format($ads_revenue,2),"total_imp"=>number_format($total_imp),"total_ecpm"=>number_format($total_ecpm,2),"est_revenue"=>number_format($total_rev,2)); 

        
          return $arrDate;
    }

function get_sum_index($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'date'=> @$array_data[$date_value]['date'],
        'adimr'=> @$array_data[$date_value]['adimr'],
        'adx_revenue'=> number_format(@$array_data[$date_value]['adx_revenue'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'date'=> date('j M, Y', strtotime($date_value)),
        'adimr'=>0,
        'adx_revenue'=> 0
        );
    }
}

    return $formatedarray;
}
function get_sum_index_ads($array_data,$array_fulldate)
{
 

foreach($array_fulldate as $date_value)
{
    if (in_array($date_value, array_keys($array_data)))
    { 
        $formatedarray[]=array(
        'date'=> @$array_data[$date_value]['date'],
        'adimr'=> @$array_data[$date_value]['adimr'],
        'ads_revenue'=> number_format(@$array_data[$date_value]['ads_revenue'],2)
        );
    }
    else
    {
        $formatedarray[]=array(
        'date'=> date('j M, Y', strtotime($date_value)),
        'adimr'=>0,
        'ads_revenue'=> 0
        );
    }
}

    return $formatedarray;
}

	#getting publisher name and website when click on filter performance overview
	public function getPub($accounts){
		if($accounts=="adx"){
			$query ='SELECT CONCAT(pub_fname," ",IFNULL(pub_lname,"")) as name,pub_id,child_net_code FROM ' . $this->table_master . ' where manager_id = '.$this->manager_id.' and child_net_code !="" AND child_net_code IS NOT NULL';
			$rep = $this->conn->prepare($query);

			$rep->execute();
			$stmt_result = $rep->get_result();
			$resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
			return $resp;
		}else if($accounts=="adsense"){
			$query ='select CONCAT(pm.pub_fname," ",IFNULL(pm.pub_lname,"")) as name, pm.pub_id, pm.pub_uniq_id as child_net_code, pm.pub_adsense_id from '.$this->table_master.' as pm join '.$this->table_user.' as ud on ud.uniq_id=pm.pub_uniq_id where ud.pub_adsense_status=1 AND pm.pub_adsense_id!="" AND pm.pub_adsense_id IS NOT NULL AND pm.pub_fname!="" AND pm.pub_fname IS NOT NULL AND pm.manager_id='.$this->manager_id.'';

			$rep = $this->conn->prepare($query);
			$rep->execute();
			$stmt_result = $rep->get_result();
			$resp = $stmt_result->fetch_all(MYSQLI_ASSOC);

			return $resp;
		}
	}

   public function getWeb(){
      $query ='SELECT id,web_name FROM ' . $this->table_web . ' where pub_id = '.$this->pub_id.'';
          $rep = $this->conn->prepare($query);
          
          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $queryApp ='SELECT id,app_name as web_name FROM ' . $this->table_app . ' where pub_id = '.$this->pub_id.'';
          $repApp = $this->conn->prepare($queryApp);
          
          $repApp->execute();
          $stmt_resultApp = $repApp->get_result();
          $respApp = $stmt_resultApp->fetch_all(MYSQLI_ASSOC);
          $resp = array_merge($resp,$respApp);  
          return $resp;
   }

	#get filter data reports
	public function filterData($accounts, $dbAdsenseId, $strtdate, $enddate){
		if($accounts=="adx"){
			#display 
			$queryDisplay = 'SELECT date(dis.ad_domain_date) as date, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM mcm_domainwise_report as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where pm.manager_id = '.$this->manager_id.' AND '.$this->where.' ORDER BY ad_domain_date';

			#video 
			$queryVideo = 'SELECT date(dis.ad_domain_date) as date, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM mcm_domainwise_video_report as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where pm.manager_id = '.$this->manager_id.' AND '.$this->where.' ORDER BY ad_domain_date';

			#app   
			$queryApp = 'SELECT date(dis.ad_domain_date) as date, sum(dis.mcm_clicks) as clicks, sum(dis.mcm_adreq) as adr, sum(dis.mcm_matchreq) as madr,  sum(dis.mcm_impression) as adimr, sum(dis.mcm_earnings) as revenue FROM mcm_domainwise_app_report as dis JOIN '.$this->table_master.' as pm ON pm.child_net_code = dis.child_net_code where pm.manager_id = '.$this->manager_id.' AND '.$this->where.' ORDER BY ad_domain_date';

			$dis = $this->conn->prepare($queryDisplay);
			$video = $this->conn->prepare($queryVideo);
			$app = $this->conn->prepare($queryApp);

			$dis->execute();
			$stmt_result = $dis->get_result();
			$resp1 = $stmt_result->fetch_all(MYSQLI_ASSOC);

			$video->execute();
			$stmt_result2 = $video->get_result();
			$resp2 = $stmt_result2->fetch_all(MYSQLI_ASSOC);

			$app->execute();
			$stmt_result3 = $app->get_result();
			$resp3 = $stmt_result3->fetch_all(MYSQLI_ASSOC);

			$arrMrge  = array_merge($resp1,$resp2,$resp3);

			foreach($arrMrge as $value){
				@$total_array[$value['date']]['date'] = date('j M, Y', strtotime($value['date']));
				@$total_array[$value['date']]['adr']+=$value['adr'];
				@$total_array[$value['date']]['adimr']+=$value['adimr'];
				@$total_array[$value['date']]['madr']+=$value['madr'];
				@$total_array[$value['date']]['clicks']+=$value['clicks'];
				@$total_array[$value['date']]['covg'] = $total_array[$value['date']]['madr'] > 0 ? number_format(($total_array[$value['date']]['madr']*100)/$total_array[$value['date']]['adr'],1) :'0.00';
				@$total_array[$value['date']]['ctr'] = $total_array[$value['date']]['adimr'] > 0 ? number_format($total_array[$value['date']]['clicks']/$total_array[$value['date']]['adimr']*100,1):'0.00';
				@$total_array[$value['date']]['revenue']+=number_format($value['revenue'],2);
				@$total_array[$value['date']]['ecpm'] = $total_array[$value['date']]['adimr'] > 0 ? number_format($total_array[$value['date']]['revenue']/$total_array[$value['date']]['adimr']*1000,2) : '0.00';
				@$total_array[$value['date']]['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);

				#total last row
				@$total_row['date'] = "Total";
				@$total_row['adr'] += $value['adr'];
				@$total_row['adimr'] += $value['adimr'];
				@$total_row['madr'] += $value['madr'];
				@$total_row['clicks'] += $value['clicks'];
				@$total_row['covg'] = $total_row['madr'] > 0 ? number_format(($total_row['madr']*100)/$total_row['adr'],1) :'0.00';
				@$total_row['ctr'] = $total_row['adimr'] > 0 ? number_format($total_row['clicks']/$total_row['adimr']*100,1):'0.00';
				@$total_row['revenue']+=number_format($value['revenue'],2);
				@$total_row['ecpm'] = $total_row['adimr'] > 0 ? number_format($total_row['revenue']/$total_row['adimr']*1000,2) : '0.00';
				@$total_row['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);
			}

			if(!empty($total_array)){
				$total_array[] = $total_row;
				foreach($total_array as $val){
					$arr[] = $val;
				}
			}else{
				$arr = array();
			}
		}else if($accounts=="adsense"){

			$command_lplist = new MongoDB\Driver\Command([
			'aggregate' => 'adsense_daywise',
			'pipeline' => [
				['$match'=>['date'=>['$gte' =>$strtdate,'$lte' =>$enddate,]]],
				['$group' => ['_id' => '$date', 'totalad_requests' => ['$sum' => '$ad_requests'], 'totalad_imp' => ['$sum' => '$impressions'], 'totalmatchad_requests' => ['$sum' => '$matched_request'], 'total_click' => ['$sum' => '$clicks'], 'total_earning' => ['$sum' => '$earnings'],'ctr' => ['$sum' => '$ad_requests_ctr'],'covg' => ['$sum' => '$ad_requests_coverage']]],
				['$sort'=>['_id'=>-1]]
			],
			'cursor' => new stdClass,
			]);
			$cursor_lplist = $this->connMongoDb->executeCommand($dbAdsenseId,$command_lplist);

			$adSenseResult = array();
			foreach($cursor_lplist as $val) {
				$adSenseResult[] = array(
					'date'=>$val->_id,
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
				@$total_array[$value['date']]['date'] = date('j M, Y', strtotime($value['date']));
				@$total_array[$value['date']]['adr']+=$value['adr'];
				@$total_array[$value['date']]['adimr']+=$value['adimr'];
				@$total_array[$value['date']]['madr']+=$value['madr'];
				@$total_array[$value['date']]['clicks']+=$value['clicks'];
				@$total_array[$value['date']]['covg'] = $total_array[$value['date']]['madr'] > 0 ? number_format(($total_array[$value['date']]['madr']*100)/$total_array[$value['date']]['adr'],1) :'0.00';
				@$total_array[$value['date']]['ctr'] = $total_array[$value['date']]['adimr'] > 0 ? number_format($total_array[$value['date']]['clicks']/$total_array[$value['date']]['adimr']*100,1):'0.00';
				@$total_array[$value['date']]['revenue']+=number_format($value['revenue'],2);
				@$total_array[$value['date']]['ecpm'] = $total_array[$value['date']]['adimr'] > 0 ? number_format($total_array[$value['date']]['revenue']/$total_array[$value['date']]['adimr']*1000,2) : '0.00';
				@$total_array[$value['date']]['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);

				#total last row
				@$total_row['date'] = "Total";
				@$total_row['adr'] += $value['adr'];
				@$total_row['adimr'] += $value['adimr'];
				@$total_row['madr'] += $value['madr'];
				@$total_row['clicks'] += $value['clicks'];
				@$total_row['covg'] = $total_row['madr'] > 0 ? number_format(($total_row['madr']*100)/$total_row['adr'],1) :'0.00';
				@$total_row['ctr'] = $total_row['adimr'] > 0 ? number_format($total_row['clicks']/$total_row['adimr']*100,1):'0.00';
				@$total_row['revenue']+=number_format($value['revenue'],2);
				@$total_row['ecpm'] = $total_row['adimr'] > 0 ? number_format($total_row['revenue']/$total_row['adimr']*1000,2) : '0.00';
				@$total_row['revenue_15']+=number_format(($value['revenue']-($value['revenue']*0.15)),2);
			}

			if(!empty($total_array)){
				$total_array[] = $total_row;
				foreach($total_array as $val){
					$arr[] = $val;
				}
			}else{
				$arr = array();
			}
		}
		return $arr;
	}

   public function array_sort($array, $on, $order=SORT_DESC){

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

   #get interval Date
    public function getIntervalDate($date_interval){
         $query ="SELECT ".$date_interval;
          $rep = $this->conn->prepare($query);

          $rep->execute();
          $stmt_result = $rep->get_result();
          $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
          return $resp;
    }
	
	
	
	
	
	#SY 
	
	public function getPartners(){
		
		#query for both Active and Inactive publisher 
        $queryFetch= 'SELECT pm.pub_id, pm.pub_uniq_id, CONCAT(pm.pub_fname, " ", IFNULL(pm.pub_lname,"")) as pub_name, pm.pub_acc_name, pm.pub_acc_new_name,pm.pub_adsense_id, pm.pub_email, pm.adx_for_display, pm.adx_for_video, pm.adx_for_app,pb.status_bank,pb.aadhaar_card_file ,pb.pan_card_file,pb.incorp_certificate_fille ,pb.cancel_check_file,pb.gst_certificate,pm.created_at,ud.pub_adx_status,ud.pub_adsense_status,ud.user_flag,pm.child_net_code,pm.network_flag from ' . $this->table_master . ' as pm LEFT JOIN ' . $this->table_bank . ' as pb ON pm.pub_uniq_id = pb.uniq_id JOIN ' . $this->table_user . ' as ud ON pm.pub_email=ud.email WHERE pm.manager_id ="' . $this->manager_id . '" AND pm.pub_email!="" GROUP BY pm.pub_email ORDER BY pm.pub_fname ASC';
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        return $row;
    }
	public function getPartnersProfile(){
        $profileFetch= 'SELECT pm.pub_id,pm.pub_uniq_id,pm.pub_fname,pm.pub_lname,pm.pub_acc_name,pm.pub_adsense_id, pm.pub_email,pm.pub_analytics_id,pm.pub_company,ud.contact,pm.pub_display_share,pm.pub_video_share,pm.pub_app_share,pm.adx_for_display,pm.adx_for_video,pm.adx_for_app from ' . $this->table_master . ' as pm JOIN ' . $this->table_user . ' as ud ON ud.email=pm.pub_email WHERE pm.pub_id="' . $this->pub_id . '" AND pm.manager_id="' . $this->manager_id . '"';
        #prepare query
        $pubProf = $this->conn->prepare($profileFetch);
		$pubProf->execute();
		$stmt_result = $pubProf->get_result();
		$resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
		if(!empty($resp)){
			$parner_uniq_id = $resp['pub_uniq_id'];
			$domFetch = 'SELECT web_name from ' . $this->table_web . '  WHERE pub_id="' . $this->pub_id . '" AND pub_uniq_id="' . $parner_uniq_id . '" ORDER BY web_name';
			#prepare query
			$pubDom = $this->conn->prepare($domFetch);
			$pubDom->execute();
			$stmt_res = $pubDom->get_result();
			$domain_list = $stmt_res->fetch_all(MYSQLI_ASSOC); 
		}else{
			$domain_list = array(); 
		}
		return array("profileData"=>$resp,"DomainData"=>$domain_list);
    }
	public function getPartnersDomainList(){
		$checkPub = 'SELECT pub_id from ' . $this->table_master . '  WHERE manager_id="' . $this->manager_id . '" AND pub_uniq_id="' . $this->parner_uniq_id . '"';
		$pubProf = $this->conn->prepare($checkPub);
		$pubProf->execute();
		$stmt_result = $pubProf->get_result();
		$resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
		if(!empty($resp)){
			$pub_id = $resp['pub_id'];
			$domFetch = 'SELECT id,web_name,status_web from ' . $this->table_web . '  WHERE pub_id="' . $pub_id . '" AND pub_uniq_id="' . $this->parner_uniq_id . '" ORDER BY web_name';
			#prepare query
			$pubDom = $this->conn->prepare($domFetch);
			$pubDom->execute();
			return $pubDom;
		}else{
			return false;
		}
		
    }
	public function getPartnersAppList(){
		$checkPub = 'SELECT pub_id from ' . $this->table_master . '  WHERE manager_id="' . $this->manager_id . '" AND pub_uniq_id="' . $this->parner_uniq_id . '"';
		$pubProf = $this->conn->prepare($checkPub);
		$pubProf->execute();
		$stmt_result = $pubProf->get_result();
		$resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
		if(!empty($resp)){
			$pub_id = $resp['pub_id'];
			$appFetch = 'SELECT id,app_name,status from ' . $this->table_app . '  WHERE pub_id="' . $pub_id . '" AND pub_uniq_id="' . $this->parner_uniq_id . '" ORDER BY app_name';
			#prepare query
			$pubApp = $this->conn->prepare($appFetch);
			$pubApp->execute();
			return $pubApp;
		}else{
			return false;
		}
		
    }
	public function getPartnersBankdetail(){
		$checkPub = 'SELECT pub_id from ' . $this->table_master . '  WHERE manager_id="' . $this->manager_id . '" AND pub_uniq_id="' . $this->parner_uniq_id . '"';
		$pubProf = $this->conn->prepare($checkPub);
		$pubProf->execute();
		$stmt_result = $pubProf->get_result();
		$resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
		if(!empty($resp)){
			$queryFetch= 'SELECT bd.*,pm.country_code_id FROM '.$this->table_bank.' bd join '.$this->table_master.' pm on pm.pub_uniq_id = bd.uniq_id AND bd.uniq_id="'.$this->parner_uniq_id.'" GROUP by bd.uniq_id';
			#prepare query
			$row = $this->conn->prepare($queryFetch);
			#execute query 
			$row->execute();
			
			return $row;
		}else{
			return false;
		}
    }
	public function getGenerateTags(){
        $profileFetch= 'SELECT pm.pub_id,pm.pub_uniq_id,pm.pub_fname,pm.pub_lname,pm.pub_acc_name,pm.adx_for_display,pm.adx_for_video,pm.adx_for_app,pm.network_flag,LEFT(pm.pub_fname,2) as prefix from ' . $this->table_master . ' as pm JOIN ' . $this->table_user . ' as ud ON ud.email=pm.pub_email WHERE pm.pub_id="' . $this->pub_id . '" AND pm.manager_id="' . $this->manager_id . '"';
        #prepare query
        $pubProf = $this->conn->prepare($profileFetch);
		$pubProf->execute();
		return $pubProf;
    }
	public function updateProfile(){
		$con = "manager_id ='".$this->manager_id."'";
		if($this->pub_adsense_id == null || $this->pub_adsense_id == 'null'){
			$con .= ",pub_adsense_id =NULL";
		}else{
			$con .= ",pub_adsense_id ='".$this->pub_adsense_id."'";
		}
		if($this->pub_display_share == null || $this->pub_display_share == 'null'){
			$con .= "";
		}else{
			$con .= ",pub_display_share ='".$this->pub_display_share."'";
		}
		if($this->pub_video_share == null || $this->pub_video_share == 'null'){
			$con .= "";
		}else{
			$con .= ",pub_video_share ='".$this->pub_video_share."'";
		}
		if($this->pub_app_share == null || $this->pub_app_share == 'null'){
			$con .= "";
		}else{
			$con .= ",pub_app_share ='".$this->pub_app_share."'";
		}
		
		$query = "UPDATE " . $this->table_master . " SET ".$con." WHERE pub_id = '".$this->pub_id."' AND pub_uniq_id= '".$this->parner_uniq_id."' AND manager_id= '".$this->manager_id."'";
		
		// echo $query ;die;
        #prepare query statement
        $stmt_pwd = $this->conn->prepare($query);
        if($stmt_pwd->execute()){
            return true;
        }else{
          return false;
        }
	}
 }
?>