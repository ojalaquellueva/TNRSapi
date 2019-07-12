<?php

//////////////////////////////////////////////////////
// Submits test data to TNRS API & displays results
//
// * Imports names from test file on local file system
// * Displays input and output at various stages of
// 	 the process.
//////////////////////////////////////////////////////

require_once("includes/php/status_codes.php");

/////////////////////
// API parameters
/////////////////////

require_once 'params.php';	// parameters in ALL_CAPS set here

// Path and name of file containing input names and political divisions
$inputfilename = "testfile.csv";

// Desired response format
//	Options: json*|xml
// Example: $format="xml";
// NOT YET IMPLEMENTED!
$format="json";

// Number of lines to import
// Use this option to limit test data to small subsample of input file
// Set to number > # of lines in file to import entire file
$lines = 10;

// api base url 
$base_url = "https://tnrsapidev.xyz/tnrs_api.php";

/////////////////////////////////////////
// TNRS options
//
// UNDER CONSTRUCTION! - NOT ALL OPTIONS
// CURRENTLY IMPLEMENTED
// 
// Set any option to empty string ("") to 
// use default
// *=default option
/////////////////////////////////////////

// Taxonomic sources
// One or more of the following, separated by commas, no spaces:
//	tpl,gcc,ildis,gcc,tropicos,usda,ncbi
//	Current default: "tpl,gcc,ildis,gcc,tropicos,usda"
$sources="tpl,ildis,gcc,tropicos,usda";		

// Classification
// 	Options: tropicos*|ncbi
// 	E.g., $class="tropicos"
$class="tropicos";

// Processing mode
//	Options: resolve*|parse
// 	E.g., $mode="parse"
$mode="parse";
//$mode="";		// Same as $mode="resolve";

// Match accuracy (NOT IMPLEMENTED)
// Must be decimal from 0.05 (default) to 1
//	E.g., $accuracy=0.50;
//	Do not enclose in quotes, except empty string for default
$acc=0.05;

// Constrain by higher taxonomy? (NOT IMPLEMENTED)
//	Options: true|false*
//	Boolean: do not enclose in quotes (except can use empty string for default)
$constr_ht=false;

// Constraint by taxonomic source? (NOT IMPLEMENTED)
//	Options: true|false*
//	Boolean: do not enclose in quotes (except can use empty string for default)
$constr_ts=false;

// Matches to return (NOT IMPLEMENTED)
//	Options: best|all*
//	E.g., $matches="best";
// Note: "best" (=return best match only) does not appear to be available
//		from current TNRSbatch. Working on it.
$matches="all";

/////////////////////////////////////////
// Display options
// 
// * Turn on/off what is echoed to terminal
// * Raw data and JSON results are always echoed
/////////////////////////////////////////

$disp_data_array=false;		// Echo raw data as array
$disp_combined_array=false;	// Echo combined options+data array
$disp_opts_array=false;		// Echo TNRS options as array
$disp_opts=true;			// Echo TNRS options
$disp_json_data=true;		// Echo the options + raw data JSON POST data
$disp_results_array=false;	// Echo results as array
$disp_results_csv=true;		// Echo results as CSV text, for pasting to Excel

////////////////////////////////////////////////////////////////
// Main
////////////////////////////////////////////////////////////////

///////////////////////////////
// Make options array
///////////////////////////////

$opts_arr = array(
"sources"=>$sources, 
"class"=>$class, 
"mode"=>$mode,
"acc"=>$acc, 
"constr_ht"=>$constr_ht, 
"constr_ts"=>$constr_ts,
"matches"=>$matches
);

///////////////////////////////
// Make data array
///////////////////////////////

// Import csv data and convert to array
$data_arr = array_map('str_getcsv', file($DATADIR.$inputfilename));

# Get subset
$data_arr = array_slice($data_arr, 0, $lines);

// Echo raw data
echo "The raw data:\r\n";
foreach($data_arr as $row) {
	foreach($row as $key => $value) echo "$value\t"; echo "\r\n";
}
echo "\r\n";

if ($disp_data_array) {
	echo "The raw data as array:\r\n";
	var_dump($data_arr);
	echo "\r\n";
}

///////////////////////////////
// Merge options and data into 
// json object for post
///////////////////////////////

# Convert to JSON
$json_data = json_encode(array('opts' => $opts_arr, 'data' => $data_arr));	

///////////////////////////////
// Decompose the JSON
// into opt and data
///////////////////////////////

$input_array = json_decode($json_data, true);

if ($disp_combined_array) {
	echo "The combined array:\r\n";
	var_dump($input_array);
	echo "\r\n";
}

$opts = $input_array['opts'];
if ($disp_opts_array) {
	echo "Options array:\r\n";
	var_dump($opts);
	echo "\r\n";
}

if ($disp_opts) {
	// Convert booleans to text for display
	$constr_ht_disp = isset($opts['constr_ht']) ?
		($opts['constr_ht']==true?"true":"false") : "false";
	$constr_ts_disp = isset($opts['constr_ts']) ?
		($opts['constr_ts']==true?"true":"false") : "false";
	$mode_disp = isset($opts['mode']) ?
		($opts['mode']=="parse"?"parse":"resolve") : "resolve";
	
	// Echo the options
	echo "TNRS options:\r\n";
	echo "  sources: " . $opts['sources'] . "\r\n";
	echo "  class: " . $opts['class'] . "\r\n";
	echo "  mode: " . $mode_disp . "\r\n";
	echo "  acc: " . $opts['acc'] . "\r\n";
	echo "  constr_ht: " . $constr_ht_disp . "\r\n";
	echo "  constr_ts: " . $constr_ts_disp . "\r\n";
	echo "  matches: " . $opts['matches'] . "\r\n";
	echo "\r\n";
}

if ($disp_json_data) {
	// Echo the final JSON post data
	echo "API input (options + raw data converted to JSON):\r\n";
	echo $json_data . "\r\n\r\n";
}


///////////////////////////////
// Call the API
///////////////////////////////

$url = $base_url;    

// Initialize curl & set options
$ch = curl_init($url);	
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// Return response (CRITICAL!)
curl_setopt($ch, CURLOPT_POST, 1);	// POST request
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);	// Attach the encoded JSON
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 

// Execute the curl API call
$response = curl_exec($ch);

// Check status of the response and echo if error
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ( $status != 201 && $status != 200 ) {
	$status_msg = $http_status_codes[$status];
    die("Error: call to URL $url failed with status $status $status_msg \r\nDetails: $response \r\n");
}

// Close curl
curl_close($ch);

///////////////////////////////
// Echo the results
///////////////////////////////

$results_json = $response;
$results = json_decode($results_json, true);	// Comnvert JSON results to array

// Echo the JSON response
echo "API results (JSON)\r\n";
echo $results_json;
echo "\r\n\r\n";

if ($disp_results_array) {
	echo "API results as array:\r\n";
	var_dump($results);
	echo "\r\n\r\n";
}

if ($disp_results_csv) {
	echo "API results as CSV:\r\n";
	foreach($results as $result) {
		echo implode(",", array_slice($result, 0)) . "\r\n";
	}
}

echo "\r\n";

?>