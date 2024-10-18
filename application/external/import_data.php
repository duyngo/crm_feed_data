<?php
@set_time_limit(0);
@ini_set('max_execution_time', 0);
//echo 'Working.... ';

function get_download_save_file($file_url, $save_to){

    // Use file_get_contents() to read the file from the URL
    $fileContents = file_get_contents($file_url);
    if ($fileContents !== false) {
        // Save the file to the specified location using file_put_contents()
        if (file_put_contents($save_to, $fileContents)) {
            echo "File downloaded and saved to: $save_to";
        } else {
            echo "Error saving the file.";
        }
       
        // Split the file contents by line breaks to get each row
        $rows = explode(PHP_EOL, $fileContents);
        $data = [];
    
        foreach ($rows as $row) {
            // Convert the row into an array using str_getcsv (handles CSV fields)
            $data[] = str_getcsv($row);
        }
        
        return $data;
    
    } else {
        echo "Error downloading the file.";
    }
}

function downloadLargeFile($url, $output_file_path) {
    $fp = fopen($output_file_path, 'w+');
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024);  // Set buffer size (128KB)
    curl_setopt($ch, CURLOPT_TIMEOUT, 600); // Timeout in seconds
    
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    echo "File downloaded and saved to: $output_file_path <br/>";
}
    

function process_dicker_data(){
    $CI = & get_instance();

    $file_url = 'https://portal.dickerdata.com.au/Download?file=raqJl1tyq4ZEzYDHtVUsOuSl4zAncwx%2Fzg%2Bx0ubLlU%2FenFx3QTFg2jNviQgIJcThjqyfwPHYyhsoCvjDX8xtrx1WQ2i3huc%2B%2FvGHrRkVJXCT9rf08l5fbXhHHBXMAayhGWNlC62UBLZqA9qvGGDMpBRQ3Do7jEnK&fileName=DickerDataDataFeedCSV.csv&displaySaveAs=True';
    $output_file_path = __DIR__ . "/DickerData.csv"; 

    //$data = get_download_save_file($file_url, $file_name);
    downloadLargeFile($file_url, $output_file_path);

    $map_col_excel2db = [
        'StockCode' => 'stock_code',
        'Vendor' => 'stock_vendor',
        'VendorStockCode' => 'stock_vendorstockcode',
        'StockDescription' => 'stock_description_short',
        'PrimaryCategory' => 'stock_cat1',
        'SecondaryCategory' => 'stock_cat2',
        'TertiaryCategory' => 'stock_cat3',
        'RRPEx' => 'stock_price_rrpex',
        'DealerEx' => 'stock_price_dealerex',
        'StockAvailable' => 'stock_available',
        'ETA' => 'stock_eta',
        'Status' => 'stock_status',
        'Type' => 'stock_type'
    ];

    //get first element of data
  
    $bulk_param_data = array();
    if (($handle = fopen($output_file_path, "r")) !== FALSE) {
        // Get the header row to determine CSV columns
        $header_excel = fgetcsv($handle);
        $bulk_param_data = array();
        $row_count = 0;
        while (($row_data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row_count ++;
            $param_data = array();
            foreach ($map_col_excel2db as $csvColumn => $dbColumn) {
                // Find the index of the CSV column in the header
                $csvIndex = array_search($csvColumn, $header_excel); 
                // Get the corresponding CSV value, fallback to empty string if not found
                $param_data[$dbColumn] = $row_data[$csvIndex] ?? ''; 
            }
    
            $bulk_param_data[] = $param_data;
            if($row_count % 300 == 0){
                $CI->db->insert_batch('crm_product_stock', $bulk_param_data);
                $bulk_param_data = array();
            }
        }
    }
    if(!empty($bulk_param_data)){
        $CI->db->insert_batch('crm_product_stock', $bulk_param_data);
    }

    echo "Done import DickerData.<br/>";
}

process_dicker_data();




