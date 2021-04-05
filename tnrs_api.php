<?php

////////////////////////////////////////////////////////
// Accepts batch web service requests and submits to 
// nsr_batch.php
//
// Note the use of goto for error handling. Simple.
// Concise. Effective. So there :P
////////////////////////////////////////////////////////

///////////////////////////////////
// Parameters
///////////////////////////////////

require_once 'server_params.php';	// parameters in ALL_CAPS set here
require_once 'params.php';			// parameters in ALL_CAPS set here
require_once($utilities_path."status_codes.inc.php");

// Temporary data directory
$data_dir_tmp = $DATADIR;
$data_dir_tmp = "/tmp/tnrs/";

// Input file name & path
// User JSON input saved to this file as pipe-delimited text
// Becomes input for tnrs_batch command (`./controller.pl [...]`)
$basename = "tnrs_" . uniqid(rand(), true);

$filename_tmp = $basename . '_in.tsv';
$file_tmp = $data_dir_tmp . $filename_tmp;

// Results file name & path
// Output of tnrs_batch command will be saved to this file
$results_filename = $basename . "_out.tsv";

# Full path and name of results file
$results_file = $data_dir_tmp . $results_filename;

///////////////////////////////////
// Functions
///////////////////////////////////

////////////////////////////////////////////////////////
// Loads results file as an asociative array
// 
// Options:
//	$filepath: path and name of file to import
//	$delim: field delimiter
////////////////////////////////////////////////////////

function file_to_array_assoc($filepath, $delim) {
	$array = $fields = array(); $i = 0;
	$handle = @fopen($filepath, "r");
	if ($handle) {
		while (($row = fgetcsv($handle, 4096, $delim , '"' , '"')) !== false) {
			// Load keys from header row & continue to next
			if (empty($fields)) {
				$fields = $row;
				continue;
			}
			
			// Load value for this row 
			foreach ($row as $k=>$value) {
				$array[$i][$fields[$k]] = $value;
			}
			$i++;
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);
	}
	
	return $array;
}

////////////////////////////////////////
// Receive & validate the POST request
////////////////////////////////////////

// Start by assuming no errors
// Any run time errors and this will be set to true
$err_code=0;
$err_msg="";
$err=false;

// Make sure request is a pre-flight request or POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	// Send pre-flight response and quit
	//header("Access-Control-Allow-Origin: http://localhost:3000");	// Dev
	header("Access-Control-Allow-Origin: *"); // Production
	header("Access-Control-Allow-Methods: POST, OPTIONS");
	header("Access-Control-Allow-Headers: Content-type");
	header("Access-Control-Max-Age: 86400");
	exit;
} else if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
	$err_msg="ERROR: Request method must be POST\r\n"; 
	$err_code=400; goto err;
}
 
// Make sure that the content type of the POST request has been 
// set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strcasecmp($contentType, 'application/json') != 0) {
	$err_msg="ERROR: Content type must be: application/json\r\n"; 
	$err_code=400; goto err;
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
	$err_msg="ERROR: Received content contained invalid JSON!\r\n";	
	$err_code=400; goto err;
}

///////////////////////////////////
// Inspect the JSON data and run 
// safety/security checks
///////////////////////////////////

// UNDER CONSTRUCTION!

///////////////////////////////////////////
// Extract & validate options
///////////////////////////////////////////

// Get options and data from JSON
if ( ! ( $opt_arr = isset($input_array['opts'])?$input_array['opts']:false ) ) {
	$err_msg="ERROR: No TNRS options (element 'opts' in JSON request)\r\n";	
	$err_code=400; goto err;
}

///////////////////////////////////////////
// Validate options and assign each to its 
// own parameter
///////////////////////////////////////////

include $APP_DIR . "validate_options.php";
if ($err) goto err;

///////////////////////////////////////////
// Check option $mode
// If "meta", ignore other options and begin
// processing metadata request. Otherwise 
// continue processing tnrs_batch request
///////////////////////////////////////////

if ( $mode=="parse" || $mode=="resolve" || $mode=="" ) { 	// BEGIN mode_if
	// tnrs_batch (no indent)
	
	///////////////////////////////////////////
	// Extract & validate data
	///////////////////////////////////////////

	// Get data from JSON
	if ( !( $data_arr = isset($input_array['data'])?$input_array['data']:false ) ) {
		$err_msg="ERROR: No data (element 'data' in JSON request)\r\n";	
		$err_code=400; goto err;
	}

	# Check payload size
	$rows = count($data_arr);
	if ( $rows>$MAX_ROWS && $MAX_ROWS>0 ) {
		$err_msg="ERROR: Requested $rows rows exceeds $MAX_ROWS row limit\r\n";	
		$err_code=413;	# 413 Payload Too Large
		goto err; 
	}

	# Validate data array structure
	# Should have 1 or more rows of exactly 2 elements each
	$rows=0;
	foreach ($data_arr as $row) {
		$rows++;
		$values=0;
		foreach($row as $value) $values++;
		if ($values<>2) {
			$err_msg="ERROR: Data has wrong number of columns in one or more rows, should be exactly 2\r\n"; $err_code=400; goto err;
		}
	}
	if ($rows==0) {
		$err_msg="ERROR: No data rows!\r\n"; $err_code=400; goto err; 
	}

	///////////////////////////////////////////
	// Reset selected options for compatibility 
	// with tnrs_batch command line syntax
	///////////////////////////////////////////

	// Processing mode
	if ( $mode == "parse" ) {
	//if(stripos($mode, "parse") !== false) {
		$opt_mode = "-mode parse";	// Parse-only mode
	} else {
		$opt_mode = ""; 		// Default 'resolve' mode
	}

	// Match mode
	if ( $matches == "all" ) {
	//if(stripos($mode, "parse") !== false) {
		$opt_matches = "-matches all";	// Return all matches
	} else {
		$opt_matches = ""; 				// Returns best match only by default
	}
	# Parse-only over-rides matches
	if ( $mode == "parse" ) {
		$opt_matches = ""; 		
	}

	///////////////////////////////////////////
	// Save data array as pipe-delimited file,
	// to be used as input for TNRS batch app
	///////////////////////////////////////////

	// Make temporary data directory & file in /tmp 
	$cmd="mkdir -p $data_dir_tmp";
	exec($cmd, $output, $status);
	if ($status) {
		$err_msg="ERROR: Unable to create temp data directory\r\n";	
		$err_code=500; goto err;
	}

	// Convert array to pipe-delimited file & save
	// tnrs_batch requires pipe-delimited
	$fp = fopen($file_tmp, "w");
	$i = 0;
	foreach ($data_arr as $row) {
		//if($i === 0) fputcsv($fp, array_keys($row));	// header
		fputcsv($fp, array_values($row), '|');	// data
		$i++;
	}
	fclose($fp);

	// Run dos2unix to fix stupid DOS/Mac/Excel/UTF-16 issues, if any
	$cmd = "dos2unix $file_tmp";
	exec($cmd, $output, $status);
	//if ($status) die("ERROR: tnrs_batch non-zero exit status");
	if ($status) {
		$err_msg="Failed file conversion: dos2unix\r\n";
		$err_code=500; goto err;
	}

	///////////////////////////////////
	// Process the CSV file in batch mode
	///////////////////////////////////

	$data_dir_tmp_full = $data_dir_tmp . "/";
	// Form the final command
	$cmd = $BATCH_DIR . "controller.pl $opt_mode $opt_matches -in '$file_tmp'  -out '$results_file' -sources '$sources' -class $class -nbatch $NBATCH -d t ";

	// Execute the tnrs_batch command
	exec($cmd, $output, $status);
	if ($status) {
		$err_msg="ERROR: tnrs_batch exit status: $status\r\n";
		$err_code=500; goto err;
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
	$results_array = file_to_array_assoc($results_file, "\t");
	
	// Clean up crap inserted by core service
	foreach ( $results_array as $rkey => $row ) {
		
		$str = $row['Name_submitted'];
		// Restore double-escaped single quote to single quote
		$str = str_replace("'\\''", "'", $str);
		// Trim surrounding single quotes added by core service
		$start = substr( $str, 0, 1 );
		$end = substr( $str, strlen($str)-1, 1 );
		if ( $start="'" && $end="'" ) {	// both quotes must be present
			$str = substr($str, 1 );
			$str = substr($str, 0, -1);
		}
		$results_array[$rkey]['Name_submitted']=$str;
		
		$str = $row['Unmatched_terms'];
		// Remove initial single quote
		if ( substr( $str, 0, 1 )=="'" ) {
			$str = substr($str, 1 ); 
		}
		// Remove backslashes
		$str = str_replace("\\", "", $str);	
		$results_array[$rkey]['Unmatched_terms']=$str;		
	}

} else {	// CONTINUE mode_if 
	// Metadaa requests

	if ( $mode=="meta" ) { 
		$api_ver=shell_exec("echo -n $(git describe --abbrev=0)");
		$code_ver=shell_exec("echo -n $(git --git-dir=../tnrs_batch/.git --work-tree=../tnrs_batch describe --abbrev=0)");

		$sql="
		SELECT db_version, build_date, 
		'$code_ver' AS code_version, 
		'$api_ver' AS api_version
		FROM meta
		;
		";
	} elseif ( $mode=="sources" ) { // CONTINUE mode_if 
		$sql="
		SELECT sourceID, sourceName, sourceNameFull, sourceUrl,
		description, dataUrl, logo_path,
		sourceVersion as version, sourceReleaseDate, 
		dateAccessed AS tnrsDateAccessed
		FROM source
		;
		";
	} elseif ( $mode=="classifications" ) { // CONTINUE mode_if 
		$sql="
		SELECT sourceID, sourceName
		FROM source
		WHERE isHigherClassification=1
		;
		";
	} elseif ( $mode=="citations" ) { // CONTINUE mode_if 
		$sql="
		SELECT 'tnrs_pub' AS source, publication as citation
		FROM meta
		UNION ALL
		SELECT 'tnrs' AS source, citation
		FROM meta
		UNION ALL
		SELECT sourceName AS source, citation
		FROM source
		WHERE citation IS NOT NULL AND TRIM(citation)<>''
		;
		";
	} elseif ( $mode=="collaborators" ) { // CONTINUE mode_if 
		$sql="
		SELECT collaboratorName, collaboratorNameFull, collaboratorUrl, 
		description, logo_path
		FROM collaborator
		;
		";
	} else {
		$err_msg="ERROR: Unknown opt mode '$mode'\r\n"; 
		$err_code=400; goto err;
	}
	
	// Run the query and save results as $results_array
	include("qy_db.php"); 
	
}	// END mode_if

$results_json = json_encode($results_array);

///////////////////////////////////
// Send the response
///////////////////////////////////

// Send the header
header("Access-Control-Allow-Origin: *");
header('Content-type: application/json');

// Send data
echo $results_json;

///////////////////////////////////
// Error: return http status code
// and error message
///////////////////////////////////

err:
http_response_code($err_code);
echo $err_msg;

?>