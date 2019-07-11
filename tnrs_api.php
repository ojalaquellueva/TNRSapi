<?php

////////////////////////////////////////////////////////
// Accepts batch web service requests and submits to 
// nsr_batch.php
////////////////////////////////////////////////////////

///////////////////////////////////
// Parameters
///////////////////////////////////

require_once 'params.php';

// Temporary data directory
$data_dir_tmp = $DATADIR;

// Input file name & path
// User JSON input saved to this file as pipe-delimited text
// Becomes input for tnrs_batch command (`./controller.pl [...]`)
$basename = uniqid(rand(), true);

$filename_tmp = $basename . '_in.txt';
$file_tmp = $data_dir_tmp . $filename_tmp;

// Results file name & path
// Output of tnrs_batch command will be saved to this file
$results_filename = $basename . "_out.txt";

# Full path and name of results file
$results_file = $data_dir_tmp . $results_filename;

///////////////////////////////////
// Functions
///////////////////////////////////

// Loads a tab separated text file as array
// Use option $load_keys=true only if file has header
function load_tabbed_file($filepath, $load_keys=false) {
    $array = array();
 
    if (!file_exists($filepath)){ return $array; }
    $content = file($filepath);
 
    for ($x=0; $x < count($content); $x++){
        if (trim($content[$x]) != ''){
            $line = explode("\t", trim($content[$x]));
            if ($load_keys){
                $key = array_shift($line);
                $array[$key] = $line;
            }
            else { $array[] = $line; }
        }
    }
    return $array;
}

////////////////////////////////////////
// Receive & validate the POST request
////////////////////////////////////////

require_once("html_status_codes.php");

// Start by assuming no errors
// Any run time errors and this will be set to true
$errs=false;
$err_code=0;

// Make sure request is a POST
if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
	$err_code=400; $err_msg="ERROR: Request method must be POST\r\n";	$errs=true;
}
 
// Make sure that the content type of the POST request has been 
// set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strcasecmp($contentType, 'application/json') != 0) {
	$err_code=400; $err_msg="ERROR: Content type must be: application/json\r\n";	$errs=true;
}
 
// Receive the RAW post data.
$input_json = trim(file_get_contents("php://input"));

///////////////////////////////////////////
// Convert post data to array and separate
// data from options
///////////////////////////////////////////

// Attempt to decode the incoming RAW post data from JSON.
$input_array = json_decode($input_json, true);

// If json_decode failed, the JSON is invalid.
if (!is_array($input_array)) {
	$err_code=400; $err_msg="ERROR: Received content contained invalid JSON!\r\n";	$errs=true;
}

// Separate options and data
$opt_arr = $input_array['opts'];
$data_arr = $input_array['data'];

///////////////////////////////////////////
// Validate input data < $MAX_ROWS rows
///////////////////////////////////////////
$rows = count($data_arr);

if ( $rows>$MAX_ROWS && $MAX_ROWS>0 ) {
	$errs=true; $err_code=400;
	$err_msg="ERROR: Requested $rows rows exceeds $MAX_ROWS row limit\r\n";	
}


///////////////////////////////////////////
// Validate TNRS options
///////////////////////////////////////////

include $APP_DIR . "validate_options.php";

///////////////////////////////////////////
// Reset selected options for compatibility 
// with TNRSbatch command line syntax
///////////////////////////////////////////

// Processing mode
if ( $mode == "parse" ) {
	$mode2 = "-mode parse";	// Parse-only mode
} else {
	$mode2 = ""; 		// Default 'resolve' mode
}

///////////////////////////////////////////
// Save data array as pipe-delimited file,
// to be used as input for TNRS batch app
///////////////////////////////////////////

// Make temporary data directory & file in /tmp 
$cmd="mkdir -p $data_dir_tmp";
exec($cmd, $output, $status);
if ($status) {
	$err_code=500; $err_msg="ERROR: Unable to create temp data directory\r\n";	$errs=true;
}

// Convert array to pipe-delimited file & save
// TNRSbatch requires pipe-delimited
$fp = fopen($file_tmp, "w");
$i = 0;
foreach ($data_arr as $row) {
    fputcsv($fp, array_values($row), '|');				// data
    $i++;
}
fclose($fp);

// Run dos2unix to fix stupid DOS/Mac/Excel/UTF-16 issues, if any
$cmd = "dos2unix $file_tmp";
exec($cmd, $output, $status);
//if ($status) die("ERROR: tnrs_batch non-zero exit status");
if ($status) {
	$err_code=500; $err_msg="Failed file conversion: dos2unix\r\n";	$errs=true;
}

///////////////////////////////////
// Process the CSV file in batch mode
///////////////////////////////////

$data_dir_tmp_full = $data_dir_tmp . "/";
// Testing with hard-coded options for now
$cmd = $BATCH_DIR . "controller.pl -in '$file_tmp'  -out '$results_file' -sources '$sources' -class $class -nbatch 10 -d t $mode2 ";
exec($cmd, $output, $status);
if ($status) {
	$err_code=500; $err_msg="ERROR: tnrs_batch exit status: $status\r\n";	$errs=true;
}
//if ($status) die("
// \$status=$status
// \$file_tmp='$file_tmp'
// \$results_file='$results_file'
// \$cmd=\"$cmd\"
// ");
///////////////////////////////////
// Retrieve the tab-delimited results
// file and convert to JSON
///////////////////////////////////

// Import the results file (tab-delimitted) to array
$results_array = load_tabbed_file($results_file, true);

// Convert to simple indexed array
$results_array = array_values($results_array); 	

// Fix header of parse-only results
if ($mode=="parse") {
	$results_array[0]=array(
	'Name_submitted',
	'Family',
	'Genus',
	'Specific_epithet',
	'Infraspecific_rank',
	'Infraspecific_epithet',
	'Infraspecific_rank_2',
	'Infraspecific_epithet_2',
	'Author',
	'Annotations',
	'Unmatched_terms'
	);
}

$results_json = json_encode($results_array);

///////////////////////////////////
// Echo the results
///////////////////////////////////

if ($errs) {
	http_response_code($err_code);
	echo $err_msg;
} else {
	header('Content-type: application/json');
	echo $results_json;
}

?>