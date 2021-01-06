<?php
require_once "loxberry_web.php";
require_once "Config/Lite.php";

$L = LBSystem::readlanguage("language.ini");

$cfg = new Config_Lite("$lbpconfigdir/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
$srv_port = $cfg['SERVER']['PORT'];
$ds_cids = $cfg['DISKSTATION']['CIDS'];

if (strpos($ds_cids, ",") == false) {
    //if there is just one cam ID -> take it
    $msg = "Snapshot:$ds_cids";
} else {
    //if there are more cam IDs -> take the first one
    $cams = explode(",", $ds_cids);
    $msg = "Snapshot:$cams[0]";
}

if(isset($_GET['test']) && $_GET['test'] == "snapshot"){
    // open UDP socket
    $sock = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_sendto($sock , $msg , strlen($msg) , 0 , '127.0.0.1' , $srv_port);
    socket_close($sock);

    echo "<span class=\"hint\">".$L['TEXT.MSG_SENT'].": \"".$msg."\"<br>".$L['TEXT.CHECK_LOG']."</span>";
}
else {
    echo "<span class=\"hint\">".$L['TEXT.TXT_NONE'].$L['TEXT.MSG_SENT'].": ".$msg."<br>".$L['TEXT.CHECK_LOG']."</span>";
}
