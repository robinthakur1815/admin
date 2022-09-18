<?php
#Author BY AD
class Invite{

 #database connection and table name
    private $conn;
    private $table_pub="publisher_master";
   

    #object properties
    public $uniq_id;
    public $child_net_code;
    public $pub_id;
    public $domainName;
    public $appName;
    public $appID;
    public $memberType;
    public $email;
    public $gamAcc;
    public $pub_name;
    
    
    

    
    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    
    #get publisher data
    public function getPubGam(){
      #query
      $queryFetch = 'SELECT gam_email,company_id FROM ' . $this->table_pub . ' where pub_email ="'.$this->email.'" and company_id IS NOT NULL';
      #prepare query
      $row = $this->conn->prepare($queryFetch);
      #execute query 
      $row->execute();
      $stmt_result = $row->get_result();
      $resp = $stmt_result->fetch_array(MYSQLI_ASSOC);
      $rows = $stmt_result->num_rows;
      $response_array = array();
      if($rows > 0){
        
        $response_array = $resp;
         return $response_array;
      }else{
       
         return $response_array;
      }
       

    }
     #get publisher domain
    public function getPubGamDomain(){
              #get domain
              #query
              $queryFetchD = 'SELECT web_name FROM publishers_website where pub_uniq_id ="'.$this->uniq_id.'"';
              #prepare query
              $rowD = $this->conn->prepare($queryFetchD);
              #execute query 
              $rowD->execute();
              return $rowD;
    }
    #publisher invite and domain registered
    public function getPubInvite(){
     
        
        #Domain 
         $domainName = $this->domainName;
        
           if($domainName != 'NULL'){
               
              $regex = "((https?|ftp)\:\/\/)?";
              $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
              $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})";
              $regex .= "(\:[0-9]{2,5})?";
              $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?";
              $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?";
              $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?";
           if (!preg_match("/^$regex$/i", $domainName)) {   
              $resultArray = array("msg"=>"false","message"=>"Please enter a valid URL","status_code"=>422);
              return $resultArray;
              exit;
            }
            $input = trim($domainName, '/');
            if (!preg_match('#^http(s)?://#', $input)) {
              $input = 'http://' . $input;
            }
            $urlParts = parse_url($input);
            $domainName = preg_replace('/^www\./', '', $urlParts['host']);


            #Query
            $subquery1 = "select web_name from publishers_website where web_name='".$domainName."'";
            #prepare query
            $row = $this->conn->prepare($subquery1);
            #execute query 
            $row->execute();
            $stmt_result = $row->get_result();
            $newDataArr1 = $stmt_result->fetch_array(MYSQLI_ASSOC);

           
            $resultArray = array(); 
            #condition if domain array not empty
            if(!empty($newDataArr1)){
                
               #condtion if domain are same in signup and GAM 
              if(isset($this->gamAcc) && !empty($this->gamAcc)){

                       #call GAM API function
                  $res = $this->gamApi($this->pub_name,$this->email,$this->pub_id);

                    if($res == true){
                          
                            $resultArray = array("msg"=>"success","text"=>"Invite Successfully Send");
                              
                              return $resultArray; 
                              exit;
                        }
                        else{
                            return $resultArray;
                          }
                   
              }else{
                 $resultArray = array("msg"=>"false","message"=>"Domain Name already exists, please check details!","status_code"=>422);
                  return $resultArray;
                  
                  exit;  
              }
              
            }else{

            #domain query 
                $web_q = 'INSERT INTO `publishers_website`(pub_id,pub_uniq_id,web_name) VALUES ('.$this->pub_id.',"'.$this->uniq_id.'","'.$domainName.'")';
            #prepare query
            $rowD = $this->conn->prepare($web_q);

              #condtion for GAM INvite API
              if(isset($this->gamAcc) && !empty($this->gamAcc)){
                    
                    #call GAM API function
                  $res = $this->gamApi($this->pub_name,$this->email,$this->pub_id);

                  if($res == true){
                         #execute domain query 
                
                            if($rowD->execute()){

                              $resultArray = array("msg"=>"success","text"=>"Invite Successfully Send");
                            }
                            return $resultArray; 

                      }
                      else{
                          return $resultArray;
                        }

                }else{

                   #execute domain query 
                
                    if($rowD->execute()){

                      $resultArray = array("msg"=>"success","text"=>"Domain Registered Successfully");
                    }
                    return $resultArray;
                }
              
               
            } #domain array condtion end

          } #domain condtion end
          
         else{
            #App case
             #call GAM API function
                  $res = $this->gamApi($this->pub_name,$this->email,$this->pub_id);
                  $resultArray = array();  
                  if($res == true){
                          $resultArray = array("msg"=>"success","text"=>"Invite Successfully Send");
                            
                            return $resultArray; 
                       }
                      else{
                          return $resultArray;
                        }
         }

    } 

    #invite api function
    public function gamApi($pub_acc_name,$inviteEmail,$pub_id){
        $name= urlencode(trim($pub_acc_name));
        $email=urlencode(trim($inviteEmail));
        #$url ="https://safe.ty.cybermediaservices.net/dfp_adx_api_singapore/company_invite_api.php?pub_name=$name&pub_email=$email";
          $url ="https://safe.ty.cybermediaservices.net/dfp_adx_api_singapore/company_invite_api_demo.php?pub_name=$name&pub_email=$email";

              $options = array(
                  CURLOPT_URL => $url,
                  CURLOPT_RETURNTRANSFER => true,
              ); 

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $content  = curl_exec($ch);
        curl_close($ch);
        $resultData = json_decode($content);
        if(!empty($resultData) && $resultData->msg=='success'){

            $update_q = "UPDATE `publisher_master` SET company_id='".$resultData->companyID."',gam_email='".$inviteEmail."', mcm_status='invite',mcm_nonmcm_status='0' where pub_id=".$pub_id."";
            #prepare query
            $rowU = $this->conn->prepare($update_q);
                #execute query 
           if($rowU->execute()){
              return true;
           }else{
              return false;
           }
            
          
        }  
    }
    Public function appCheck($uniq_id,$memberType,$appName){
         $subquery1 = "select * from publishers_app where pub_uniq_id='".$uniq_id."' AND app_type='".$memberType."' AND app_name='".$appName."'";
           #prepare query
        $row = $this->conn->prepare($subquery1);
            #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $newDataArr = $stmt_result->fetch_array(MYSQLI_ASSOC);
        return $newDataArr;
    } 

    #APP Invite
    public function getPubAppInvite(){

      if($this->memberType =='Android' || $this->memberType =='IOS'){

      if($this->appName ==''){
        $resultArray = array("msg"=>"false","message"=>"Please Enter App Name!","status_code"=>422);
        return $resultArray;
        exit;
      }
      if($this->appID == ''){
        $resultArray = array("msg"=>"false","message"=>"Please Enter App ID!","status_code"=>422);
        return $resultArray;
       exit;
      }
      if($this->appName != ''){

        $newDataArr1 = $this->appCheck($this->uniq_id,$this->memberType,$this->appName);      
        if(!empty($newDataArr1)){
          $resultArray = array("msg"=>"false","message"=>"App Name already exists, please check details!","status_code"=>422);
          return $resultArray;
          
          exit;
        }
      }
      if($this->appID != ''){

        $newDataArr1 = $this->appCheck($this->uniq_id,$this->memberType,$this->appName);
        if(!empty($newDataArr1)){
          $resultArray = array("msg"=>"false","message"=>"App ID already exists, please check details!","status_code"=>422);
          return $resultArray;
          exit;
        }
      }
      
      $qi_pub = 'INSERT INTO `publishers_app` (pub_id,pub_uniq_id,app_name,app_id, app_type) VALUES ('.$this->pub_id.',"'.$this->uniq_id.'", "'.$this->appName.'","'.$this->appID.'", "'.$this->memberType.'")';
        
       #prepare query
        $row = $this->conn->prepare($qi_pub);
        #execute query
      $resultArray =array();   
      if($row->execute()){
          $resultArray = array("msg"=>"success","text"=>"App Added Successfully");
          return $resultArray;
        }else{

        return $resultArray;
        }
    }else{
      $resultArray = array("msg"=>"false","message"=>"Please Check App Type!","status_code"=>422);
      return $resultArray;
    }

  }


 }
?>