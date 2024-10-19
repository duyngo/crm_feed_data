<?php
@set_time_limit(0);
@ini_set('max_execution_time', 0);

function get_download_save_file($file_url, $output_file_path){

    // Use file_get_contents() to read the file from the URL
    $fileContents = file_get_contents($file_url);
    if ($fileContents !== false) {
        // Save the file to the specified location using file_put_contents()
        if (file_put_contents($output_file_path, $fileContents)) {
            echo "File downloaded and saved to: $output_file_path <br/>";
        } else {
            die("Error saving the file.");
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
        die("Error downloading the file.");
    }
}

function save_data2DB($data, $map_col_excel2db){
    $CI = & get_instance();
    //get first element of data
    $header_excel = array_shift($data);
    $bulk_param_data = array();
    foreach($data as $index => $row){
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        $param_data = array();
        foreach ($map_col_excel2db as $csvColumn => $dbColumn) {
            // Find the index of the CSV column in the header
            $csvIndex = array_search($csvColumn, $header_excel); 
            // Get the corresponding CSV value, fallback to empty string if not found
            $param_data[$dbColumn] = $row[$csvIndex] ?? ''; 
        }

        $bulk_param_data[] = $param_data;
        if($index % 300 == 0){
            $CI->db->insert_batch('crm_product_stock', $bulk_param_data);
            $bulk_param_data = array();
        }
    }
    if(!empty($bulk_param_data)){
        $CI->db->insert_batch('crm_product_stock', $bulk_param_data);
    }
}

function process_dicker_data(){

    $file_url = 'https://portal.dickerdata.com.au/Download?file=raqJl1tyq4ZEzYDHtVUsOuSl4zAncwx%2Fzg%2Bx0ubLlU%2FenFx3QTFg2jNviQgIJcThjqyfwPHYyhsoCvjDX8xtrx1WQ2i3huc%2B%2FvGHrRkVJXCT9rf08l5fbXhHHBXMAayhGWNlC62UBLZqA9qvGGDMpBRQ3Do7jEnK&fileName=DickerDataDataFeedCSV.csv&displaySaveAs=True';
    $output_file_path = __DIR__ . "/DickerData.csv"; 

    $data = get_download_save_file($file_url, $output_file_path);

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

    save_data2DB($data, $map_col_excel2db);

    echo "Done import DickerData.<br/>";
}

process_dicker_data();




