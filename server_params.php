<?php

/////////////////////////////////////////////////
// Server-specific parameters
// Keep outside repo to preserve settings
/////////////////////////////////////////////////

// Application base directory
$BASE_DIR = "/home/boyle/bien/tnrs/";

// API directory
$APP_DIR = $BASE_DIR."api/";

// Batch application source directory
$BATCH_DIR=$BASE_DIR."tnrs_batch/src/";

// dir where db user & pwd file kept
// Should be outside application directory and html directory
$CONFIG_DIR = $BASE_DIR . "config/"; 

// Input & output data directory
$DATADIR = $BASE_DIR."data/user/";	// For production, keep outside API directory
//$DATADIR = $APP_DIR."example_data/";	// For testing only

// Path and name of log file
$LOGFILE_NAME = "log.txt";
$LOGFILE_PATH = $APP_DIR;
$LOGFILE = $LOGFILE_PATH . $LOGFILE_NAME;

// Path to general php funcions and generic include files
$utilities_path=$APP_DIR."php_utilities/";	// Local submodule directory

// General php funcions and generic include files
include $utilities_path."functions.inc";
include $utilities_path."taxon_functions.inc";
include $utilities_path."sql_functions.inc";
$timer_on=$utilities_path."timer_on.inc";
$timer_off=$utilities_path."timer_off.inc";

?>
