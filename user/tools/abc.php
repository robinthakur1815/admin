  
  public function getInvoiceApproveName(){
	  
	  //echo"jhvsdjhzc";
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
  
   public function getApiDocData(){
	 $dompdf = new DOMPDF(); 
  $html = '
    <div class="card uper">
  <div class="card-header">
    Add Disneyplus Shows
  </div>
  <div class="card-body">
      <div class="alert alert-danger">
        <ul>
            hvdhgzca
        </ul>
      </div><br />
  </div>
</div>
    </html>';

//$dompdf->loadHtml($bodymail);
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->setPaper('A4', 'portrait');
$dompdf->stream();
$canvas = $dompdf->get_canvas();
$font = Font_Metrics::get_font("helvetica", "bold");
$canvas->page_text(16, 800, "Page: {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0,0,0));
$dompdf->stream("sample.pdf");



// $output = $dompdf->output();
// $filename= "invoice_".str_replace("/","_",$this->apidoc).".pdf";
    
//     // file_put_contents("/home/apiauxoads/public_html/assets/api/admin/user/invoice/invoice_upload/".$filename, $output);
//     file_put_contents("/home/safedev/public_html/assets/api/admin/user/tools/upload/".$filename, $output);
	
// 	return $filename;
  }