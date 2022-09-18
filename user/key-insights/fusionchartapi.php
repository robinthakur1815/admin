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
#for number format
ini_set('serialize_precision', 10);
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Analytics.php';

#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$dbMongoDb = $database->getConnectionMongoDb();
$header = new Common($db);
$analytics = new Analytics($db,$dbMongoDb);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];



$countries = array('AG'=>'Antigua and Barbuda', 'BS'=>'Bahamas', 'BB'=>'Barbados', 'BZ'=>'Belize', 'CA'=>'Canada', 'CR'=>'Costa Rica', 'CU'=>'Cuba', 'DM'=>'Dominica', 'DO'=>'Dominican Republic', 'SV'=>'El Salvador', 'GD'=>'Grenada', 'GT'=>'Guatemala', 'HT'=>'Haiti', 'HN'=>'Honduras', 'JM'=>'Jamaica', 'MX'=>'Mexico', 'NI'=>'Nicaragua', 'PA'=>'Panama', 'KN'=>'St. Kitts & Nevis', 'LC'=>'St. Lucia', 'VC'=>'St. Vincent & the Grenadines', 'TT'=>'Trinidad & Tobago', 'US'=>'United States', 'GL'=>'Greenland', 'AR'=>'Argentina', 'BO'=>'Bolivia', 'BR'=>'Brazil', 'CL'=>'Chile', 'CO'=>'Colombia', 'EC'=>'Ecuador', 'FK'=>'Falkland Islands', 'GF'=>'French Guiana', 'GY'=>'Guyana', 'PY'=>'Paraguay', 'PE'=>'Peru', 'SR'=>'Suriname', 'UY'=>'Uruguay', 'VE'=>'Venezuela', 'DZ'=>'Algeria', 'AO'=>'Angola', 'BJ'=>'Benin', 'BW'=>'Botswana', 'BF'=>'Burkina Faso', 'BI'=>'Burundi', 'CM'=>'Cameroon', 'CV'=>'Cape Verde', 'CP'=>'Central African Republic', 'TD'=>'Chad', 'KM'=>'Comoros', 'CI'=>'Ivory Coast', 'CD'=>'DR Congo', 'DJ'=>'Djibouti', 'EG'=>'Egypt', 'GQ'=>'Equatorial Guinea', 'ER'=>'Eritrea', 'ET'=>'Ethiopia', 'GA'=>'Gabon', 'GH'=>'Ghana', 'GN'=>'Guinea', 'GW'=>'Guinea-Bissau', 'KE'=>'Kenya', 'LS'=>'Lesotho', 'LI'=>'Liberia', 'LR'=>'Libya', 'MS'=>'Madagascar', 'MW'=>'Malawi', 'ML'=>'Mali', 'MR'=>'Mauritania', 'MA'=>'Morocco', 'MZ'=>'Mozambique', 'NA'=>'Namibia', 'NE'=>'Niger', 'NG'=>'Nigeria', 'RW'=>'Rwanda', 'ST'=>'Sao Tome and Principe', 'SN'=>'Senegal', 'SC'=>'Seychelles', 'SL'=>'Sierra Leone', 'SO'=>'Somalia', 'ZA'=>'South Africa', 'SD'=>'Republic of Sudan', 'SZ'=>'Swaziland', 'TZ'=>'Tanzania', 'TG'=>'Togo', 'TN'=>'Tunisia', 'UG'=>'Uganda', 'WA'=>'Western Sahara', 'ZM'=>'Zambia', 'ZW'=>'Zimbabwe', 'GM'=>'Gambia', 'CG'=>'Congo', 'MI'=>'Mauritius', 'AF'=>'Afghanistan', 'AM'=>'Armenia', 'AZ'=>'Azerbaijan', 'BD'=>'Bangladesh', 'BT'=>'Bhutan', 'BN'=>'Brunei', 'MM'=>'Myanmar (Burma)', 'KH'=>'Cambodia', 'CN'=>'China', 'TP'=>'Timor-Leste', 'GE'=>'Georgia', 'IN'=>'India', 'ID'=>'Indonesia', 'IA'=>'Iran', 'JP'=>'Japan', 'KZ'=>'Kazakhstan', 'KP'=>'North Korea', 'KR'=>'South Korea', 'KG'=>'Kyrgyzstan', 'LA'=>'Laos', 'MY'=>'Malaysia', 'MN'=>'Mongolia', 'NP'=>'Nepal', 'PK'=>'Pakistan', 'PH'=>'Philippines', 'RU'=>'Russia', 'SG'=>'Singapore', 'LK'=>'Sri Lanka', 'TJ'=>'Tajikistan', 'TH'=>'Thailand', 'TM'=>'Turkmenistan', 'UZ'=>'Uzbekistan', 'VN'=>'Vietnam', 'TW'=>'Taiwan', 'HK'=>'Hong Kong', 'MO'=>'Macau', 'AL'=>'Albania', 'AD'=>'Andorra', 'AT'=>'Austria', 'BY'=>'Belarus', 'BE'=>'Belgium', 'BH'=>'Bosnia and Herzegovina', 'BG'=>'Bulgaria', 'HY'=>'Croatia', 'CZ'=>'Czech Republic', 'DK'=>'Denmark', 'EE'=>'Estonia', 'FI'=>'Finland', 'FR'=>'France', 'DE'=>'Germany', 'GR'=>'Greece', 'HU'=>'Hungary', 'IC'=>'Iceland', 'IR'=>'Ireland', 'IT'=>'Italy', 'LV'=>'Latvia', 'LN'=>'Liechtenstein', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MK'=>'Macedonia', 'MT'=>'Malta', 'MV'=>'Moldova', 'MC'=>'Monaco', 'MG'=>'Montenegro', 'NL'=>'Netherlands', 'NO'=>'Norway', 'PL'=>'Poland', 'PT'=>'Portugal', 'RO'=>'Romania', 'SM'=>'San Marino', 'CS'=>'Serbia', 'SK'=>'Slovakia', 'SI'=>'Slovenia', 'ES'=>'Spain', 'SE'=>'Sweden', 'CH'=>'Switzerland', 'UA'=>'Ukraine', 'UK'=>'United Kingdom', 'VA'=>'Vatican City', 'CY'=>'Cyprus', 'TK'=>'Turkey', 'AU'=>'Australia', 'FJ'=>'Fiji', 'KI'=>'Kiribati', 'MH'=>'Marshall Islands', 'FM'=>'Micronesia', 'NR'=>'Nauru', 'NZ'=>'New Zealand', 'PW'=>'Republic of Palau', 'PG'=>'Papua New Guinea', 'WS'=>'Samoa', 'SB'=>'Solomon Islands', 'TO'=>'Tonga', 'TV'=>'Tuvalu', 'VU'=>'Vanuatu', 'NC'=>'New Caledonia', 'BA'=>'Bahrain', 'IZ'=>'Iraq', 'IS'=>'Israel', 'JO'=>'Jordan', 'KU'=>'Kuwait', 'LB'=>'Lebanon', 'OM'=>'Oman', 'QA'=>'Qatar', 'SA'=>'Saudi Arabia', 'SY'=>'Syria', 'AE'=>'United Arab Emirates', 'YM'=>'Yemen', 'PR'=>'Puerto Rico', 'KY'=>'Cayman Islands', 'SS'=>'South Sudan', 'KO'=>'Kosovo', 'AB'=>'Aruba', 'AN'=>'Anguilla', 'AS'=>'American Samoa', 'BM'=>'Bermuda', 'BU'=>'BES Islands', 'CC'=>'Cocos (Keeling) Islands', 'CK'=>'Cook Islands', 'CT'=>'Christmas Island', 'CW'=>'Curacao', 'FA'=>'Faroe Islands', 'FP'=>'French Polynesia', 'GI'=>'Gibraltar', 'GO'=>'Guam', 'GP'=>'Guadeloupe', 'GS'=>'Gaza Strip', 'GU'=>'Guernsey', 'IM'=>'Isle of Man', 'JS'=>'Jersey', 'KS'=>'Kingman Reef', 'MD'=>'Maldives', 'ME'=>'Montserrat', 'MP'=>'Mayotte', 'MQ'=>'Martinique', 'NF'=>'Norfolk Island', 'NM'=>'Northern Mariana Islands', 'NU'=>'Niue', 'PI'=>'Pitcairn Islands', 'RE'=>'La Réunion', 'SF'=>'Sint Maarten', 'SH'=>'Saint Helena', 'SP'=>'Saint Pierre and Miquelon', 'TC'=>'Turks and Caicos Islands', 'VK'=>'Virgin Islands (UK)', 'VS'=>'Virgin Islands (US)', 'WE'=>'Palestine', 'WF'=>'Wallis and Futuna', 'WC'=>'Cape Town', 'LP'=>'La Paz', 'AB'=>'Abkhazia', 'NA'=>'Netherlands Antilles', 'NC'=>'Northern Cyprus', 'SV'=>'Svalbard', 'TK'=>'Tokelau');


    


#make sure data is not empty
if(
    !empty($data->uniq_id) &&
    !empty($data->account_id) &&
    !empty($data->child_net_code)
    
){
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $row = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
   
     #set traffic property values 
    //======================= LAST WEEK=========================//
    $analytics->account_id = $data->account_id;
    $previous_week = strtotime("-1 week +1 day");
    $start_week = strtotime("last monday midnight",$previous_week);
    $end_week = strtotime("next sunday",$start_week);
    $start_week = date("Y-m-d",$start_week);
    $end_week = date("Y-m-d",$end_week);
    $daterangelastweek=date("j M", strtotime($start_week)).' - '.date("j M, Y", strtotime($end_week));

    $analytics->pstart_week = $start_week;
    $analytics->pend_week = $end_week;
    $result_lweek = $analytics->getTrafficSource(); 
    $result_lweekchart = $result_lweek->toArray();
    
   if(!empty($result_lweekchart)){
           
         #calculation
          $data = prepareData($result_lweekchart,$countries,$daterangelastweek);
           
          #set response code - 200 ok
        http_response_code(200);
  
        #tell the user
        echo json_encode(array("data"=>$data,"status_code"=>200));
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
    echo json_encode(array("message" => "Unable to get Traffic Source. Data is incomplete.","status_code"=>400));
}

function prepareData($resultp,$countries,$daterangelastweek){
$inArrayL = array(); $newArrL = array(); $inArrayP = array(); $newArrP = array(); $tableP = array();  $tableL = array(); 
	
	//======================= LAST WEEK=========================//
foreach($resultp as $k=>$val) {
		
		// if($val->source == '(direct)'){
			
			$country									= $val->country;
			$inArrayL[$country][$k]['users']			= $val->users;
			$inArrayL[$country][$k]['pageviews']		= $val->pageviews;
			if(in_array($country,$inArrayL)){

				@$inArrayL[$country][$k]['users']			+= $val->users;
				@$inArrayL[$country][$k]['pageviews']		+= $val->pageviews;
				
			}else{
				$inArrayL[$country][$k]['country']			= $val->country;
			}
			$tot_usersL += $val->users;
			$tot_pageviewsL += $val->pageviews;
			
		// }                                                                 
	}                                                        
   #sum countrywise
	foreach($inArrayL as $k=> $arrVal){

		$data = array();

		foreach ($inArrayL[$k] as $kk=>$v){

			$data['country'] = $v['country'];
			@$data['users']	+= $v['users'];
		    @$data['pageviews']	+= $v['pageviews'];
			 
		}
		$newArrL[$k] = $data; 
	}
  
	foreach($newArrL as $key=>$nval){

		$tableL['country'][$key]			= $nval['country'];
		$tableL['usersL'][$key]				= $nval['users'];
		$tableL['pageviewsL'][$key]			= $nval['pageviews'];
		
	}
	
arsort($tableL['usersL']);
	 
    /*Make Geo Data For Fusion Chart*/ 
    if(!empty($tableL['usersL'])){
        $geoRecCount = 0;
        foreach($tableL['usersL'] AS $geoKey=>$geoVals){
            //if($geoRecCount < 10){
            
                $findCountryCode = array_search($geoKey, $countries);
                $geoDataTrafficJSON[] = array(strtolower($findCountryCode),$geoVals);
                
                $arrCountryKey[] = $findCountryCode;
            //}
            //$geoRecCount++;
        }

        }

        #if country not in our db value will be zero
        
        foreach ($countries as $counkey => $counvalue) {
            if(!in_array($counkey,$arrCountryKey)){
                 $geoDataTrafficJSON[] =  array(strtolower($counkey),0);   
            }
         
        }

 // echo "<pre>";
 // print_r($geoDataTrafficJSON);die;
    
$sendresponse=array('chartdata'=>$geoDataTrafficJSON,'daterangetop'=>array($daterangelastweek));

   return $sendresponse; 
}/***calculation function end*****/


?>