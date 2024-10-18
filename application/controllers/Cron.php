<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL);

class Cron extends CI_Controller {

	public function index() {
		echo "Hello Cron !" . PHP_EOL;
		exit;
	}

	public function run_external_script(){


		$script_path = APPPATH . 'external/import_data.php';
        
		// Check if the file exists
		if (file_exists($script_path)) {
				// Require the external script
				include($script_path);

				// If your external script has a function or output, you can now access it
				//$result = some_external_function(); // Assuming the script has a function

				// Output the result or handle as needed
				//echo $result;
		} else {
				echo "External script not found!";
		}

		die;
	}

}
