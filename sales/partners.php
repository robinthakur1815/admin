<?php #Author BY SS
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
include_once '../objects/Salesapi.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$connMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];

$sales = new Salesapi($db,$connMongoDb,$data->strtdate,$data->enddate);

if(
	!empty($data->uniq_id) &&
	!empty($data->pub_type) &&
	!empty($data->strtdate) &&
	!empty($data->enddate)
){
	#set token property values 
	$header->access_token = trim($token);
	$header->pub_uniq_id = $data->uniq_id;
	$result_fun = $header->verifyToken();
	$stmt_result = $result_fun->get_result();
	$rows = $stmt_result->num_rows;
	if($rows > 0){
		$sales->uniq_id = $data->uniq_id;
		$accounts = $data->pub_type;
		$days = $data->days;

		// $salesName = $data->sales_name;
		// $channelName = $data->channel_name;
		// $userStaus = $data->user_staus;

		$strtdate = $data->strtdate;
		$enddate = $data->enddate;

		$where = "";
		if($days !='NULL'){
			if($days != 'NULL' && $strtdate !='NULL' && $enddate !='NULL'){
				$strtdate = date("Y-m-d",strtotime($data->strtdate));
				$enddate = date("Y-m-d",strtotime($data->enddate));
				$where .= 'AND dis.ad_domain_date between "'.$strtdate.'" and "'.$enddate.'" group by dis.ad_domain_date';
			}else{
				if($days=='Last7days'){
					$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 7 DAY) group by dis.ad_domain_date';

					$date_interval = "DATE(NOW()) + INTERVAL -7 DAY as startdate, DATE(NOW()) as enddate";
					$interval_date = $sales->getIntervalDate($date_interval);

					$strtdate = $interval_date['startdate'];
					$enddate = $interval_date['enddate'];
				}
				if($days=='Last10days'){
					$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 10 DAY) group by dis.ad_domain_date'; 

					$date_interval = "DATE(NOW()) + INTERVAL -10 DAY as startdate, DATE(NOW()) as enddate";
					$interval_date = $sales->getIntervalDate($date_interval);

					$strtdate = $interval_date['startdate'];
					$enddate = $interval_date['enddate'];
				}
				if($days=='Last30days'){
					$where .= 'AND DATE(dis.ad_domain_date) >= DATE(NOW() - INTERVAL 30 DAY) group by dis.ad_domain_date'; 

					$date_interval = "DATE(NOW()) + INTERVAL -30 DAY as startdate, DATE(NOW()) as enddate";
					$interval_date = $sales->getIntervalDate($date_interval);

					$strtdate = $interval_date['startdate'];
					$enddate = $interval_date['enddate'];
				}
				if($days=='ThisMonth'){
					$where .= 'AND dis.ad_domain_date >= (LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 MONTH) AND dis.ad_domain_date <  (LAST_DAY(NOW()) + INTERVAL 1 DAY) group by dis.ad_domain_date';

					$date_interval = "DATE(NOW()) - interval day(now()) day + interval 1 day as startdate, LAST_DAY(DATE(NOW())) as enddate";
					$interval_date = $sales->getIntervalDate($date_interval);

					$strtdate = $interval_date['startdate'];
					$enddate = $interval_date['enddate'];
				}
				if($days=='LastMonth'){
					$where .= 'AND YEAR(dis.ad_domain_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(dis.ad_domain_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) group by dis.ad_domain_date';

					$date_interval = "last_day(NOW() - interval 2 month) + interval 1 day as startdate, last_day(NOW() - interval 1 month) as enddate";
					$interval_date = $sales->getIntervalDate($date_interval);

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
			$interval_date = $sales->getIntervalDate($date_interval);

			$strtdate = $interval_date['startdate'];
			$enddate = $interval_date['enddate'];
		}
		$sales->where = $where;

		$result_data = $sales->getOverAllPublisherList($accounts, $strtdate, $enddate);		
		$sales_team = $sales->salesTeam();
		$sales_channel = $sales->salesChannel();

		$salesPublisherData = array();
		foreach($result_data as $siteKey => $siteValue){
			$salesPublisherData[] = $siteValue;
		}

		if(!empty($result_data)){
			$json_response = json_encode(array("message"=>"Success", "data"=>$salesPublisherData, "sales_team"=>$sales_team, "sales_channel"=>$sales_channel, "status_code"=>200));
			echo $json_response;
		}else{
			http_response_code(422);
			echo json_encode(array("message"=>"No Data Found!", "data"=>"", "sales_team"=>"", "sales_channel"=>"", "status_code"=>422));
		}
	}else{
		http_response_code(422);
		echo json_encode(array("message"=>"Invalid token", "data"=>"", "sales_team"=>"", "sales_channel"=>"", "status_code"=>422));
	}
}else{
	http_response_code(400);
	echo json_encode(array("message"=>"Unable to get Network Partner Data. Data is incomplete.", "data"=>"", "sales_team"=>"", "sales_channel"=>"", "status_code"=>400));
}

?>
