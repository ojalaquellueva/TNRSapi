<?php

//////////////////////////////////////////////////////
// Assembles test batch request and submits to NSR API
// Imports names from file on local file sysytem
//////////////////////////////////////////////////////

/////////////////////////////////////////
// TNRS options
//
// UNDER CONSTRUCTION! - NOT ALL USED YET
// 
// Set any option to empty string ("") to 
// use default
// *=default option
/////////////////////////////////////////

// Taxonomic sources
// One or more of the following, separated by commas, no spaces:
//	tpl,gcc,ildis,gcc,tropicos,usda,ncbi
//	Default: tpl,gcc,ildis,gcc,tropicos,usda
$sources="tropicos,tpl,ildis,gcc,usda";
$sources="usda";

// Classification
// 	Options: tropicos*|ncbi
// 	E.g., $class="tropicos"
$class="tropicos";

// Processing mode
//	Options: "resolve" (default), "parse"
// 	E.g., $mode="parse"
$mode="parse";

// Match accuracy
// Must be decimal from 0.05 (default) to 1
//	E.g., $accuracy=0.50;
//	Do not enclose in quotes, except empty string for default
$acc=0.05;

// Constrain by higher taxonomy?
//	Options: "true"|"false"*
$constr_ht=false;

// Constraint by taxonomic source?
//	Options: "true"|"false"*
$constr_ts=false;

// Matches to return
//	Options: best*|all
//	E.g., $matches="best";
$matches="all";

/////////////////////
// Other parameters
/////////////////////

require_once 'params.php';	// parameters in ALL_CAPS set here

// Path and name of file containing input names and political divisions
$inputfilename = "testfile.csv";

// Desired response format
//	Options: json*|xml
// Example: $format="xml";
// NOT USED YET
$format="json";

// Number of lines to import
// Use to subsample the input file
// Set to large number to impart entire file
$lines = 5;

// api base url 
//$base_url = "https://paramo.cyverse.org/tnrs/api/tnrs_api.php";
$base_url = "https://tnrsapidev.xyz/tnrs_api.php";

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

echo "The options as array:\r\n";
var_dump($opts_arr);
echo "\r\n";

//die("Exiting at 'Make options array'\r\n");

///////////////////////////////
// Make data array
///////////////////////////////

// Import csv data and convert to array
$data_arr = array_map('str_getcsv', file($DATADIR.$inputfilename));

# Get subset
$data_arr = array_slice($data_arr, 0, $lines);

// Echo raw data
echo "The raw data as text:\r\n";
foreach($data_arr as $row) {
	foreach($row as $key => $value) echo "$value\t"; echo "\r\n";
}
echo "\r\n";

echo "The raw data as array:\r\n";
var_dump($data_arr);
echo "\r\n";

//die("Exiting at 'Import csv data and convert to array'\r\n");

///////////////////////////////
// Merge options and data into 
// json object for post
///////////////////////////////

# Convert to JSON
$json_data = json_encode(array('opts' => $opts_arr, 'data' => $data_arr));	

// Echo the JSON
echo "JSON input for the API:\r\n";
echo $json_data . "\r\n\r\n";

///////////////////////////////
// Decompose the JSON
// into opt and data
///////////////////////////////

$input_array = json_decode($json_data, true);
echo "The combined array:\r\n";
var_dump($input_array);
echo "\r\n";

echo "The options:\r\n";
$opts = $input_array['opts'];
var_dump($opts);
echo "\r\n";

echo "Some individual options:\r\n";
echo "  sources: " . $opts['sources'] . "\r\n";
echo "  acc: " . $opts['acc'] . "\r\n";
echo "  constr_ht: " . $opts['constr_ht'] . "\r\n";
echo "\r\n";


echo "The data:\r\n";
var_dump($input_array['data']);
echo "\r\n";

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
    die("Error: call to URL $url failed with status $status, response $response, curl_error " . curl_error($ch) . ", curl_errno " . curl_errno($ch) . "\r\n");
}

// Close curl
curl_close($ch);

///////////////////////////////
// Echo the results
///////////////////////////////

//$result_json = var_dump($response);
$results_json = $response;
$results = json_decode($results_json, true);

// Echo the response content
echo "JSON response:\r\n";
//var_dump($response);
echo $results_json;
echo "\r\n\r\n";

echo "Response as CSV (selected fields only):\r\n";
foreach($results as $result) {
	$flds1 =array_slice($result, 1, 5);
	$flds2 =array_slice($result, 7, 1);
	$flds3 =array_slice($result, 14, 4);
	$flds4 =array_slice($result, 20, 1);
	$line = implode(",", $flds1) . "," . implode(",", $flds2) . "," . implode(",", $flds3) . "," . implode(",", $flds4);
	echo $line;
    echo "\r\n";
}
echo "\r\n";

?>