<?php

/////////////////////////////////////////////////
// Check these parameters after every database 
// update to confirm they apply. Change as needed
// Ideally, database version-species parameters
// should queries from the database so that 
// manual parameter adjustments not needed.
/////////////////////////////////////////////////

// The following are needed to retrieve $DB (db name)
include_once 'server_params.php';
include_once $CONFIG_DIR.'db_config.php';

/////////////////////////////////////////////////
// API parameters
/////////////////////////////////////////////////

// Return offending SQL on error? (true|false)
// TURN OFF FOR PRODUCTION! ($err_show_sql=false)
$err_show_sql=false;

// Maximum permitted input rows per request
// For no limit, set to 0
$MAX_ROWS=5001;	
					
// Number of batches
$NBATCH=10000;	
$NBATCH=25;				

//////////////////////////////////////////////////
// All TNRS options
// Use to test if submitted options allowed
//////////////////////////////////////////////////

# Options "resolve" & "parse" go to tnrs_batch, but other options
# query database directly
$TNRS_MODES = array(
"resolve",
"parse",
"meta",
"sources",
"citations",
"classifications",
"collaborators",
"logos",
"dd"
);

// Database-version specific parameters for 
// available and default sources & classifications
// THIS SHOULD BE DONE DYNAMICALLY BY QUERYING THE DATABASE FOR AVAILABLE SOURCES!
if ( $DB=="tnrs_4_2" ) {
	$TNRS_SOURCES = array("tropicos","wfo","wcvp","usda");
	$TNRS_DEF_SOURCES = "tropicos,wcvp";	
	$TNRS_CLASSIFICATIONS = array("tropicos","wfo");
	$TNRS_DEF_CLASSIFICATION = "tropicos"; 
} else if ( $DB=="tnrs_4_3" ) {
	$TNRS_SOURCES = array("wfo","wcvp");
	$TNRS_DEF_SOURCES = "wfo,wcvp";
	$TNRS_CLASSIFICATIONS = array("wfo");
	$TNRS_DEF_CLASSIFICATION = "wfo";
} else if ( $DB=="tnrs_4_4" ) {
	$TNRS_SOURCES = array("wfo","wcvp","cact");
	$TNRS_DEF_SOURCES = "wfo";
	$TNRS_CLASSIFICATIONS = array("wfo","wcvp");
	$TNRS_DEF_CLASSIFICATION = "wfo";
} else {
	// Fallback: all historical sources for backward-compatibility
	$TNRS_SOURCES = array("tropicos","tpl","wfo","wcvp","usda","cact");
	$TNRS_DEF_SOURCES = "wfo";	
	$TNRS_CLASSIFICATIONS = array("wfo");
	$TNRS_DEF_CLASSIFICATION = "wfo"; 
}

//////////////////////////////////////////////////
// These options shouldn't need changing
//////////////////////////////////////////////////

$TNRS_CONSTR_HT = array("true","false"); 	// Constrain by higher taxa
$TNRS_CONSTR_TS = array("true","false"); 	// Constrain by taxonomic sources
$TNRS_MATCHES =  array("best","all");		// Matches to return
$TNRS_ACC_MIN = 0;		// Min match accuracy: return all matches
$TNRS_ACC_MAX = 1;		// Max match accuracy: exact matches only

//////////////////////////////////////////////////
// TNRS default options
//////////////////////////////////////////////////

$TNRS_DEF_MODE = "resolve";		// Processing mode
$TNRS_DEF_CONSTR_HT = "false"; 	// Constrain by higher taxa
$TNRS_DEF_CONSTR_TS = "false"; 	// Constrain by taxonomic sources
$TNRS_DEF_MATCHES =  "all";		// Matches to return
$TNRS_DEF_ACC = 0.53;			// Match accuracy

?>
