<?php
require_once "loxberry_web.php";
require_once "Config/Lite.php";

// This will read your language files to the array $L
$L = LBSystem::readlanguage("language.ini");
$template_title = ucfirst($lbpplugindir);
$helplink = $L['HELP.LINK'];
$helptemplate = "#";

LBWeb::lbheader($template_title, $helplink, $helptemplate);

if ($_POST){
	echo "<h1> POST AREA </h1>";
	// Get values from form
	$srv_port = $_POST['srv_port'];
	$ds_user = $_POST['ds_user'];
	$ds_pwd = $_POST['ds_pwd'];
	$ds_stored_pwd = $_POST['ds_stored_pwd'];
	$ds_host = $_POST['ds_host'];
	$ds_port = $_POST['ds_port'];
	$ds_cids = $_POST['ds_cids'];
	$ds_mail = $_POST['ds_mail'];
	$ds_sentvia = $_POST['sent_via'];
	if ($ds_sentvia == 1) {
    	$tbot_token = $_POST['tbot_token'];
    	$tbot_chat = $_POST['tbot_chat'];
    	$email_srv = $email_port = $email_user = $email_pwd = $email_stored_pwd = "";
	} elseif ($ds_sentvia == 2) {
    	$tbot_token = $tbot_chat = "";
    	$email_srv = $_POST['email_srv'];
    	$email_port = $_POST['email_port'];
    	$email_user = $_POST['email_user'];
    	$email_pwd = $_POST['email_pwd'];
    	$email_stored_pwd = $_POST['email_stored_pwd'];
	}
	else {
    	$tbot_token = $tbot_chat = "";
    	$email_srv = $email_port = $email_user = $email_pwd = $email_stored_pwd = "";
	}
	// Write new config file
	$cfg = new Config_Lite("$lbpconfigdir/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
	$cfg->setQuoteStrings(False);
	$cfg->set("SERVER","PORT",$srv_port);
	$cfg->set("SERVER","INITIAL","0");
	$cfg->set("DISKSTATION","USER",$ds_user);
	if ($ds_pwd && $ds_pwd != "") { $cfg->set("DISKSTATION","PWD",base64_encode($ds_pwd)); }
	else { $cfg->set("DISKSTATION","PWD",$ds_stored_pwd); }
	$cfg->set("DISKSTATION","HOST",$ds_host);
	$cfg->set("DISKSTATION","PORT",$ds_port);
	$cfg->set("DISKSTATION","CIDS",$ds_cids);
	$cfg->set("DISKSTATION","NOTIFICATION",$ds_mail);
	$cfg->set("DISKSTATION","SENT_VIA",$ds_sentvia);
	$cfg->set("TELEGRAM","TOKEN",$tbot_token);
	$cfg->set("TELEGRAM","CHAT_ID",$tbot_chat);
	$cfg->set("EMAIL","SERVER",$email_srv);
	$cfg->set("EMAIL","PORT",$email_port);
	if ($email_user && $email_user != "") { $cfg->set("EMAIL","USER",$email_user); }
	else { $cfg->set("EMAIL","USER",""); }
	if ($email_pwd && $email_pwd != "") { $cfg->set("EMAIL","PWD",base64_encode($email_pwd)); }
	else { $cfg->set("EMAIL","PWD",$email_stored_pwd); }
	$cfg->save();
	// Restart Daemon
    pclose(popen("$lbhomedir/system/daemons/plugins/$lbpplugindir restart", 'r'));
    // Get cameras
	pclose(popen("python $lbpbindir/cameras.py", 'r'));
} 
else {
	// Get values from config file
	$cfg = new Config_Lite("$lbpconfigdir/plugin.cfg",LOCK_EX,INI_SCANNER_RAW);
	$srv_port = $cfg['SERVER']['PORT'];
	$srv_init = $cfg['SERVER']['INITIAL'];
	$ds_user = $cfg['DISKSTATION']['USER'];
	$ds_pwd = $cfg['DISKSTATION']['PWD'];
	$ds_host = $cfg['DISKSTATION']['HOST'];
	$ds_port = $cfg['DISKSTATION']['PORT'];
	$ds_cids = $cfg['DISKSTATION']['CIDS'];
	$ds_mail = $cfg['DISKSTATION']['NOTIFICATION'];
	$ds_sentvia = $cfg['DISKSTATION']['SENT_VIA'];
	if ($ds_sentvia == 1) {
		$tbot_token = $cfg['TELEGRAM']['TOKEN'];
		$tbot_chat = $cfg['TELEGRAM']['CHAT_ID'];
        $tbot_testurl = "https://api.telegram.org/bot".$tbot_token."/getUpdates";
		$email_srv = $email_port = $email_user = $email_pwd = "";
	} elseif ($ds_sentvia == 2) {
		$tbot_token = $tbot_chat = "";
		$email_srv = $cfg['EMAIL']['SERVER'];
		$email_port = $cfg['EMAIL']['PORT'];
		$email_user = $cfg['EMAIL']['USER'];
		$email_pwd = $cfg['EMAIL']['PWD'];
	} else {
		$tbot_token = $tbot_chat = "";
		$email_srv = $email_port = $email_user = $email_pwd = "";
	}	
}

if(isset($_GET['test']) && $_GET['test'] == "snapshot"){
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    
    if (strpos($ds_cids, ",") == false) {
        $msg = "Snapshot:$ds_cids";
    } else {
        $cams = explode(",", $ds_cams);
        $msg = "Snapshot:$cams[0]";
    }
    $len = strlen($msg);

    socket_sendto($sock, $msg, $len, 0, '127.0.0.1', $srv_port);
    socket_close($sock);
}

//Get camera data
$myfile = fopen("$lbpdatadir/cameras.dat", "r") or die("Unable to open file!");
$data = fread($myfile,filesize("$lbpdatadir/cameras.dat"));
fclose($myfile);
$order   = array("\r\n", "\n", "\r");
$replace = '<br />';
$cams = str_replace($order, $replace, $data);

//Sent via Dropdown Box
$choices = array(0 => $L['TEXT.TXT_NONE'], 1 => "Telegram Bot", 2 => "Email");
$options = "";
foreach($choices as $code => $name) {
	if ($code == $ds_sentvia) {
		$options .= "<option selected value=\"$code\">$name</option>\n";
	}
	else {
  		$options .= "<option value=\"$code\">$name</option>\n";
	}
}
$select = "<select name=\"sent_via\" id=\"sent_via\" data-mini=\"true\">$options</select>";

 
// This is the main area for your plugin
?>
<style>
.divTable{
    display: table;
    width: 100%;
}
.divTableRow {
    display: table-row;
}
.divTableHeading {
    background-color: #EEE;
    display: table-header-group;
}
.divTableCell, .divTableHead {
    border: 0px dotted #999999;
    display: table-cell;
    padding: 3px 10px;
    vertical-align: middle;
}
.divTableBody {
    display: table-row-group;
}
</style>
<h2><?=$L['TEXT.GREETING']?></h2>

<?php
// debug section
//echo "<p>$cams</p>";
?>

<form method="post" data-ajax="false" name="main_form" id="main_form" action="./index.php">
    <input type="hidden" name="ds_stored_pwd" id="ds_stored_pwd" value="<?=$ds_pwd?>">
    <input type="hidden" name="email_stored_pwd" id="email_stored_pwd" value="<?=$email_pwd?>">
        <div class="divTable">
            <div class="divTableBody">
                <div class="divTableRow">
                    <div class="divTableCell"><h3><?=$L['TEXT.SRV'].' '.$L['TEXT.SETTINGS']?></h3></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell" style="width:25%"><?=$L['TEXT.SRVPORT']?></div>
                    <div class="divTableCell"><input type="number" name="srv_port" id="srv_port" min="1025" max="65535" value="<?=$srv_port?>" data-validation-rule="special:number-min-max-value:1025:65535"></div>
                    <div class="divTableCell" style="width:25%"><span class="hint"><?=$L['HELP.SRV']?></span></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell">Status</div>
                    <div class="divTableCell">
					<?php if (file_exists("/tmp/syno_plugin.lock")) { echo "<span style=\"color:green\">".$L['TEXT.RUNNING']."</span>"; } else { echo "<span style=\"color:red\">".$L['TEXT.NOT_RUNNING']."</span>"; } ?>
					</div>
                    <div class="divTableCell"><span class="hint"><a href="#" onClick="$.ajax({url: 'test_server.php?test=snapshot', type: 'GET', data: { 'test':'snapshot'} }).success(function(data) { $( '#test_server' ).html(data).trigger('create'); }) ;">Test Server</a></span><div id="test_server"></div></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><h3><?=$L['TEXT.DS'].' '.$L['TEXT.SETTINGS']?></h3></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSUSER']?></div>
                    <div class="divTableCell"><input type="text" name="ds_user" id="ds_user" value="<?=$ds_user?>"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSPWD']?></div>
                    <div class="divTableCell"><input type="password" name="ds_pwd" id="ds_pwd"></div>
                    <div class="divTableCell"><span class="hint"><?=$L['HELP.DSPWD']?></span></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSIP']?></div>
                    <div class="divTableCell"><input type="text" name="ds_host" id="ds_host" value="<?=$ds_host?>" data-validation-rule="special:hostname_or_ipaddr"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSPORT']?></div>
                    <div class="divTableCell"><input type="number" name="ds_port" id="ds_port" value="<?=$ds_port?>" data-validation-rule="special:port"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSCAMS']?></div>
                    <div class="divTableCell"><input type="text" name="ds_cids" id="ds_cids" value="<?=$ds_cids?>"></div>
                    <div class="divTableCell"><span class="hint"><a href="#" onClick="$.ajax({url: 'ajax_cams.php', type: 'GET', data: { 'get':'cams'} }).success(function(data) { $( '#installed_cams' ).html(data).trigger('create'); }) ;"><?=$L['TEXT.INSTALLED_CAMS']?></a></span><div id="installed_cams"></div></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSEMAIL']?></div>
                    <div class="divTableCell"><input type="text" name="ds_mail" id="ds_mail" value="<?=$ds_mail?>" data-validation-rule="special:email"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSSNAPSHOT']?></div>
                    <div class="divTableCell"><?=$select?></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow" id="tbot_1">
                    <div class="divTableCell"><h3><?=$L['TEXT.TBOT']?></h3></div>
                </div>
                <div class="divTableRow" id="tbot_2">
                    <div class="divTableCell"><?=$L['TEXT.TBOTTOKEN']?></div>
                    <div class="divTableCell"><input type="text" name="tbot_token" id="tbot_token" value="<?=$tbot_token?>"></div>
                    <div class="divTableCell"><span class="hint">
                        <a href="#" onClick="$.ajax({url: '<?=$tbot_testurl?>', type: 'GET', data: { 'tbot':'test'} }).success(function(data) { $( '#tbot_test' ).html(data).trigger('create'); }) ;">Test</a>
                        <a href="<?=$tbot_testurl?>" target="_blank">Test Telegram API</a></span><div id="tbot_test"></div>
                    </div>
                </div>
                <div class="divTableRow" id="tbot_3">
                    <div class="divTableCell"><?=$L['TEXT.TBOTCHAT']?></div>
                    <div class="divTableCell"><input type="text" name="tbot_chat" id="tbot_chat" value="<?=$tbot_chat?>"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow" id="email_1">
                    <div class="divTableCell"><h3><?=$L['TEXT.EMAIL']?></h3></div>
                </div>
                <div class="divTableRow" id="email_2">
                    <div class="divTableCell"><?=$L['TEXT.EMAILSRV']?> *</div>
                    <div class="divTableCell"><input type="text" name="email_srv" id="email_srv" value="<?=$email_srv?>" data-validation-rule="special:hostname_or_ipaddr"></div>
                    <div class="divTableCell"><span class="hint"><?=$L['HELP.EMAILSRV']?></span></div>
                </div>
                <div class="divTableRow" id="email_3">
                    <div class="divTableCell"><?=$L['TEXT.EMAILPORT']?> *</div>
                    <div class="divTableCell"><input type="number" name="email_port" id="email_port" value="<?=$email_port?>" data-validation-rule="special:port"></div>
                    <div class="divTableCell"><span class="hint"><?=$L['HELP.EMAILPORT']?></span></div>
                </div>
                <div class="divTableRow" id="email_4">
                    <div class="divTableCell"><?=$L['TEXT.EMAILUSER']?></div>
                    <div class="divTableCell"><input type="text" name="email_user" id="email_user" value="<?=$email_user?>"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow" id="email_5">
                    <div class="divTableCell"><?=$L['TEXT.EMAILPWD']?></div>
                    <div class="divTableCell"><input type="password" name="email_pwd" id="email_pwd"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><input type="submit" id="do" value="<?=$L['TEXT.SAVE']?>" data-mini="true"></div>
                    <div class="divTableCell"><span class="hint"><?=$L['HELP.REBOOT']?></span></div>
                    <div class="divTableCell"><a id="btnlogs" data-role="button" href="/admin/system/tools/logfile.cgi?logfile=plugins/synology/synology.log&header=html&format=template" target="_blank" data-inline="true" data-mini="true" data-icon="action"><?=$L['TEXT.LOGFILE']?></a></div>
                </div>
            </div>
        </div>
</form>

<script>
$('#main_form').validate();

//$.ajax({url: 'test_server.php?test=snapshot', type: 'GET', data: { 'test':'snapshot'} }).success(function(data) { $( '#test_server' ).html(data).trigger('create'); }) ;

$(function() {
    // show / hide block for telegram configuration parts
    if($('#sent_via').val() == '1') {
        $('#tbot_1').show(); $('#tbot_2').show(); $('#tbot_3').show();
        $('#email_1').hide(); $('#email_2').hide(); $('#email_3').hide(); $('#email_4').hide(); $('#email_5').hide();
    } 
    // show / hide block for email configuration parts
    else if ($('#sent_via').val() == '2') {
        $('#tbot_1').hide(); $('#tbot_2').hide(); $('#tbot_3').hide();
        $('#email_1').show(); $('#email_2').show(); $('#email_3').show(); $('#email_4').show(); $('#email_5').show();
    }  
    else {
        $('#tbot_1').hide(); $('#tbot_2').hide(); $('#tbot_3').hide();
        $('#email_1').hide(); $('#email_2').hide(); $('#email_3').hide(); $('#email_4').hide(); $('#email_5').hide();
    }
    $('#sent_via').change(function(){
        if($('#sent_via').val() == '1') {
            $('#tbot_1').show(); $('#tbot_2').show(); $('#tbot_3').show();
            $('#email_1').hide(); $('#email_2').hide(); $('#email_3').hide(); $('#email_4').hide(); $('#email_5').hide();
        } 
        else if($('#sent_via').val() == '2') {
            $('#tbot_1').hide(); $('#tbot_2').hide(); $('#tbot_3').hide();
            $('#email_1').show(); $('#email_2').show(); $('#email_3').show(); $('#email_4').show(); $('#email_5').show();
        } 
        else {
            $('#tbot_1').hide(); $('#tbot_2').hide(); $('#tbot_3').hide();
            $('#email_1').hide(); $('#email_2').hide(); $('#email_3').hide(); $('#email_4').hide(); $('#email_5').hide();
        }
    });
});
$( document ).ready(function()
{
    validate_enable('#srv_port');
    validate_enable('#ds_mail');
    validate_enable('#ds_host');
    validate_enable('#ds_port');
    validate_enable('#email_srv');
    validate_enable('#email_port');
    //validate_chk_object(['#srv_port','#ds_mail','#ds_host','#ds_port','#email_srv','#email_port']);
});
</script>
 
<?php 
// Finally print the footer 
LBWeb::lbfooter();
?>
