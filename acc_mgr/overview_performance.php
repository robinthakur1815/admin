<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK");
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#for number format
ini_set('serialize_precision', 10);
#include database and object files
include_once '../config/connection.php';
include_once '../objects/Common.php';
include_once '../objects/Accmgr.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$accmgr = new Accmgr($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
   
#make sure data is not empty
if(
    !empty($data->uniq_id) && 
    !empty($data->accounts) && 
    !empty($data->pub_id) && 
    !empty($data->filter) && 
    !empty($data->manager_id) 
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rows = $stmt_result->num_rows;
    if($rows > 0){
    	#set token property values
       $accmgr->manager_id = $data->manager_id;
       $result_topdata = $accmgr->topPerData();
       #get publishers list
       $result_web = array();
       if($data->accounts != 'NULL'){
         $result_pub = $accmgr->getPub($data->accounts);
          #get website condition
         if($data->pub_id != 'NULL'){
               $accmgr->pub_id = $data->pub_id; 
               $result_web = $accmgr->getWeb();
          }else{
             $result_web = array();
          }
       }else{
         $result_pub = array();
       }

       #filterdata
       if($data->filter == 'yes' ){
           $child_net_code = $data->child_net_code;
           $web_name = $data->web_name;
           $days = $data->days;
           $strtdate = $data->strtdate;
           $enddate = $data->enddate;
           if($child_net_code != 'NULL'){
               $where = 'dis.child_net_code= "'.$child_net_code.'" '; 
           }else{
            $where = 'dis.child_net_code IS NOT NULL ';
           }
           if($web_name !='NULL'){
            $where .= 'AND dis.site_name LIKE "%'.$web_name.'%" ';
           }
			if($days !='NULL'){
				if($days != 'NULL' && $strtdate !='NULL' && $enddate !='NULL'){
					$strtdate = date("Y-m-d",strtotime($data->strtdate));
					$enddate = date("Y-m-d",strtotime($data->enddate));
					$where .= 'AND dis.ad_domain_date between "'.$strtdate.'" and "'.$enddate.'" group by dis.ad_domain_date';
				}else{
					if($days=='Last7days'){ 
						$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 7 DAY) group by dis.ad_domain_date';
						
						$date_interval = "DATE(NOW()) + INTERVAL -7 DAY as startdate, DATE(NOW()) as enddate";
						$interval_date = $accmgr->getIntervalDate($date_interval);

						$strtdate = $interval_date['startdate'];
						$enddate = $interval_date['enddate'];
					}
					if($days=='Last10days'){
						$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 10 DAY) group by dis.ad_domain_date'; 

						$date_interval = "DATE(NOW()) + INTERVAL -10 DAY as startdate, DATE(NOW()) as enddate";
						$interval_date = $accmgr->getIntervalDate($date_interval);

						$strtdate = $interval_date['startdate'];
						$enddate = $interval_date['enddate'];
					}
					if($days=='Last30days'){ 
						$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 30 DAY) group by dis.ad_domain_date'; 

						$date_interval = "DATE(NOW()) + INTERVAL -30 DAY as startdate, DATE(NOW()) as enddate";
						$interval_date = $accmgr->getIntervalDate($date_interval);

						$strtdate = $interval_date['startdate'];
						$enddate = $interval_date['enddate'];
					}
					if($days=='ThisMonth'){ 
						$where .= 'AND dis.ad_domain_date >= (LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 MONTH) AND dis.ad_domain_date <  (LAST_DAY(NOW()) + INTERVAL 1 DAY) group by dis.ad_domain_date';

						$date_interval = "DATE(NOW()) - interval day(now()) day + interval 1 day as startdate, LAST_DAY(DATE(NOW())) as enddate";
						$interval_date = $accmgr->getIntervalDate($date_interval);

						$strtdate = $interval_date['startdate'];
						$enddate = $interval_date['enddate'];
					}
					if($days=='LastMonth'){
						$where .= 'AND YEAR(dis.ad_domain_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(dis.ad_domain_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) group by dis.ad_domain_date';

						$date_interval = "last_day(NOW() - interval 2 month) + interval 1 day as startdate, last_day(NOW() - interval 1 month) as enddate";
						$interval_date = $accmgr->getIntervalDate($date_interval);

						$strtdate = $interval_date['startdate'];
						$enddate = $interval_date['enddate'];
					}
				}
			}

           if($days == 'NULL' && $strtdate !='NULL' && $enddate !='NULL'){
            $strtdate = date("Y-m-d",strtotime($data->strtdate));
            $enddate = date("Y-m-d",strtotime($data->enddate));
             $where .= 'AND dis.ad_domain_date between "'.$strtdate.'" and "'.$enddate.'" group by dis.ad_domain_date';
           }

           if($days == "NULL" && $strtdate == "NULL"){
             $where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 7 DAY)  group by dis.ad_domain_date';

				$date_interval = "DATE(NOW()) - INTERVAL 7 DAY as startdate, DATE(NOW()) as enddate";
				$interval_date = $accmgr->getIntervalDate($date_interval);

				$strtdate = $interval_date['startdate'];
				$enddate = $interval_date['enddate'];
           }
           $accmgr->where = $where;

          $resp_filterData = $accmgr->filterData($data->accounts, $child_net_code, $strtdate, $enddate);
       }else{
         $resp_filterData = array();
       }
      
       
       if(!empty($result_topdata) || !empty($result_pub) || !empty($resp_filterData)){
            
            # JSON-encode the response
            $json_response = json_encode(array("data"=>array("Topoverview"=>$result_topdata,"pub_list"=>$result_pub,"pub_web"=>$result_web,"filterData"=>$resp_filterData),"status_code"=>200));

            # Return the response
            echo $json_response;

         }else{
            #set response code - 422 validation error
                http_response_code(422);
          
                #tell the user
                echo json_encode(array("message" => "No Data Found!","status_code"=>422));
         }


     }
     else{
        #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid token","status_code"=>422));
      }
}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to get Account Manager Top. Data is incomplete.","status_code"=>400));
}
?>