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
#include database and object files
include_once '../config/connection.php';
include_once '../objects/User.php';
  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();
$users = new User($db);
#get posted data
$data = json_decode(file_get_contents("php://input"));
#make sure data is not empty
if(
    !empty($data->email)
    
){

    #set email property values 
    $users->email = $data->email;
    $res = $users->verifyEmail();
    $stmt_result = $res->get_result();
    $rowArr = $stmt_result->fetch_array(MYSQLI_ASSOC);
    $rows = $stmt_result->num_rows;
    if($rows > 0){
            // $token = openssl_random_pseudo_bytes(16);
            // $token = bin2hex($token);
            // $token_pub = $rowArr['pub_uniq_id'].$token;  
            // $users->token = $token_pub;
            // $users->tokenUpdate();
            $users->uniq_id = $rowArr['pub_uniq_id'];
            $users->email = $rowArr['pub_email'];
            $users->name = $rowArr['name'];
            $users->verificationText = date('d').'rgji'.date('m').''.date('y').'xbjcmr'.'';
            $response = $users->resetPwdMail();
            // echo "<pre>";
            // print_r($response);die;

            if($response == 1){
                $request_array=array(
                'message'=>"An email has been sent to your registered email with instructions to reset your password",
                'success'=>True
                );
            # JSON-encode the response
            $json_response = json_encode(array("data"=>$request_array,"status_code"=>200));

            # Return the response
            echo $json_response; 
            }else{
            $request_array=array(
                'message'=>"Something went wrong",
                'success'=>false
                );
            # JSON-encode the response
            $json_response = json_encode(array("data"=>$request_array,"status_code"=>204));

            # Return the response
            echo $json_response;

            } 
           

    }
    else{
        #set response code - 422 validation error
        http_response_code(422);
  
        #tell the user
        echo json_encode(array("message" => "Invalid email address!","status_code"=>422));
      }
}
 #tell the user data is incomplete
else{
  
    #set response code - 400 bad request
    http_response_code(400);
  
    #tell the user
    echo json_encode(array("message" => "Unable to forgot password. Data is incomplete.","status_code"=>400));
}
?>