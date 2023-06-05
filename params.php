<?php

# The following are needed to retrieve $DB (db name)
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
"logos"
);

# Database-version specific parameters
if ( $DB=="tnrs_4_2" ) {
	# All available taxonomy sources
	$TNRS_SOURCES = array("tropicos","wfo","wcvp","usda");
	
	# All available family classification sources
	$TNRS_CLASSIFICATIONS = array("tropicos","wfo");
	
	# Default sources
	$TNRS_DEF_SOURCES = "tropicos,wcvp";	
	$TNRS_DEF_CLASSIFICATION = "tropicos"; 
} else if ( $DB=="tnrs_4_3" ) {
	$TNRS_SOURCES = array("wfo","wcvp");
	$TNRS_CLASSIFICATIONS = array("wfo");
	$TNRS_DEF_SOURCES = "wfo,wcvp";
	$TNRS_DEF_CLASSIFICATION = "wfo";
} else {
	# Fallback: include all historical sources for backward-compatibility
	$TNRS_SOURCES = array("tropicos","wfo","wcvp","usda");
	$TNRS_CLASSIFICATIONS = array("tropicos","wfo");
	$TNRS_DEF_SOURCES = "tropicos,wcvp";	
	$TNRS_DEF_CLASSIFICATION = "tropicos"; 
}


/* 
# Sources are now only "wfo","wcvp" but leaving as-is for
# backward compatibility. tnrs_batch will ignore the now non-extistent
# sources and process will still run without error
$TNRS_SOURCES = array("tropicos","wfo","wcvp","usda");
$TNRS_SOURCES = array("wfo","wcvp");

//$TNRS_CLASSIFICATIONS = array("tropicos","ncbi");	// Family classification
//$TNRS_CLASSIFICATIONS = array("tropicos","wfo");	// Family classification
$TNRS_CLASSIFICATIONS = array("wfo");	// Family classification
 */

$TNRS_CONSTR_HT = array("true","false"); 	// Constrain by higher taxa
$TNRS_CONSTR_TS = array("true","false"); 	// Constrain by taxonomic sources
$TNRS_MATCHES =  array("best","all");		// Matches to return
$TNRS_ACC_MIN = 0;		// Min match accuracy: return all matches
$TNRS_ACC_MAX = 1;		// Max match accuracy: exact matches only

//////////////////////////////////////////////////
// TNRS default options
//////////////////////////////////////////////////

/* 
//$TNRS_DEF_SOURCES = "tropicos,wcvp";	// Taxonomic sources
//$TNRS_DEF_CLASSIFICATION = "tropicos"; 				// Family classification
$TNRS_DEF_SOURCES = "wfo,wcvp";	// Taxonomic sources
$TNRS_DEF_CLASSIFICATION = "wfo"; 				// Family classification
 */

$TNRS_DEF_MODE = "resolve";		// Processing mode
$TNRS_DEF_CONSTR_HT = "false"; 	// Constrain by higher taxa
$TNRS_DEF_CONSTR_TS = "false"; 	// Constrain by taxonomic sources
$TNRS_DEF_MATCHES =  "all";		// Matches to return
$TNRS_DEF_ACC = 0.53;			// Match accuracy

?>
