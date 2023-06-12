<?php

/* 
# For troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$CONFIG_DIR="/home/boyle/bien/tnrs/config/";
$err_show_sql=FALSE;
$sql="SELECT app_version, db_version, build_date, code_version, api_version FROM meta";
 */

////////////////////////////////////////////////////////
// Queries database with supplied sql ($sql)
////////////////////////////////////////////////////////

include $CONFIG_DIR.'db_config.php';

// On error, display SQL if request (turn off for production!)
if ( $err_show_sql ) {
	$sql_disp = " SQL: " . $sql;
} else {
	$sql_disp = "";
}

// connect to the db
$link = mysqli_connect($HOST,$USER,$PWD,$DB);

// check connection
if (mysqli_connect_errno()) {
	echo "Connection failed: ". mysqli_connect_error();
	exit();
}

$qy = mysqli_query($link,$sql) or die('Query failed!'.$sql_disp);

// create one master array of the records
$results_array = array();
if(mysqli_num_rows($qy)) {
	while($result = mysqli_fetch_assoc($qy)) {
		//$results_array[] = array($mode=>$result); // Include $mode
		$results_array[] = $result;					// Omit $mode
	}
}

// disconnect from the db
@mysqli_close($link);

?>