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

require 'params.php';
require_once($utilities_path."status_codes.php");

// Temporary data directory
$data_dir_tmp = $DATADIR;
$data_dir_tmp = "/tmp/tnrs";

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

////////////////////////////////////////////////////////
// Loads results file as an array, with option to keep 
// best match only
// 
// Option $best_match=true treats the value in the first 
// column as an array key, and keeps only the last of a 
// series of lines bearing the same key. Assuming the 
// file has already been sorted with best match for each 
// name last, $best_match=true keeps best match only. 
// In other words, results file MUST be already sorted!
// 
// Options:
//	$filepath: path and name of file to import
//	$delim: file field delimiter
//	$best_match: 
//		true="keep best match only" (default)
//		false="keep all matches"
////////////////////////////////////////////////////////
function results_to_array($filepath, $delim, $best_match=true) {
    $array = array();
 
    if (!file_exists($filepath)){ return $array; }
    $content = file($filepath);
 
    for ($x=0; $x < count($content); $x++){
        if (trim($content[$x]) != ''){
            $line = explode($delim, trim($content[$x]));
            if ($best_match) {
                // This has the effect of keeping only the last in a 
                // series of rows bearing the same ID, where ID is in
                // the first column
                $key = $line[0];
                //$key = array_shift($line); // Omits first column
                 $array[$key] = $line; 
            } else { 
            	$array[] = $line;
            }
        }
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

// Make sure request is a POST
if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
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
// continue processing TNRSbatch request
///////////////////////////////////////////

if ( $mode=="parse" || $mode=="resolve" || $mode=="" ) { 	// BEGIN mode_if
// TNRSbatch (no indent)
	
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

# Valid data array structure
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
// with TNRSbatch command line syntax
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
	$err_msg="Failed file conversion: dos2unix\r\n";
	$err_code=500; goto err;
}

///////////////////////////////////
// Process the CSV file in batch mode
///////////////////////////////////

$data_dir_tmp_full = $data_dir_tmp . "/";
// Testing with hard-coded options for now
$cmd = $BATCH_DIR . "controller.pl $opt_mode $opt_matches -in '$file_tmp'  -out '$results_file' -sources '$sources' -class $class -nbatch $NBATCH -d t ";
// For testing without $opt_mode
//$cmd = $BATCH_DIR . "controller.pl -in '$file_tmp'  -out '$results_file' -sources '$sources' -class $class -nbatch $NBATCH -d t  ";
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

// TESTING
// Count line in raw output file
$lines_raw = count(file($results_file)) - 1;

// Import the results file (tab-delimitted) to array
// Set third parameter to true to keep best match only for each name,
// or false to keep all matches
//$results_array = load_tabbed_file($results_file, false);
$best_only=$matches=="best"?true:false;
$results_array = results_to_array($results_file, "\t", $best_only);

// Convert to simple indexed array
$results_array = array_values($results_array); 	

// Post-processing
// Ultimately, this should be done by core services, 
// but handling in API for now
if ($mode=="parse") {
	// Fix header of parse-only results
	$results_array[0]=array(
	'ID',
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

////////////////////////////////////////////////////
// Remove superfluous single quotes and escapes 
////////////////////////////////////////////////////

// Set column number for "Unmatched_terms"
if ($mode=="parse") {
	$umt_col = 11;
} elseif ($mode=="resolve" || $mode=="" ) {
	$umt_col = 28;
}

$n = 0;
foreach ($results_array as $row) {	
	///////////////////////////////////
	// Name_sumbitted (column 1)
	///////////////////////////////////
	
	// First single quote
	$old = $results_array[$n][1];
	$ptn = "/^\"'/";
	$repl = "\"";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][1] = $new;
	
	// Last single quote
	$old = $results_array[$n][1];
	$ptn = "/'\"$/";
	$repl = "\"";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][1] = $new;

	// Escapes of embedded single quotes, if any
	$old = $results_array[$n][1];
	$ptn = "/'\\\'/";
	$repl = "";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][1] = $new;
	
	///////////////////////////////////
	// Unmatched_terms
	//   resolve mode: column 27
	//   parse mode: column 10
	///////////////////////////////////
	
	// Initial single quote preceded by double quote
	$old = $results_array[$n][$umt_col];
	$ptn = "/^\"'/";
	$repl = "\"";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][$umt_col] = $new;

	// Escape characters
	$old = $results_array[$n][$umt_col];
	$ptn = "/\\\/";
	$repl = "";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][$umt_col] = $new;
		
	// Extra leading whitespace, preceded by double quote
	// Multiple time to catch multiple whitespace
	$results_array[$n][$umt_col] = preg_replace("/^\"\s+/", "\"", $results_array[$n][$umt_col]);
	
	// Initial single quote not preceded by double quote
	$old = $results_array[$n][$umt_col];
	$ptn = "/^'/";
	$repl = "";
	$new = 	preg_replace($ptn, $repl, $old);
	$results_array[$n][$umt_col] = $new;
	
	$n++;
}

} elseif ( $mode=="meta" ) { // CONTINUE mode_if 
	$sql="
	SELECT db_version, build_date, code_version
	FROM meta
	;
	";
	include("qy_db.php");
} elseif ( $mode=="sources" ) { // CONTINUE mode_if 
	$sql="
	SELECT sourceID, sourceName, sourceNameFull, sourceUrl,
	sourceVersion as version, sourceReleaseDate, 
	dateAccessed AS tnrsDateAccessed
	FROM source
	;
	";
	include("qy_db.php");
} elseif ( $mode=="citations" ) { // CONTINUE mode_if 
	$sql="
	SELECT 'tnrs' AS source, citation
	FROM meta
	UNION ALL
	SELECT sourceName AS source, citation
	FROM source
	WHERE citation IS NOT NULL AND TRIM(citation)<>''
	;
	";
	include("qy_db.php");
}	// END mode_if

$results_json = json_encode($results_array);

///////////////////////////////////
// Echo the results
///////////////////////////////////

header('Content-type: application/json');
echo $results_json;
//echo "sources='$sources_bak', class='$class_bak', mode='$mode_bak'";
//echo "n=$n";
//echo "\r\nlines_raw=$lines_raw, n=$n";

///////////////////////////////////
// Error: return http status code
// and error message
///////////////////////////////////

err:
http_response_code($err_code);
echo $err_msg;

?>