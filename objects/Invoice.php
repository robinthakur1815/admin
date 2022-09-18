<?php
#Author BY AD
require_once dirname(__FILE__).'/dompdf/lib/html5lib/Parser.php';
require_once dirname(__FILE__).'/dompdf/src/Autoloader.php';

Dompdf\Autoloader::register();
use Dompdf\Dompdf;
class Invoice{
  
    #database connection and table name
    private $conn;
    public $connMongoDb;
    private $table_name = "publisher_invoice";
    private $table_bank = "bank_details";
    
  
    #object properties
    public $uniq_id;
    public $child_net_code;
    public $curr_month_year;
    public $month_year;
    public $manager_name;
    public $manager_email;
    public $pub_name;
    public $pub_email;
    public $month;
    public $invoice_number;
    public $invoice_date;

    #constructor with $db as database connection
    public function __construct($db,$connMongoDb){
        $this->conn = $db;
        $this->connMongoDb = $connMongoDb;
    }
    
    #Get invoice
    public function getInvoices(){
       $queryFetch='SELECT month, SUM(adx_dis_pub_rev) AS adx_display, SUM(adx_app_pub_rev) AS adx_app, SUM(adx_video_pub_rev) AS adx_video, SUM(adx_hbadser_pub_rev) AS hb, SUM(invalid_traffic) AS invalid,SUM(adx_deals_pub_rev) AS adx_deals_pub_rev, policy_disabled, invoice_status,pdf_name FROM ' . $this->table_name . ' WHERE child_net_code = "'.$this->child_net_code.'" AND pub_uniq_id="'.$this->uniq_id.'" GROUP BY month ORDER BY date ';
		// echo $queryFetch;die;
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        //return $row;
        $stmt_inv = $row->get_result();
        $rowInv = $stmt_inv->fetch_all(MYSQLI_ASSOC);
        $rowsInv = $stmt_inv->num_rows;
        if($rowsInv > 0){
         foreach($rowInv as $list){
            if($list['adx_deals_pub_rev'] > 0 ){
                $adx_deals = $list['adx_deals_pub_rev'];
            }else{
                $adx_deals = 0;
            }
            if(($list['adx_display']+$list['adx_app']+$list['adx_video']+$list['hb']+$adx_deals -$list['invalid'])>=1){
                $list_array[] = array(
                    'month'=>$list['month'],
                    'final_rev'=>number_format(($list['adx_display']+$list['adx_app']+$list['adx_video']+$list['hb']+$adx_deals -$list['invalid']),2),
                    'status'=>$list['invoice_status'],
                    'pdf_name'=>$list['pdf_name'],
                );
            }
            else{
                $list_array[] = array(
                    'month'=>$list['month'],
                    'final_rev'=>0,
                    'status'=>"No Billing",
                    'earningFrom'=>'Auxo Ads',
                    'pdf_name'=>'',
                );
             }
        } #foreach end
        
        
     } #row check end

    $check_flag = 0;
    foreach ($list_array as $kl=>$vl) {
        if(in_array($this->month_year,$vl)){
            $check_flag++;
        }
    }


    if($check_flag == 0){
        $list_array[] = array(
            'month'=>$this->month_year,
            'final_rev'=>"0.00",
            'status'=>"In Process",
            'earningFrom'=>'Auxo Ads',
            'pdf_name'=>'',
        );
       
    }

       #get direct deal data from mongoDB
       $command_lplist11 = new MongoDB\Driver\Command([
                    'aggregate' => 'global_third_party_order_test',
                    'pipeline' => [
                        ['$match'=>['child_net_code'=>$this->child_net_code]],
                        ['$group' => ['_id' => [
                                'payment_month' => '$payment_month',
                            ],
                            'total_revenue' => ['$sum' => '$total_revenue'],
                            ]],
                        ['$sort'=>['report_date'=>1]],
                    ],
                    'cursor' => new stdClass,
                ]);
       $result_non_google_currmnth = $this->connMongoDb->executeCommand('Directdeal_Order',$command_lplist11); 

       if(!empty($result_non_google_currmnth)){
        foreach ($result_non_google_currmnth as $val1) {
            foreach($list_array as $key => $value){
                if($val1->_id->payment_month == $value['month']){
                    if($value['final_rev'] > 0){
                        $list_array[$key]['final_rev'] = $value['final_rev'];
                    }else{
                        $list_array[$key]['final_rev'] = number_format($value['final_rev']+$val1->total_revenue,2);
                    }
                    $checkArray = array($value['month']);
                }
            }
                if(in_array($val1->_id->payment_month, $checkArray)){
                    continue;
                }else{
                    $list_array[] = array(
                        "month"=>$val1->_id->payment_month,
                        "final_rev"=>$val1->total_revenue,
                        'status'=>"In Process",
                        'earningFrom'=>'thirdparty',
                        'pdf_name'=>'',
                        );
                }
                
            }
        }else{

            $list_array[] = array(
                'month'=>$this->curr_month_year,
                'final_rev'=>"0.00",
                'status'=>"In Process",
                'earningFrom'=>'Auxo Ads',
                'pdf_name'=>'',
            );
        }
       // echo "<pre>";
       // print_r($list_array);die; 
      krsort($list_array);
        $list_array = array_values($list_array);

        usort($list_array, function($a, $b) {
            // return strtotime($a['month']) - strtotime($b['month']);
            return strtotime($b['month']) - strtotime($a['month']);
        });
        
       return $list_array; 
    
    }
  #Get BCR Rate
  public function bcr(){
      $queryFetch='SELECT amount as bcr FROM bcr WHERE bcr_month="'.$this->month_year.'"';
    
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $rowBcr = $stmt_result->fetch_array(MYSQLI_ASSOC);
        $rows = $stmt_result->num_rows;
        if($rows > 0){
         $bcr_rate = $rowBcr['bcr'];
        }else{
          $bcr_rate = "0.00";
        }
        return $bcr_rate;
  } 
  #Invoice View
  public function getInvoiceView(){
        $BCR = $this->bcr();
        #Get Invoice
        $prev_month_array = $this->getPubInvoice($BCR);
        $request_array['invoice'] = $prev_month_array;

        #Get Bank Details
        $bank_arrayRes = $this->getBankDetails();

        if(!empty($bank_arrayRes)){
           $request_array['bank'] = $bank_arrayRes;
           return $request_array;
        }else{
          $request_array = array();
          return $request_array;
        }        


  }
  #Get Publisher Invoice Revenue
  public function getPubInvoice($BCR){

     $query_previous_month="SELECT month,pub_uniq_id,pub_name, SUM(adx_dis_pub_rev) AS adx_display, SUM(adx_app_pub_rev) AS adx_app, SUM(adx_video_pub_rev) AS adx_video, SUM(adx_hbadser_pub_rev) AS hb, SUM(invalid_traffic) AS invalid,acc_mgr_id, policy_disabled, invoice_status, date,adx_deals_pub_rev FROM " . $this->table_name . " WHERE child_net_code = '".$this->child_net_code."' AND pub_uniq_id='".$this->uniq_id."' AND month='".$this->month_year."'";
    #prepare query
        $row = $this->conn->prepare($query_previous_month);
        #execute query 
        $row->execute();
        $stmt_result = $row->get_result();
        $val = $stmt_result->fetch_array(MYSQLI_ASSOC);

    if($val['adx_deals_pub_rev'] > 0){
            $adx_deals_pub_rev = $val['adx_deals_pub_rev'];
        }else{
            $adx_deals_pub_rev = 0;
        }
        $prev_month_array=array(
        'month'=> $val['month'],
        'bcr_rate'=> $BCR,
        'CAN' => $val['adx_display']+$val['adx_app']+$val['adx_video'],
        'CAP' => $val['hb'],
        'invalid_rev'=> $val['invalid'],
        'invoice_date'=>date('d-F-Y',strtotime($val['date'])),
        'invoice_number'=>date('Y/M',strtotime($this->month_year)),
        'acc_mgr_id'=> $val['acc_mgr_id'],
        'policy_disabled'=> $val['policy_disabled'],
        'status'=> $val['invoice_status'],
        'adx_deals_pub_rev'=> $adx_deals_pub_rev,
        );
        return $prev_month_array;

  } 
  #Get Bank details
  public function getBankDetails(){
    $query_bank_detail="SELECT * FROM " . $this->table_bank . " WHERE uniq_id = '".$this->uniq_id."'";
	
    #prepare query
        $rowBank = $this->conn->prepare($query_bank_detail);
        #execute query 
        $rowBank->execute();
        $stmt_resultB = $rowBank->get_result();
        $val_bank = $stmt_resultB->fetch_array(MYSQLI_ASSOC);
        $rowsBank = $stmt_resultB->num_rows;
        $bank_array = array();
        if($rowsBank > 0){
            if(ucwords($val_bank['country'])=='99' || empty($val_bank['country'])){
            $countryType = "domestic";
        }else{
            $countryType = "international";
        }
        $bank_array=array(
        
        'accountHolderName'=> $val_bank['acc_holder_name'],
        'accountHolderAddress'=> $val_bank['acc_holder_address'],
        'city'=> $val_bank['city'],
        'state'=> $val_bank['state'],
        'postal_code'=> $val_bank['postal_code'],
        'bankName'=>  $val_bank['bank_name'],
        'accountNumber'=>  $val_bank['acc_number'],
        'ifsc'=>  $val_bank['bank_ifsc'],
        'swift_code'=>  $val_bank['swift_code'],
        'country'=>  ucwords($val_bank['country']),
        'pan_number'=>  $val_bank['pan_number'],
        'gst_number'=>  $val_bank['gst_num'],
        'gst_address'=>  $val_bank['gst_address'],
        'gst_city'=>  $val_bank['gst_city'],
        'gst_state'=>  $val_bank['gst_state'],
        'gst_postal'=>  $val_bank['gst_postal'],
        'acountType'=>  ucwords($val_bank['bank_acc_type']),
        'bank_address'=>  $val_bank['bank_address'],
        'bank_city'=>  $val_bank['bank_city'],
        'bank_state'=>  $val_bank['bank_state'],
        'bank_postal'=>  $val_bank['bank_postal'],
        'status_bank'=>  $val_bank['status_bank'],
        'country_type'=>  $countryType,
        );
        return $bank_array;
      }else{
          
         return $bank_array; 
       }

  }
  
  public function getInvoiceApproveName(){
	  
	  
		$filename = $this->pubInvoiceGenerate();
	  
	  
		$query_update_status = "UPDATE ".$this->table_name." SET pdf_name='".$filename."' WHERE month='".$this->month."' AND pub_uniq_id='".trim($this->uniq_id)."'";
        #prepare query
        $rowStatus = $this->conn->prepare($query_update_status);
        #execute query 
		$rowStatus->execute();
		
		$query_invoice_name="SELECT * FROM " . $this->table_name . " WHERE pub_uniq_id = '".$this->uniq_id."' AND month='".$this->month_year."'";
		#prepare query
        $rowInvoiceName = $this->conn->prepare($query_invoice_name);
        #execute query 
        $rowInvoiceName->execute();
        $stmt_invoice_name = $rowInvoiceName->get_result();
        $val_invoice_name = $stmt_invoice_name->fetch_array(MYSQLI_ASSOC);
        $rowsinvoice_name = $stmt_invoice_name->num_rows;
		if($rowsinvoice_name > 0){
			$invoice_array=array(
				"pdf_name"=>$val_invoice_name['pdf_name'],
				"invoice_status"=>$val_invoice_name['invoice_status'],
				"month"=>$val_invoice_name['month'],
				"pub_uniq_id"=>$val_invoice_name['pub_uniq_id']
			);
		}else{
			$invoice_array = array();
		}
		
		return $invoice_array;
  }
  #Generate Invoice
  public function pubInvoiceGenerate(){ 
		#PDF object
		$dompdf = new Dompdf();
		
		$bank_arrayRes = $this->getBankDetails();
		$this->month_year = $this->month;
		#Get convertion rate
		$BCR = $this->bcr();
		#Get Invoice
		$data = $this->getPubInvoice($BCR);
		$data['final_rev'] = round($data['CAN']+$data['CAP']+$data['adx_deals_pub_rev'],2);
		#upload Invoice PDF
    $monthName = $this->month;
    if($bank_arrayRes['gst_state'] == 'Delhi'){
        $delhi_state = 1;
    }
    else{
        $delhi_state = 0;
    }

    if($bank_arrayRes['gst_number'] != '' && strlen($bank_arrayRes['gst_number']) > 10){
        $gst_flag = 1;
    }
    else{
        $gst_flag =0;
    }
    
    $bodymail='
    <table style="clear: both;width: 100%;border: 0px;border-collapse: collapse;font: 14px/1.4 , serif; ">
    <br>
        <tr>
            <td style="width: 55%; float:center;" align="center">';
            if($gst_flag ==0) {
            $bodymail .='
                <h1>PAYMENT INVOICE</h1>';
            }
            if($gst_flag ==1) {
            $bodymail .='
                <h1>PURCHASE ORDER</h1>';
            }
            $bodymail .='
            </td>
        </tr>
    <br>
    </table>
    <table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
    <tr>';
    if($gst_flag ==1) {
    $bodymail .='
        <td style="padding: 5px; width:50%;border-right:1px solid black; vertical-align:top !important ;">
            <strong>From,<br>'.$bank_arrayRes['accountHolderName'].'</strong><br><br>
            '.$bank_arrayRes['gst_address'].'<br>
            '.$bank_arrayRes['gst_city'].', '.$bank_arrayRes['gst_state'].' '.$bank_arrayRes['gst_postal'].'<br><br>
        </td>';
    }
    if($gst_flag ==0) {
    $bodymail .='
        <td style="padding: 5px; width:50%;border-right:1px solid black; vertical-align:top !important ;">
            <strong>From,<br>'.$bank_arrayRes['accountHolderName'].'</strong><br><br>
            '.$bank_arrayRes['accountHolderAddress'].'<br>
            '.$bank_arrayRes['city'].', '.$bank_arrayRes['state'].' '.$bank_arrayRes['postal_code'].'<br><br>
        </td>';
    }
    if($bank_arrayRes['country_type'] == 'international'){
        $bodymail .='
        <td style="padding: 5px; width:50%;border-left: 1px solid black;vertical-align:top !important ;">
            <strong>To,<br>Cyber Media Services Pte Limited</strong><br><br>
            1 North Bridge Road #07-10<br>
            Street Centre Singapore<br>
            179094
        </td>';
    }else{
        $bodymail .='
        <td style="padding: 5px; width:50%;border-left: 1px solid black;vertical-align:top !important ;">
            <strong>To,<br>Cyber Media Research & Services Limited</strong><br><br>
            D-74, Panchsheel Enclave, New Delhi -110017, India<br>
            Tel: +91 124 482 2222<br>
            http://www.cybermediaservices.net
        </td>';
    }
    $bodymail .='</tr>';
    
    if($bank_arrayRes['country_type'] == 'international'){
		if($gst_flag ==1) {
			$bodymail .='<tr>
				<td style="padding: 5px; width:50%;border-right: 1px solid black;border-bottom: 1px solid black;">
					GST :  '.$bank_arrayRes['gst_number'].'<br>
				</td>
				<td style="padding: 5px; width:50%;border-left:1px solid black;border-bottom: 1px solid black;">
					GST :  201725642G<br>
				</td>
			</tr>';
		}else{
			$bodymail .='<tr>
				<td style="padding: 5px; width:50%;border-right: 1px solid black;border-bottom: 1px solid black;"></td>
				<td style="padding: 5px; width:50%;border-left:1px solid black;border-bottom: 1px solid black;"></td>
			</tr>';
		}
        
    }else{
        $bodymail .='<tr>
            <td style="padding: 5px; width:50%;border-right: 1px solid black;border-bottom: 1px solid black;">
                GSTIN :  '.$bank_arrayRes['gst_number'].'<br>
                PAN Number :  '.$bank_arrayRes['pan_number'].'<br><br>
            </td>
            <td style="padding: 5px; width:50%;border-left:1px solid black;border-bottom: 1px solid black;">
                GSTIN :  07AAACI2770A1Z3<br><br>
            </td>
        </tr>';
    }
    
    
    
    
    $bodymail .='<tr>
        <table style="clear: both;width: 100%;border-top: 1px solid black;border-right: 1px solid black;border-left: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">';
        if($gst_flag==1) {
        $bodymail .='
            <tr>
                <td style="padding: 5px;"><br>
                    Invoice Date:  '.$this->invoice_date.'<br><br>
                    Revenue Month:  '.$this->month.'<br><br>
                </td>
            </tr>';
        }
        if($gst_flag==0) {
        $bodymail .='
            <tr>
                <td style="padding: 5px;"><br>
                    Invoice Number:  '.$this->invoice_number.'<br><br>
                    Invoice Date:  '.$this->invoice_date.'<br><br>
                    Revenue Month:  '.$this->month.'<br><br>
                </td>
            </tr>';
        }
        $bodymail .='</table></tr></table>';
        
        if($bank_arrayRes['country_type'] == 'international'){
            
            $bodymail .='<table cellspacing="0" cellpadding="0" style="clear: both;border-left: 1px solid; border-top: 1px solid;width: 100%;font: 14px/1.5, serif;">
          <tr>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 50%">Description</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 15%">Qty</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 15%">Rate</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 20%">Amount (US$)</th>
          </tr>
          <tr>
              <td style="border-right:1px solid black;padding: 8px;width: 40%;">
              Auxo Ads Network Earnings For '.$this->month.'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 15%;">1</td>
              <td style="border-right:1px solid black;padding: 5px;text-align:right;width: 15%;">'.number_format($data['CAN'],2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 20%;">'.number_format($data['CAN'],2).'</td>
          </tr>';
            if(($data['CAP']+$data['adx_deals_pub_rev']) > 0){
              $bodymail .='
              <tr>
                  <td style="border-right:1px solid black;padding: 8px;width: 40%;">
                  CyberAds Pro Earnings For '.$this->month.'</td>
                  <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 15%;">1</td>
                  <td style="border-right:1px solid black;padding: 5px;text-align:right;width: 15%;">'.number_format(($data['CAP']+$data['adx_deals_pub_rev']),2).'</td>
                  <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 20%;">'.number_format(($data['CAP']+$data['adx_deals_pub_rev']),2).'</td>
              </tr>';
            }
            
            $bodymail .='
          <tr>
              <td style="border-right:1px solid black;padding: 8px;">
              Invalid Traffic Deduction For '.$this->month.'
              </td>
              <td style="border-right:1px solid black;padding: 5px;text-align:right;">1</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;">(-) '.number_format($data['invalid_rev'],2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;">(-) '.number_format($data['invalid_rev'],2).'</td>
             
          </tr></table>';
            
        }else{
            $bodymail .='<table cellspacing="0" cellpadding="0" style="clear: both;border-left: 1px solid; border-top: 1px solid;width: 100%;font: 14px/1.5, serif;">
          <tr>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 50%">Description</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 15%">USD</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 15%">Conv. Rate</th>
              <th style="background: #eee;border-right: 1px solid;border-bottom: 1px solid; black;padding: 5px; width: 20%">INR</th>
              
          </tr>
          <tr>
              <td style="border-right:1px solid black;padding: 8px;width: 40%;">
              Auxo Ads Network Earnings For '.$this->month.'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 15%;">$ '.number_format($data['CAN'],2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align:right;width: 15%;">Rs. '.number_format($data['bcr_rate'],2).'
                </td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 20%;">Rs. '.number_format($data['CAN']*$data['bcr_rate'],2).'</td>
          </tr>';
          if(($data['CAP']+$data['adx_deals_pub_rev']) > 0){
          $bodymail .='
          <tr>
              <td style="border-right:1px solid black;padding: 8px;width: 40%;">
              CyberAds Pro Earnings For '.$this->month.'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 15%;">$ '.number_format(($data['CAP']+$data['adx_deals_pub_rev']),2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align:right;width: 15%;">Rs. '.number_format($data['bcr_rate'],2).'
                </td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;width: 20%;">Rs. '.number_format(($data['CAP']+$data['adx_deals_pub_rev'])*$data['bcr_rate'],2).'</td>
          </tr>';
          }
          
          $bodymail .='
          <tr>
              <td style="border-right:1px solid black;padding: 8px;">
              Invalid Traffic Deduction For '.$this->month.'
              </td>
              <td style="border-right:1px solid black;padding: 5px;text-align:right;"> $ '.number_format($data['invalid_rev'],2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;">Rs. '.number_format($data['bcr_rate'],2).'</td>
              <td style="border-right:1px solid black;padding: 5px;text-align: right;">(-) Rs. '.number_format($data['invalid_rev']*$data['bcr_rate'],2).'</td>
             
          </tr>
          
        </table>';
        }
        
        if($bank_arrayRes['country_type'] == 'international'){
            $bodymail .='<table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
            <tr>
              <th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
              <th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Sub Total</th>
              <td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">'.number_format(($data['final_rev'])-($data['invalid_rev']),2).'</td>
            </tr>
        </table>';
        }else{
            $bodymail .='<table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
                <tr>
                  <th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
                  <th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Sub Total</th>
                  <td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">Rs. '.number_format(($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']),2).'</td>
                </tr>
            </table>';
			
        }
        
		
		
		
        if($bank_arrayRes['country_type'] == 'international'){
            
			if($bank_arrayRes['country']==192){
				if($gst_flag==1) {
					$bodymail .='
					<table cellspacing="0" cellpadding="0" style="clear: both;border-left: 1px solid; border-top: 1px solid;width: 100%;font: 14px/1.5, serif;">
						<tr>
							<td style="padding: 8px;">
							IGST @ 0%
							</td>
							<td style="padding: 5px;text-align:center;"></td>
							<td style="padding: 5px;text-align: right;"></td>
							<td style="border-right:1px solid black;padding: 5px;text-align: right;">0.00</td>
						   
						</tr>
					</table>';
					$bodymail .='
					<table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
						<tr>
							<th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
							<th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Grand Total</th>
							<td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">'.number_format(($data['final_rev'] - $data['invalid_rev']),2).'</td>
						</tr>
					</table>';
				}
				if($gst_flag==0) {
					$bodymail .='
					<table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
						<tr>
							<th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
							<th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Grand Total</th>
							<td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">'.number_format(($data['final_rev'])-($data['invalid_rev']),2).'</td>
						</tr>
					</table>';
				}
			}else{
				$bodymail .='
                <table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
                    <tr>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Grand Total</th>
                        <td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">'.number_format(($data['final_rev'])-($data['invalid_rev']),2).'</td>
                    </tr>
                </table>';
			}
            
        }else{
            if($delhi_state==1 && $gst_flag==1) {
                $bodymail .='
                <table cellspacing="0" cellpadding="0" style="clear: both;border-left: 1px solid; border-top: 1px solid;width: 100%;font: 14px/1.5, serif;">
                <tr>
                    <td style="padding: 8px;">
                    CGST @ 9%
                    </td>
                    <td style="padding: 5px;text-align:center;"></td>
                    <td style="padding: 5px;text-align: right;"></td>
                    <td style="border-right:1px solid black;padding: 5px;text-align: right;">Rs. '.number_format((($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']))*0.09,2).'</td>
                   
                </tr>
                <tr>
                    <td style="padding: 8px;">
                    SGST @ 9%
                    </td>
                    <td style="padding: 5px;text-align:center;"></td>
                    <td style="padding: 5px;text-align: right;"></td>
                    <td style="border-right:1px solid black;padding: 5px;text-align: right;">Rs. '.number_format((($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']))*0.09,2).'</td>
                   
                </tr>
                </table>';
            }
            if($delhi_state==0 && $gst_flag==1) {
                $bodymail .='
                <table cellspacing="0" cellpadding="0" style="clear: both;border-left: 1px solid; border-top: 1px solid;width: 100%;font: 14px/1.5, serif;">
                    <tr>
                        <td style="padding: 8px;">
                        IGST @ 18%
                        </td>
                        <td style="padding: 5px;text-align:center;"></td>
                        <td style="padding: 5px;text-align: right;"></td>
                        <td style="border-right:1px solid black;padding: 5px;text-align: right;">Rs.  '.number_format((($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']))*0.18,2).'</td>
                       
                    </tr>
                </table>';
            }
            if($gst_flag==1) {
                $bodymail .='
                <table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
                    <tr>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Grand Total</th>
                        <td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">Rs. '.number_format((($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate'])+(($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']))*0.18),2).'</td>
                    </tr>
                </table>';
            }
            if($gst_flag==0) {
                $bodymail .='
                <table style="clear: both;width: 100%;border: 1px solid black;border-collapse: collapse;font: 14px/1.5 , serif;">
                    <tr>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 50%;"></th>
                        <th style="background: #eee;border: 1px solid black;padding: 5px; width: 30%; text-align:right;">Grand Total</th>
                        <td style="background: #eee;border: 1px solid black;padding: 5px;width: 20%;text-align: right;">Rs. '.number_format(($data['final_rev']*$data['bcr_rate'])-($data['invalid_rev']*$data['bcr_rate']),2).'</td>
                    </tr>
                </table>';
            }
        }
        
$bodymail .='
    <table style="clear: both;width: 100%;border-left: 1px solid;border-right: 1px solid;border-bottom: 1px solid;border-collapse: collapse;font: 14px/1.5 , serif;">
        <tr>
            <td style="padding: 5px; width: 60%;"><br>
            <strong>For '.$bank_arrayRes['accountHolderName'].'</strong><br><br><br>


            <strong>Please Remit the proceeds: By A/C payee cheque/dd in the name of '.$bank_arrayRes['accountHolderName'].'</strong><br><br><br>
                <table style="clear: both;width: 100%;border: 0px;border-collapse: collapse;font: 14px/1.5 , serif;">';
                    if($bank_arrayRes['country_type'] == 'international'){
                        $bodymail .='<tr>
                            <td style="padding: 5px; width: 60%;">
                                <strong>Bank Details:<br><br>
                                Bank Name: '.$bank_arrayRes['bankName'].'<br><br>
                                Bank Account #: '.$bank_arrayRes['accountNumber'].'<br><br>
                                Bank A/C Type #: '.$bank_arrayRes['acountType'].'<br><br>
                                Swift Code: '.$bank_arrayRes['swift_code'].'<br><br>
                                Branch Address: '.$bank_arrayRes['bank_address'].' '.$bank_arrayRes['bank_city'].' '.$bank_arrayRes['bank_state'].' '.$bank_arrayRes['bank_postal'].'</strong><br>
                            </td>
                        </tr>';
                    }else{
                        $bodymail .='<tr>
                            <td style="padding: 5px; width: 60%;">
                                <strong>Bank Details:<br><br>
                                Bank Name: '.$bank_arrayRes['bankName'].'<br><br>
                                Bank Account #: '.$bank_arrayRes['accountNumber'].'<br><br>
                                Bank A/C Type #: '.$bank_arrayRes['acountType'].'<br><br>
                                IFSC/RTGS/NEFT Code: '.$bank_arrayRes['ifsc'].'<br><br>
                                Branch Address: '.$bank_arrayRes['bank_address'].' '.$bank_arrayRes['bank_city'].' '.$bank_arrayRes['bank_state'].' '.$bank_arrayRes['bank_postal'].'</strong><br>
                            </td>
                        </tr>';
                    }
                    
                    
                $bodymail .='</table>
            </td>
        </tr>
    </table>
    <table style="clear: both;width: 100%;border-collapse: collapse;font: 14px/1.5 , serif;">
        <tr>
            <td style="width: 55%; float:center;" align="center"><br>
            <strong>* This invoice is system generated. No Signature is required.</strong><br><br><br>
            </td>
        </tr>
    </table>
    ';
    
    $dompdf->loadHtml($bodymail);
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->render();
    // Output the generated PDF to Browser
    //$dompdf->stream();
    
    $output = $dompdf->output();
     
    $filename= "invoice_".str_replace("/","_",$this->invoice_number).".pdf";
    
    file_put_contents("/home/safedev/public_html/assets/api/admin/user/invoice/invoice_upload/".$filename, $output);
	
	return $filename;
  }
  #Invoice Approve
  public function getInvoiceApprove(){ 
    #Call mailer 
    include_once('../../mailerLib/class.phpmailer.php');
    
    #PDF object
    $dompdf = new Dompdf();
     #Get Bank Details
    $bank_arrayRes = $this->getBankDetails();
    $this->month_year = $this->month;
    #Get convertion rate
    $BCR = $this->bcr();
    #Get Invoice
    $data = $this->getPubInvoice($BCR);
    $data['final_rev'] = round($data['CAN']+$data['CAP']+$data['adx_deals_pub_rev'],2);
    if($bank_arrayRes['status_bank'] == 2){
        $subject_bank = "New Publisher Account";
        $body_bbb = '<!DOCTYPE>
<html>
<head>
  <meta charset="utf-8" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>New Publisher Account</title> 
</head>

<body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
				<center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
				<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
					<tbody>
					<tr style="background: #D6C4FB !important;">
						<td style="padding: 10px!important; color: #000; text-align: center;"> 
						<!-- <a href="#" target="_blank">
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo1.png" style="border: none ; width: 150px; margin-top: 1px;" /> 
						</a>  -->
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
						</td>

						<!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
						<p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
						</td>-->
					</tr>
					</tbody>
				</table>

        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
          <tbody>
            <tr>
              <td>
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>Sir,</p>

                <p style="font-size: 18px; line-height: 33px;">New publisher account '.$this->pub_name.' has been created. Please login and verify the bank details.</p>

                <p> 
                <a href="http://safedev.cybermediaservices.in/" style="display: inline-block;
                      padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Login</a>
                </p>
                
				<span style="font-size: 18px; line-height: 28px; font-weight: 500;">Thanks!</span><br>
				<span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                </td>
              </tr>
            </tbody>
          </table>

        <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
          <span style="position: relative; top: -5px;">
            Visit us at
            <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
            <br/>
             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
             </span>
          </span>
        </center>
      </center>
  </body>
</html>';
		
		
        $mail22             = new PHPMailer();
        $mail22->IsSMTP();  
        $mail22->Host       = "103.76.212.101";
        $mail22->SMTPDebug  = 1; 
        $mail22->SMTPAuth   = true;   
        $mail22->Username   = 'noreply@cybermedia.co.in';
        $mail22->Password   = 'K6Cx*5G%W8j';
        $mail22->Port = "587";
        $mail22->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
        $mail22->Subject = $subject_bank;           
        
        $mail22->addAddress('sandeepy@cybermedia.co.in');
        // $mail22->addAddress('srishtis@cybermedia.co.in');
        // $mail22->addAddress('omp@cybermedia.co.in');
        // $mail22->addCC('shankarv@cmrindia.com');
        $mail22->MsgHTML($body_bbb);
		$mail22->Body = $body_bbb;
        $mail22->send();
        $mail22->ClearAddresses();
        $mail22->ClearCCs();
        $mail22->clearAllRecipients();
       
      }
    
		$filename = $this->pubInvoiceGenerate();

      #Mail to publsher and update filname and status
      $mailToPublisher = $this->mailPublisher($filename);
      return $mailToPublisher;

    }

    #mail
    public function mailPublisher($filename){
		
		$body='<!DOCTYPE>
<html>
<head>
  <meta charset="utf-8" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@500&display=swap" rel="stylesheet">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Auxo Ads invoice approve </title> 
</head>

<body style="margin: 0px; background: #f8f8f8; padding: 0px 0px; font-family: Noto Sans KR , sans-serif;  line-height: 28px;  height: 100%;  width: 100%; color: #514d6a;">
				<center style="max-width: 750px; padding: 50px 0;  margin: 0px auto; font-size: 18px;">
				<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
					<tbody>
					<tr style="background: #D6C4FB !important;">
						<td style="padding: 10px!important; color: #000; text-align: center;"> 
						<!-- <a href="#" target="_blank">
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo1.png" style="border: none ; width: 150px; margin-top: 1px;" /> 
						</a>  -->
							<img src="https://safedev.cybermediaservices.in/assets/registerlp/img/new/safe-logo.png" alt="auxo ads" style="border: none ; width: 150px; margin-top: 1px; color: #fff; font-size: 30px;float: left;margin-left: 50px;" /> 
						</td>

						<!--<td style="padding: 10px; color: #000; font-family: Noto Sans KR ,  sans-serif; text-align: center;"> 
						<p style="font-size: 18px;">Analyze. Predict. Monetize. </p>
						</td>-->
					</tr>
					</tbody>
				</table>
        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; padding: 40px;  color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #fff; border: 5px solid #f2f2f0;">
          <tbody>
            <tr>
              <td>
                <p style="font-size: 18px; line-height: 33px;">Dear<span>&nbsp;</span>'.ucwords($this->pub_name).',</p>

                <p style="font-size: 18px; line-height: 33px;"></p>

                <p style="font-size: 18px; line-height: 33px;"> Thank you for approving the payment advice for '.$this->month.'. Kindly email the corresponding invoice to accounts@auxoads.com in order to process the payment. </p>

                <p style="font-size: 18px; line-height: 33px;">For further analysis, tools, and features, please login to your </p>
               <p> 
                <a href="http://safedev.cybermediaservices.in/" style="display: inline-block;
                      padding: 8px 40px !important; margin: 0px 0px 8px;  font-size: 18px; color: #fff; background: #8d70fa; border-radius: 100px; font-family: Noto Sans KR , sans-serif; text-align: left; text-decoration: none;">Auxo Ads Dashboard</a>
                </p>
				<span style="font-size: 18px; line-height: 28px; font-weight: 500;">Happy Earnings!</span><br>
				<span style="font-size: 18px; line-height: 28px; font-weight: 500;">Auxo Ads</span>
                </td>
              </tr>
            </tbody>
          </table>

        <center style="text-align: center; font-size: 15px; color: rgba(0, 0, 0, 0.87); font-family: Noto Sans KR , sans-serif; background: #f2f2f0; padding: 10px 0px 10px 0px;">
          <span style="position: relative; top: -5px;">
            Visit us at
            <a style="color: #8d70fa; text-decoration: underline;font-family: Noto Sans KR , sans-serif; font-size: 14px;" href="https://safe.cybermediaservices.net/">auxoads.com</a>
            <br/>
             <span style="font-size: 14px;">Copyright 2022. All Rights Reserved.
             </span>
          </span>
        </center>
      </center>
  </body>
</html>';
		
		
		
		
		
        

    $subject = "Payment Approved Confirmation for ".$this->month;
    $mail             = new PHPMailer();
    $mail->IsSMTP();  
    $mail->Host       = "103.76.212.101";
    $mail->SMTPDebug  = 1; 
    $mail->SMTPAuth   = true;   
    $mail->Username   = 'noreply@cybermedia.co.in';
    $mail->Password   = 'K6Cx*5G%W8j';
    $mail->Port = "587";
    $mail->SetFrom('noreply@cybermedia.co.in', 'Auxo Ads');
    $mail->Subject = $subject;          
    // $mail->addAddress($this->pub_email);
    // $mail->addCC($this->manager_email);
    //$mail->addAddress('ankurdu@cybermedia.co.in');
    $mail->addAddress('sandeepy@cybermedia.co.in');
    // $mail->addAddress('srishtis@cybermedia.co.in');
    $mail->MsgHTML($body);
    $mail->Body = $body;
    if($mail->send()) {
       $query_update_status = "UPDATE ".$this->table_name." SET invoice_status='Payment Under Process', pdf_name='".$filename."' WHERE month='".$this->month."' AND pub_uniq_id='".trim($this->uniq_id)."'";
        #prepare query
        $rowStatus = $this->conn->prepare($query_update_status);
        #execute query 
        
        if($rowStatus->execute()) {
            return true;
        }
        else{
            return false;
        }
    }
    else{
        return false;
    }
    $mail->ClearAddresses();
    $mail->ClearCCs();
    $mail->clearAllRecipients();

    }
	
	public function getnotification(){
		$queryFetch='SELECT * from ' . $this->table_name . ' WHERE invoice_status = "Approval Pending" AND pub_uniq_id="'.$this->uniq_id.'" GROUP BY month ORDER BY date ';
		// echo $queryFetch;die;
        #prepare query
        $row = $this->conn->prepare($queryFetch);
        #execute query 
        $row->execute();
		
		$stmt_resultB = $row->get_result();
        $val_invoice = $stmt_resultB->fetch_array(MYSQLI_ASSOC);
        $rowsInvoice = $stmt_resultB->num_rows;
        $invoice_array = array();
        if($rowsInvoice > 0){
			$invoice_array=array('invoice_status'=>$val_invoice['invoice_status']);
			return $invoice_array;
		}else{
			return $invoice_array; 
		}
	}
}
?>