<?php 

////////////////////////////////////////////////////////////////
// Retrieve and send requested image from database
// Source: https://stackoverflow.com/a/30277594/2757825
// Needs updating to mysqli & get parameters from config file
////////////////////////////////////////////////////////////////

$db = mysql_connect("localhost","user","password") or die(mysql_error()); 

mysql_select_db("shareity",$db) or die(mysql_error()); 
$eid = $_GET['eid']; 

$query = "SELECT logo AS image FROM source WHERE sourceName='$eid'"; 
$result = mysql_query($query) or die(mysql_error()); 
$img_arr = mysql_fetch_array($result); 

header('Content-Type:image/jpeg'); 
echo $img_arr['image']; 

?>
