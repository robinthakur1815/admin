<?php
#Author BY AD
#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

#include database and object files
include_once '../config/connection.php';

  
#instantiate database and product object
$database = new Database();
$db = $database->getConnection();

 /*****For pub_id*************/
//  $queryFetch= "SELECT  pub_id,pub_uniq_id FROM publisher_master_overall";
//     #prepare query
//     $stmt = $db->prepare($queryFetch);
//     #execute query 
//     $stmt->execute();
//     $stmt_result = $stmt->get_result();
//     $row = $stmt_result->fetch_all(MYSQLI_ASSOC);

// foreach($row as $value){
//     #update query
//     $query = "UPDATE
//                 publishers_website_overall
//             SET
//                 pub_id = ".$value['pub_id']."
                
//             WHERE
//                 pub_uniq_id = '".$value['pub_uniq_id']."'";
  
//     #prepare query statement
//     $stmt_token = $db->prepare($query);
//     $stmt_token->execute();
// }

/*****For vertical*************/
//  $queryFetch1 = "SELECT  website,vertical,vertical2 FROM website_vertical_details";
//     #prepare query
//     $stmtv = $db->prepare($queryFetch1);
//     #execute query 
//     $stmtv->execute();
//     $stmt_resultv = $stmtv->get_result();
//     $rowv = $stmt_resultv->fetch_all(MYSQLI_ASSOC);

// foreach($rowv as $valuev){
//     #update query
//     $query1 = "UPDATE
//                 publishers_website_overall
//             SET
//                 vertical = '".$valuev['vertical']."',vertical2 = '".$valuev['vertical2']."'
                
//             WHERE
//                     web_name LIKE '%".$valuev['website']."%'";
  
//     //prepare query statement
//     $stmt_token1 = $db->prepare($query1);
//     $stmt_token1->execute();
// }
/*****For pub org add*************/

?>
