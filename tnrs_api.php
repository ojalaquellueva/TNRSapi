<?php

////////////////////////////////////////////////////////
// Accepts batch web service requests and submits to 
// nsr_batch.php
////////////////////////////////////////////////////////

///////////////////////////////////
// Parameters
///////////////////////////////////

require_once 'params.php';
//$msg = "Processing batch api request\r\n\r\n";
//file_put_contents($LOGFILE, $msg)

// Temporary data directory
//$data_dir_tmp = "/tmp/tnrs";
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

// Make sure request is a POST
if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
    throw new Exception('Request method must be POST!');
}
 
// Make sure that the content type of the POST request has been 
// set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strcasecmp($contentType, 'application/json') != 0) {
    throw new Exception('Content type must be: application/json');
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
    throw new Exception('Received content contained invalid JSON!');
}

// Separate options and data
$opt_arr = $input_array['opts'];
$data_arr = $input_array['data'];

///////////////////////////////////////////
// Validate TNRS options
///////////////////////////////////////////

include $APP_DIR . "validate_options.php";

///////////////////////////////////////////
// Save data array as pipe-delimited file,
// to be used as input for TNRS batch app
///////////////////////////////////////////

// Make temporary data directory & file in /tmp 
$cmd="mkdir -p $data_dir_tmp";
exec($cmd, $output, $status);
if ($status) die("ERROR: Unable to create temp data directory");

// Convert array to pipe-delimited file & save
// TNRSbatch requires pipe-delimited
$fp = fopen($file_tmp, "w");
$i = 0;
foreach ($data_arr as $row) {
    //if($i === 0) fputcsv($fp, array_keys($row));	// header
    //fputcsv($fp, array_values($row));				// data
    fputcsv($fp, array_values($row), '|');				// data
    $i++;
}
// foreach ($data_arr as $row) {
// 	$curr_row = array_values($row);
// 	$result = implode("|", array_map(function ($v) {
// 		return $v[0] . "=" .$v[1];
// 	}, $curr_row));
// 	fputcsv($fp, $result);
//     $i++;
// }
fclose($fp);

// Run dos2unix to fix stupid DOS/Mac/Excel/UTF-16 issues, if any
$cmd = "dos2unix $file_tmp";
exec($cmd, $output, $status);
//if ($status) die("ERROR: tnrs_batch non-zero exit status");
if ($status) die("Failed file conversion: dos2unix\r\n");

///////////////////////////////////
// Process the CSV file in batch mode
///////////////////////////////////

$data_dir_tmp_full = $data_dir_tmp . "/";
// Testing with hard-coded options for now
$cmd = $BATCH_DIR . "controller.pl -in '$file_tmp'  -out '$results_file' -sources '$sources' -class $class -nbatch 10 -d t ";
exec($cmd, $output, $status);
if ($status) die("ERROR: tnrs_batch exit status: $status");
// if ($status) die("
// \$status=$status
// \$file_tmp='$file_tmp'
// \$results_file='$results_file'
// \$cmd=\"$cmd\"
// ");
///////////////////////////////////
// Retrieve the tab-delimited results
// file and convert to JSON
///////////////////////////////////

/*
header('Content-type: application/json');echo "\r\n\$data_dir_tmp: " . $data_dir_tmp . "\r\n\r\n";
die();
*/

// Import the results file (tab-delimitted)
$results_array = load_tabbed_file($results_file, true);
$results_json = json_encode($results_array);

///////////////////////////////////
// Echo the results
///////////////////////////////////

header('Content-type: application/json');
echo $results_json;

?>