<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(1);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("HTTP/1.1 200 OK"); 
#Time Zone
date_default_timezone_set('Asia/Kolkata');
#for number format
ini_set('serialize_precision', 10);

SELECT * FROM `publishers_website` WHERE `pub_uniq_id` = 'ROHI_171117_151451'
include_once '../../config/connection.php';
include_once '../../objects/Common.php';
include_once '../../objects/Accmgr.php';
$database = new Database();
$conn = $database->getConnection();
$header = new Common($conn);

#get posted data
try{
$data = json_decode(file_get_contents("php://input"));
$headers = apache_request_headers();
preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    $token = $matches[1];
    // if(
    // !empty($data->uniq_id) 
    // ){ $header->access_token = trim($token);
    // $header->pub_uniq_id = $data->uniq_id;
    // $result_fun = $header->verifyToken();
    // $stmt_result = $result_fun->get_result();
    // $rowPub = $stmt_result->fetch_array(MYSQLI_ASSOC);
    // $rows = $stmt_result->num_rows;
    // if($rows > 0){

      
    $sites = $data->uniq_id;
    $checkToken = mysqli_query($conn,"select * from thirdparty_api_doc where unid_id='$sites'");
    
    if($checkToken->num_rows >0 ){
     echo json_encode(array("message" => "token and uniq_id allready exist in databse","status_code"=>202));
      exit();
    }
   else{
      $sites = $data->uniq_id;
      $token = openssl_random_pseudo_bytes(16);
      $token = bin2hex($token);
      $newtoken = $sites.$token;
        $query ='INSERT INTO thirdparty_api_doc(unid_id,access_token)VALUES ("'.$sites.'", "'.$newtoken.'")';
        if(mysqli_query($conn,$query))
          {
         echo json_encode(array("message" => "successfully generated token","status_code"=>200));
         exit();  
      }else{
         print_r(mysqli_error($conn));
          exit;
      }
  }
}
// }
// }
catch(\Exception $e){
    print_r($e);
    
}
 ?>
<!-- 
$checkToken = mysqli_query($conn,"select * from thirdparty_api_doc where unid_id='$data->uniq_id'");
            
            if($checkToken->num_rows >0 ){
            echo json_encode(array("message" => "token and uniq_id allready exist in databse","status_code"=>202));
            exit();
            }
         else{
         $sites = $data->uniq_id;
         $token = openssl_random_pseudo_bytes(16);
         $token = bin2hex($token);
         $newtoken = $sites.$token;
         $query ='INSERT INTO thirdparty_api_doc(unid_id,access_token)VALUES ("'.$sites.'", "'.$newtoken.'")';
         if(mysqli_query($conn,$query))
          {
         echo json_encode(array("message" => "successfully generated token","status_code"=>200));
         exit();  
         }else{
         print_r(mysqli_error($conn));
          exit;
         }
         }
        } -->

        #$checkToken values 
             $checkToken = mysqli_query($conn,"select * from thirdparty_api_doc where unid_id='$data->uniq_id'");
             // print_r($checkToken);die;
             if($checkToken->num_rows >0 ){
              echo $sites = $data->uniq_id;
              echo $token = $header->access_token;
              
            // $header->uniq_id = $data->uniq_id;
            // $result_mngr = $header->checkApiDoc();
            // $result_head = $result_header->get_result();
            // $rowHead = $result_head->fetch_array(MYSQLI_ASSOC);
            // $rowsHead = $result_head->num_rows;
           // echo "robin";
           // print_r($rowsHead);die;
            // $data[];
            //  echo json_encode(array("message" => "token and uniq_id allready exist in databse","status_code"=>202));
            //  $pubInvoice->manager_name = $rowMngr['manager_name'];
             exit();
          }
         else{
         $sites = $data->uniq_id;
         $token = openssl_random_pseudo_bytes(16);
         $token = bin2hex($token);
         $newtoken = $sites.$token;
         $query ='INSERT INTO thirdparty_api_doc(unid_id,access_token)VALUES ("'.$sites.'", "'.$newtoken.'")';
         if(mysqli_query($conn,$query))
          {
         echo json_encode(array("message" => "successfully generated token","status_code"=>200));
         exit();  
         }else{
         print_r(mysqli_error($conn));
          exit;
         }