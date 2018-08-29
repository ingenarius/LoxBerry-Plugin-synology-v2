<?php
require_once "loxberry_web.php";
require_once "Config/Lite.php";

$cfg = new Config_Lite("$lbpconfigdir/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
$srv_port = $cfg['SERVER']['PORT'];
$ds_port = $cfg['DISKSTATION']['PORT'];
$ds_cids = $cfg['DISKSTATION']['CIDS'];

if (strpos($ds_cids, ",") == false) {
    $msg = "Snapshot:$ds_cids";
} else {
    $cams = explode(",", $ds_cams);
    $msg = "Snapshot:$cams[0]";
}

if(isset($_GET['test']) && $_GET['test'] == "snapshot"){
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    $len = strlen($msg);
    socket_sendto($sock, $msg, $len, 0, '127.0.0.1', $srv_port);
    socket_close($sock);

    echo "<span class=\"hint\">Message sent: $msg </span>";
}
else {
    echo "<span class=\"hint\">No message sent: $msg </span>";
}
