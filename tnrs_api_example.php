<?php

//////////////////////////////////////////////////////
// Submits test data to TNRS API & displays results
//
// * Imports names from test file on local file system
// * Displays input and output at various stages of
// 	 the process.
//////////////////////////////////////////////////////

// Load library of http status codes
require_once("includes/php/status_codes.php");

/////////////////////
// API parameters
/////////////////////

require_once 'server_params.php';	// parameters in ALL_CAPS set here
require_once 'params.php';			// parameters in ALL_CAPS set here

// Path and name of file containing input names and political divisions
$inputfilename = "tnrs_testfile.csv";
$inputfile = $DATADIR.$inputfilename;
$inputfile = "http://bien.nceas.ucsb.edu/bien/wp-content/uploads/2019/07/tnrs_testfile.csv";

// Desired response format
//	Options: json*|xml
// Example: $format="xml";
// NOT YET IMPLEMENTED!
$format="json";

// Number of lines to import
// Use this option to limit test data to small subsample of input file
// Set to number > # of lines in file to import entire file
$lines = 10000000000;
$lines = 4;

// api base url 
$base_url = "https://tnrsapi.xyz/tnrs_api.php";	# Production
$base_url = "http://vegbiendev.nceas.ucsb.edu:8975/tnrs_api.php"; # Dev (port)

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

// Processing mode
//	Options: resolve*|parse|meta
// 	E.g., $mode="parse"
$mode="resolve";			// Resolve names
//$mode="";					// Same as $mode="resolve";
//$mode="parse";			// Parse names
//$mode="meta";				// Return metadata on TNRS & sources
// $mode="sources";			// List TNRS sources
// $mode="citations";		// Return citations for TNRS & sources
//$mode="classifications";	// Return citations for TNRS & sources

// Taxonomic sources
// One or more of the following, separated by commas, no spaces:
//	tpl,gcc,ildis,gcc,tropicos,usda,ncbi
//	Current default: "tpl,tropicos,usda"
$sources="tropicos,tpl,usda";		

// Classification
// 	Options: tropicos only
// 	E.g., $class="tropicos"
$class="tropicos";

// Matches to return
// 	Options: best*|all
// 	Over-ride command line option -m
$matches="best";
//$matches="all";

// Match accuracy (NOT IMPLEMENTED)
// Must be decimal from 0.05 (default) to 1
//	E.g., $accuracy=0.50;
//	Do not enclose in quotes, except empty string for default
$acc=0.05;

// Constrain by higher taxonomy? (NOT YET IMPLEMENTED)
//	Options: true|false*
//	Boolean: do not enclose in quotes (except can use empty string for default)
$constr_ht=false;

// Constraint by taxonomic source? (NOT YET IMPLEMENTED)
//	Options: true|false*
//	Boolean: do not enclose in quotes (except can use empty string for default)
$constr_ts=false;

/////////////////////////////////////////
// Display options
// 
// * Turn on/off what is echoed to terminal
// * Raw data always displayed
/////////////////////////////////////////

$disp_data_array=false;		// Echo raw data as array
$disp_combined_array=false;	// Echo combined options+data array
$disp_opts_array=false;		// Echo TNRS options as array
$disp_opts=true;			// Echo TNRS options
$disp_json_data=true;		// Echo the options + raw data JSON POST data
$disp_results_json=true;	// Echo results as array
$disp_results_array=false;	// Echo results as array
$disp_results_csv=true;		// Echo results as CSV text
$time=true;					// Echo time elapsed

/////////////////////////////////////////////////////
// Command line options
// Use to over-ride the above parameters
/////////////////////////////////////////////////////

// Get options, set defaults for optional parameters
// Use default if unset
$options = getopt("b:m:");
$batches=isset($options["b"])?$options["b"]:"$NBATCH";	
// $matches=isset($options["m"])?$options["m"]:"$TNRS_DEF_MATCHES";

////////////////////////////////////////////////////////////////
// Main
////////////////////////////////////////////////////////////////

include $timer_on; 	// Start the timer
echo "\n";

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
	"matches"=>$matches,
	"batches"=>$batches
	);

///////////////////////////////
// Make data array
///////////////////////////////

// Import csv data and convert to array
$data_arr = array_map('str_getcsv', file($inputfile));

# Get subset
$data_arr = array_slice($data_arr, 0, $lines);

if ( $mode=="parse" || $mode=="resolve" || $mode=="" ) {
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
}

///////////////////////////////
// Merge options and data into 
// json object for post
///////////////////////////////

// Convert to JSON
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
	$mode_disp = isset($opts['mode']) ? $mode : "resolve";
	
	// Echo the options
	echo "TNRS options:\r\n";
	echo "  sources: " . $opts['sources'] . "\r\n";
	echo "  class: " . $opts['class'] . "\r\n";
	echo "  mode: " . $mode_disp . "\r\n";
	echo "  acc: " . $opts['acc'] . "\r\n";
	echo "  constr_ht: " . $constr_ht_disp . "\r\n";
	echo "  constr_ts: " . $constr_ts_disp . "\r\n";
	echo "  matches: " . $opts['matches'] . "\r\n";
	echo "  batches: " . $opts['batches'] . "\r\n";
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

// Send the API call
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
$results = json_decode($results_json, true);	// Convert JSON results to array

// Echo the JSON response
if ($disp_results_json) {
	echo "API results (JSON)\r\n";
	echo $results_json;
	echo "\r\n\r\n";
}

if ($disp_results_array) {
	echo "API results as array:\r\n";
	var_dump($results);
	echo "\r\n\r\n";
}

if ($disp_results_csv) {
	echo "API results as CSV:\r\n";
	
// 	if ( ! ($mode=="resolve" || $mode=="" || $mode=="parse") ) {
// 		$results = $results[0];	// Remove one level of nesting
// 	}
// 
	foreach ( $results as $rkey => $row ) {
		$rind=array_search( $rkey, array_keys($results) );	# Index: current row
		$cindmax = count( $row )-1;	// Index: last column of current row
	
		if ( $rind==0 ) {
			// Print header
			foreach ( $row as $key => $value )  {	
				$cind=array_search( $key, array_keys($row) );
				$cind==$cindmax?$format="%1s\n":$format="%1s,";
				printf($format, $key);
			}
		}

		// Print data 
		foreach ( $row as $key => $value )  {	
			$cind=array_search( $key, array_keys($row) );
			$cind==$cindmax?$format="%1s\n":$format="%1s,";
			printf($format, $value);
		}
	}
}

///////////////////////////////////
// Echo time elapsed
///////////////////////////////////

include $timer_off;	// Stop the timer
if ($time) echo "\r\nTime elapsed: " . $tsecs . " seconds.\r\n\r\n"; 

?>
