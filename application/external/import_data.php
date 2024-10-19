<?php
@set_time_limit(0);
@ini_set('max_execution_time', 0);

define("BATCH_SIZE", 300);

function get_download_save_file($file_url, $output_file_path){

    if($file_url){
        // Use file_get_contents() to read the file from the URL
        $fileContents = file_get_contents($file_url);
        if ($fileContents !== false) {
            // Save the file to the specified location using file_put_contents()
            if (file_put_contents($output_file_path, $fileContents)) {
                echo "File downloaded and saved to: $output_file_path <br/>";
            } else {
                die("Error saving the file.");
            }    
        } else {
            die("Error downloading the file.");
        }
    }

    if(!file_exists($output_file_path)){
        //if not existed file return empty
        return [];
    }

    $fileHandle = fopen($output_file_path, 'r');
    if ($fileHandle !== false) {
        $data = [];
        // Read the CSV file line by line
        while (($row = fgetcsv($fileHandle)) !== false) {
            $data[] = $row;
        }

        // Close the file after reading
        fclose($fileHandle);

        return $data;
    } else {
        die("Error opening the file.");
    }

}

function save_data2DB($data, $map_col_excel2db, $supplier_code){
    $CI = & get_instance();
    //get first element of data
    $header_excel = array_shift($data);
    $bulk_param_data = array();
    foreach($data as $index => $row){
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        $param_data = array('supplier_code'=>$supplier_code);
        foreach ($map_col_excel2db as $csvColumn => $dbColumn) {
            //find divided col
            $number_divided = 0;
            if(strpos($csvColumn, "::divided") !== false){
                list($real_column, $divided_part) = explode("::", $csvColumn);
                $csvColumn = $real_column;
                list($text_divided, $number_divided) = explode("_", $divided_part);
            }
            //find concat col
            $concat_param_data = array();
            if(strpos($csvColumn, "CONCAT::") !== false){
                list($text_concat, $list_col) = explode("::", $csvColumn);
                $arr_concat_col = explode("_", $list_col);
                $concat_param_data = array();
                foreach($arr_concat_col AS $col_name){
                    $concatIndex = array_search($col_name, $header_excel); 
                    $concat_param_data[] = $row[$concatIndex] ?? '';
                }
            }
            // Find the index of the CSV column in the header
            $csvIndex = array_search($csvColumn, $header_excel); 
            // Get the corresponding CSV value, fallback to empty string if not found
            $param_data[$dbColumn] = $row[$csvIndex] ?? '';

            //extra checking update value
            if($number_divided){
                $round_result = ROUND((float)$param_data[$dbColumn] / (float)$number_divided, 4);
                $param_data[$dbColumn] = number_format($round_result,4,'.','');
            }
            if(!empty($concat_param_data)){
                $param_data[$dbColumn] = implode("", $concat_param_data);
            }
        }

        $bulk_param_data[] = $param_data;
        if($index % BATCH_SIZE == 0){
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

    //total = 13 columns
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

    save_data2DB($data, $map_col_excel2db, 'DIC001');

    echo "Done import DickerData.<br/>";
}

function process_leader_data(){

    $file_url = 'https://www.leadersystems.com.au/WSDataFeed.asmx/DownLoad?CustomerCode=U2FsdGVkX1%2B4nlE6rjlX0TC%2B8u9NRtVumyh2alt4vmv8tzvWxuQKlpWjBILXhVRN&WithHeading=true&WithLongDescription=true&DataType=7';
    $output_file_path = __DIR__ . "/LeaderData.csv"; 

    $data = get_download_save_file($file_url, $output_file_path);

    //total = 15 columns
    $map_col_excel2db = [
        "STOCK CODE" => "stock_code",
        "MANUFACTURER" => "stock_vendor",
        "MANUFACTURER SKU" => "stock_vendorstockcode",
        "SHORT DESCRIPTION" => "stock_description_short",
        "LONG DESCRIPTION" => "stock_description_long",
        "CATEGORY NAME" => "stock_cat1",
        "SUBCATEGORY NAME" => "stock_cat2",
        "RRP::divided_1.1" => "stock_price_rrpex",// divided 1.1
        "DBP" => "stock_price_dealerex",
        "CONCAT::AT_AA_AQ_AN_AV_AW" => "stock_available", //AT+AA+AQ+AN+AV+AW
        "ETAV" => "stock_eta",
        "WARRANTY" => "stock_warranty",
        "ALTERNATIVE REPLACEMENTS" => "stock_alternative",
        "IMAGE" => "stock_image",
        "BAR CODE" => "stock_barcode"
    ];

    save_data2DB($data, $map_col_excel2db, 'LDR001');

    echo "Done import LeaderData.<br/>";
}

function process_mmt_data(){

    $file_url = 'https://www.mmt.com.au/datafeed/index.php?lt=s&ft=csv&tk=14I5D2N577ND49O938TC820AB008470145%204BF%2002F%20380%20E4C%20B6F%2055D%20597%2036EF2E15CE36A112A&af%5b%5d=tn&af%5b%5d=ai&af%5b%5d=um&af%5b%5d=sn&af%5b%5d=wt&af%5b%5d=dp&af%5b%5d=si&af%5b%5d=li&af%5b%5d=et&af%5b%5d=st&af%5b%5d=ln';

    $output_file_path = __DIR__ . "/MmtData.csv"; 
    $data = get_download_save_file($file_url, $output_file_path);

    //total = 15 columns
    $map_col_excel2db = [
        "MMT Code" => "stock_code",
        "Manufacturer" => "stock_vendor",
        "Man.Code/SKU" => "stock_vendorstockcode",
        "Description" => "stock_description_short",
        "Extended Description" => "stock_description_long",
        "DotPoints" => "stock_desc_brief",
        "Parent Category Name" => "stock_cat1",
        "Category Name" => "stock_cat2",
        "RRP Inc.::divided_1.1" => "stock_price_rrpex",//divided 1.1
        "Your Buy Ex. GST" => "stock_price_dealerex",
        "Available (Qty)" => "stock_available",
        "ETA" => "stock_eta",
        "Status" => "stock_status",
        "LgeImage" => "stock_image",
        "BAR CODE" => "stock_barcode"
    ];

    save_data2DB($data, $map_col_excel2db, 'MMT001');

    echo "Done import MmtData.<br/>";
}

function process_alloys_data(){

    $file_url = 'https://feeds.alloys.com.au/ecom/e_CSV_Ecom_MW.asp?src=all&signon=auto&token=ZZk7uPSTjbXs7uqXaKwzGl0ZeQNA6SqL1726118870536=';
    $output_file_path = __DIR__ . "/AlloysData.csv"; 
    $data = get_download_save_file($file_url, $output_file_path);

    //total = 17 columns
    $map_col_excel2db = [
        "PartNumber" => "stock_code",
        "Manufacturer" => "stock_vendor",
        "SupplierPartNumber" => "stock_vendorstockcode",
        "Name" => "stock_description_short",
        "Description" => "stock_description_long",
        "FeaturesBenefits" => "stock_desc_brief",
        "CategoryName" => "stock_cat1",
        "Group" => "stock_cat2",
        "PriceRetailEx" => "stock_price_rrpex",
        "PriceCostEx" => "stock_price_dealerex",
        "Quantity" => "stock_available",
        "ETADate" => "stock_eta",
        "ETAStatus" => "stock_status",
        "ProductSpecificURL" => "stock_url",
        "Warranty" => "stock_warranty",
        "image_thumbnail" => "stock_image",
        "EAN" => "stock_barcode"
    ];

    save_data2DB($data, $map_col_excel2db, 'ALL002');

    echo "Done import AlloysData.<br/>";
}

function process_synnex_data(){

    $file_url = '';
    $output_file_path = __DIR__ . "/datafeed_syn.txt"; 

    $data = get_download_save_file($file_url, $output_file_path);

    //total = 13 columns
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

    save_data2DB($data, $map_col_excel2db, 'SYN001');

    echo "Done import SynnexrData.<br/>";
}


// process_dicker_data();
// process_leader_data();
// process_mmt_data();
// process_alloys_data();
process_synnex_data();



