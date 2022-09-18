<?php
#Author By ST
class Bankdetail{
  
    #database connection and table name
    private $conn;
    private $table_name = "bank_details";
    private $table_name_pub = "publisher_master";
    
    #object properties
    public $uniq_id;
    public $child_net_code;
    public $org_type;
    public $org_country;
    public $org_address;
    public $org_city;
    public $org_state;
    public $org_postalcode;
    public $acc_holder_name;
    public $bank_name;
    public $account_number;        
    public $bank_ifsc;
    public $bank_swift_code;
    public $bank_address;
    public $bank_city;
    public $bank_state;
    public $bank_postalcode;
    public $bank_acctype;
    public $GST_address;
    public $GST_city;
    public $GST_state;
    public $GST_postalcode;
    public $GST_num;
    public $PAN_num;
    public $pan_card;
    public $aadhaar_card;
    public $gst_certify;
    public $check_copy;
    public $incorp_cf;

    #constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

   #create get bank details
    public function getbankdetail(){
    $queryFetch= 'SELECT * FROM '.$this->table_name.' WHERE uniq_id ="'.$this->uniq_id.'"';
    #prepare query
    $row = $this->conn->prepare($queryFetch);
    #execute query 
    $row->execute();
    return $row;    
    }
	public function getpub_bankdetail(){
		$queryFetch= 'SELECT * FROM '.$this->table_name.' WHERE uniq_id ="'.$this->uniq_id.'"';
		$queryFetch= 'SELECT bd.*,pm.country_code_id FROM '.$this->table_name.' bd join '.$this->table_name_pub.' pm on pm.pub_uniq_id = bd.uniq_id AND bd.uniq_id="'.$this->uniq_id.'" GROUP by bd.uniq_id';
		#prepare query
		$row = $this->conn->prepare($queryFetch);
		#execute query 
		$row->execute();
		
		return $row;    
    }
    public function generateImage($pan_card)
    {
        if (!empty($pan_card)) {
            $folderPath = "../../uploads/";
            $image_parts = explode(";base64,", $pan_card);
			if($image_parts[0]=='data:application/pdf'){
				$extension = ".pdf";
			}else{
				$extension = ".png";
			}
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $file_name = uniqid() . $extension;
            $file = $folderPath . $file_name;
            file_put_contents($file, $image_base64);
            return $file_name;
			
        }
    }

    public function generateAadhaar($aadhaar_card)
    {
        if (!empty($aadhaar_card)) {
            $folderPath = "../../uploads/";
            $image_parts = explode(";base64,", $aadhaar_card);
			if($image_parts[0]=='data:application/pdf'){
				$extension = ".pdf";
			}else{
				$extension = ".png";
			}
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $file_name = uniqid() . $extension;
            $file = $folderPath . $file_name;
            file_put_contents($file, $image_base64);
            return $file_name;
        }
    }

    public function generateGst($gst_certify)
    {
        if (!empty($gst_certify)) {
            $folderPath = "../../uploads/";
            $image_parts = explode(";base64,", $gst_certify);
			if($image_parts[0]=='data:application/pdf'){
				$extension = ".pdf";
			}else{
				$extension = ".png";
			}
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $file_name = uniqid() . $extension;
            $file = $folderPath . $file_name;
            file_put_contents($file, $image_base64);
            return $file_name;
        }
    }

    public function generateCheck($check_copy)
    {
        if (!empty($check_copy)) {
            $folderPath = "../../uploads/";
            $image_parts = explode(";base64,", $check_copy);
			if($image_parts[0]=='data:application/pdf'){
				$extension = ".pdf";
			}else{
				$extension = ".png";
			}
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $file_name = uniqid() . $extension;
            $file = $folderPath . $file_name;
            file_put_contents($file, $image_base64);
            return $file_name;
        }
    }

    public function generateIncorpcf($incorp_cf)
    {
        if (!empty($incorp_cf)) {
            $folderPath = "../../uploads/";
            $image_parts = explode(";base64,", $incorp_cf);
			if($image_parts[0]=='data:application/pdf'){
				$extension = ".pdf";
			}else{
				$extension = ".png";
			}
			
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $file_name = uniqid() . $extension;
            $file = $folderPath . $file_name;
            file_put_contents($file, $image_base64);
            return $file_name;
        }
    }


    public function createbankdetail() {
    $queryInsert= 'INSERT INTO ' . $this->table_name . '(uniq_id,acc_holder_name, acc_holder_address,bank_name,acc_number,bank_acc_type,bank_ifsc,swift_code,pan_number,gst_num,gst_address,gst_city,gst_state,gst_postal,bank_address,bank_city,bank_state,bank_postal,city,state,country,postal_code,company_type,aadhaar_card_file,pan_card_file,gst_certificate,cancel_check_file,incorp_certificate_fille) VALUES ("'.$this->uniq_id.'", "'.$this->acc_holder_name.'", "'.$this->org_address.'", "'.$this->bank_name.'","'.$this->account_number.'","'.$this->bank_acctype.'","'.$this->bank_ifsc.'","'.$this->bank_swift_code.'","'.$this->PAN_num.'","'.$this->GST_num.'","'.$this->GST_address.'","'.$this->GST_city.'","'.$this->GST_state.'","'.$this->GST_postalcode.'","'.$this->bank_address.'","'.$this->bank_city.'","'.$this->bank_state.'","'.$this->bank_postalcode.'","'.$this->org_city.'","'.$this->org_state.'","'.$this->org_country.'","'.$this->org_postalcode.'","'.$this->org_type.'","'.$this->aadhaar_card.'","'.$this->pan_card.'","'.$this->gst_certify.'","'.$this->check_copy.'","'.$this->incorp_cf.'")';
    #prepare query
    $row1 = $this->conn->prepare($queryInsert);
        #execute query 
        if($row1->execute()){ 
          return true;
        }

    }

}

?>