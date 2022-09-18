<?php
#Author BY robin
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
#required headers
header("HTTP/1.1 200 OK");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#include database and object files
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/User.php';

#instantiate database and product object
$database = new Database();
$conn = $database->getConnection();
$header = new Common($conn);
$user = new User($conn);
#get posted data
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
$token = $matches[1];
#make sure data is not empty

if (
    !empty($data->uniq_id)
) {
    #set token property values 
    $header->access_token = trim($token);
    $header->pub_uniq_id = $data->pub_uniq_id;
    $result_fun = $header->verifyToken();
    $stmt_result = $result_fun->get_result();
    $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if ($rows > 0) {
        $user->uniq_id = $data->uniq_id;
        $result_mngr = $user->getapidoc();
        $stmt_mngr = $result_mngr->get_result();
        $rowMngr = $stmt_mngr->fetch_array(MYSQLI_ASSOC);
        $user->uniq_id = $data->uniq_id;
        $result_pub = $user->checkApiDoc();
        $stmt_pub = $result_pub->get_result();
        $rowCheckApi = $stmt_pub->fetch_array(MYSQLI_ASSOC);
           $list_array[] = array(
                'adx_for_display'=> $rowCheckApi['adx_for_display'],
                'adx_for_video'=>$rowCheckApi['adx_for_video'],
                'adx_for_app'=> $rowCheckApi['adx_for_app'],
                
            );
        if ($rowMngr > 0) {
            #set checkApiDoc property values 
            if ($rowCheckApi > 1) {
                #set response code - 200 ok
                http_response_code(200);

                #tell the user
                echo json_encode(array("data" =>$list_array,"mesage" => "PDF can be genrated",  "status_code" => 200));
            }

            #if PDF can not be genrated the user, tell the user
            else {
                http_response_code(503);

                #tell the user
               echo json_encode(array("message" => "this User Does NOt Have Any PDF for downlaod.", "status_code" => 202));
            }
        } else {
            #set checkApiDoc property values 
            if ($rowCheckApi > 1) {
                #set response code - 200 ok
                http_response_code(200);

                #tell the user
                echo json_encode(array("data" =>$list_array,"mesage" => "PDF can be genrated", "status_code" => 200));
            }

            #if unable to create the user, tell the user
            else {

                #set response code - 503 service unavailable
                http_response_code(503);

                #tell the user
                echo json_encode(array("message" => "this User Does NOt Have Any PDF for downlaod.", "status_code" => 503));
            }
            $sites = $data->uniq_id;
            $token = openssl_random_pseudo_bytes(16);
            $token = bin2hex($token);
            $newtoken = $sites . $token;
            $query = 'INSERT INTO thirdparty_api_doc(unid_id,access_token)VALUES ("' . $sites . '", "' . $newtoken . '")';
            if (mysqli_query($conn, $query)) {
                echo json_encode(array("message" => "successfully generated token", "status_code" => 200));
                //print_r($pdf);
            } else {
                #set response code - 503 service unavailable
                http_response_code(503);

                #tell the user
                echo json_encode(array("message" => "Unable to generated token", "status_code" => 503));
            }
        }
    } else {
        #set response code - 422 validation error
        http_response_code(422);

        #tell the user
        echo json_encode(array("message" => "Invalid token", "status_code" => 422));
    }
}
#tell the user data is incomplete
else {

    #set response code - 400 bad request
    http_response_code(400);

    #tell the user
    echo json_encode(array("message" => "Unable to get pdf. Data is incomplete.", "status_code" => 400));
}
