<?php

/////////////////////////////////////////////////
// API parameters
/////////////////////////////////////////////////

// Return offending SQL on error? (true|false)
// TURN OFF FOR PRODUCTION! ($err_show_sql=false)
$err_show_sql=false;

// Maximum permitted input rows per request
// For no limit, set to 0
$MAX_ROWS=5000;	
					
// Number of batches
$NBATCH=10000;	
$NBATCH=25;				

//////////////////////////////////////////////////
// Include paths and filenames
//////////////////////////////////////////////////

// Application base directory
$BASE_DIR = "/home/boyle/bien/tnrs/";

// API directory
$APP_DIR = $BASE_DIR."api/";

// Batch application source directory
$BATCH_DIR=$BASE_DIR."TNRSbatch/src/";

// dir where db user & pwd file kept
// Should be outside application directory and html directory
$CONFIG_DIR = $BASE_DIR . "config/"; 

// Input & output data directory
$DATADIR = $BASE_DIR."data/user/";	// For production, keep outside API directory
$DATADIR = $APP_DIR."example_data/";	// For testing only

// Path and name of log file
$LOGFILE_NAME = "log.txt";
$LOGFILE_PATH = $APP_DIR;
$LOGFILE = $LOGFILE_PATH . $LOGFILE_NAME;

// Path to general php funcions and generic include files
$utilities_path=$APP_DIR."includes/php/";	// Local submodule directory

// General php funcions and generic include files
include $utilities_path."functions.inc";
include $utilities_path."taxon_functions.inc";
include $utilities_path."sql_functions.inc";
$timer_on=$utilities_path."timer_on.inc";
$timer_off=$utilities_path."timer_off.inc";

//////////////////////////////////////////////////
// All TNRS options
// Use to test if submitted options allowed
//////////////////////////////////////////////////

# Am now treating this option as equivalent to a RESTful API "route"
# Options "resolve" & "parse" go to TNRSbatch, but other options
# query database directly
$TNRS_MODES = array("resolve","parse","meta","sources","citations","classifications");

# Sources are now only "tropicos","tpl","usda" but leaving as-is for
# backward compatibility. TNRSbatch will ignore the now non-extistent
# sources and process will still run without error
$TNRS_SOURCES = array("tropicos","tpl","gcc","ildis","usda","ncbi");

#$TNRS_CLASSIFICATIONS = array("tropicos","ncbi");	// Family classification
$TNRS_CLASSIFICATIONS = array("tropicos");	// Family classification

$TNRS_CONSTR_HT = array("true","false"); 	// Constrain by higher taxa
$TNRS_CONSTR_TS = array("true","false"); 	// Constrain by taxonomic sources
$TNRS_MATCHES =  array("best","all");		// Matches to return
$TNRS_ACC_MIN = 0.05;		// Min match accuracy
$TNRS_ACC_MAX = 1;			// Max match accuracy

//////////////////////////////////////////////////
// TNRS default options
//////////////////////////////////////////////////

$TNRS_DEF_SOURCES = "tpl,gcc,ildis,tropicos,usda";	// Taxonomic sources
$TNRS_DEF_CLASSIFICATION = "tropicos"; 				// Family classification
$TNRS_DEF_MODE = "resolve";		// Processing mode
$TNRS_DEF_CONSTR_HT = "false"; 	// Constrain by higher taxa
$TNRS_DEF_CONSTR_TS = "false"; 	// Constrain by taxonomic sources
$TNRS_DEF_MATCHES =  "all";		// Matches to return
$TNRS_DEF_ACC = 0.05;			// Match accuracy

?>
