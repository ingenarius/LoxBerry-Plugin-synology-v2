<?php
require_once "loxberry_web.php";
require_once "Config/Lite.php";

$L = LBSystem::readlanguage("language.ini");

//$cfg = new Config_Lite("$lbpconfigdir/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
$cfg = new Config_Lite("../../config/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
$srv_port = $cfg['SERVER']['PORT'];
$ds_port = $cfg['DISKSTATION']['PORT'];
$ds_cids = $cfg['DISKSTATION']['CIDS'];

//Get camera data
// $myfile = fopen("$lbpdatadir/cameras.dat", "r") or die("Unable to open file!");
// $data = fread($myfile,filesize("$lbpdatadir/cameras.dat"));
// fclose($myfile);
// $order   = array("\r\n", "\n", "\r");
// $replace = '<br />';
// $cams = str_replace($order, $replace, $data);

// echo "<span class=\"hint\">".$cams."</span>";

$cameras = array();
$camstring = "";
// $handle = fopen("../../data/cameras.dat", "r") or die("Unable to open file!");
$cameras = file("../../data/cameras.dat", FILE_IGNORE_NEW_LINES);
// while (($cam = fgets($handle)) !== false)
    // array_push($cameras, $cam);
// fclose($handle);

foreach($cameras as $cam) {
	// echo "RAW: $cam";
	list($cid, $cmodel) = explode(':', $cam);
	$camstring = $camstring."<input type=\"checkbox\" name=\"cam$cid\" value=\"$cmodel\"><br>";
	//echo "ID: $cid, Model: $cmodel";
}
echo "\n";
echo $camstring;
echo "\n";