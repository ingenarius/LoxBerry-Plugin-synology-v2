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
	// Get values from form
	$srv_port = $_POST['srv_port'];
    $srv_init = 0;
	$ds_user = $_POST['ds_user'];
	$ds_pwd = $_POST['ds_pwd'];
    $ds_stored_pwd = $_POST['ds_stored_pwd'];
	$ds_host = $_POST['ds_host'];
	$ds_port = $_POST['ds_port'];
    if (isset($_POST['ds_cids'])) { 
        $ds_cids = $_POST['ds_cids']; 
    } 
    else {
        $ds_cids = 0;  
    }
    if(empty($ds_cids) || $ds_cids == 0) { 
        $cids = ""; 
    }
	else {
        $N = count($ds_cids); 
        $cids = "";
        for($i=0; $i < $N; $i++) { 
            if ($i == 0) {
                $cids = $cids.$ds_cids[$i]; 
            } 
            else {
                $cids = $cids.",".$ds_cids[$i];
            }
        }
    }
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
	$cfg->set("SERVER","INITIAL",$srv_init);
	$cfg->set("DISKSTATION","USER",$ds_user);
	if ($ds_pwd && $ds_pwd != "") { $cfg->set("DISKSTATION","PWD",base64_encode($ds_pwd)); }
	$cfg->set("DISKSTATION","HOST",$ds_host);
	$cfg->set("DISKSTATION","PORT",$ds_port);
	$cfg->set("DISKSTATION","CIDS",$cids);
	$cfg->set("DISKSTATION","NOTIFICATION",$ds_mail);
	$cfg->set("DISKSTATION","SENT_VIA",$ds_sentvia);
	$cfg->set("TELEGRAM","TOKEN",$tbot_token);
	$cfg->set("TELEGRAM","CHAT_ID",$tbot_chat);
	$cfg->set("EMAIL","SERVER",$email_srv);
	$cfg->set("EMAIL","PORT",$email_port);
	if ($email_user && $email_user != "") { $cfg->set("EMAIL","USER",$email_user); }
	else { $cfg->set("EMAIL","USER",""); }
	if ($email_pwd && $email_pwd != "") { $cfg->set("EMAIL","PWD",base64_encode($email_pwd)); }
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
	$ds_stored_pwd = $cfg['DISKSTATION']['PWD'];
	$ds_host = $cfg['DISKSTATION']['HOST'];
	$ds_port = $cfg['DISKSTATION']['PORT'];
	$ds_cids = explode(",", $cfg['DISKSTATION']['CIDS']);
	$ds_mail = $cfg['DISKSTATION']['NOTIFICATION'];
	$ds_sentvia = $cfg['DISKSTATION']['SENT_VIA'];
    if ( $ds_sentvia != 2 ) {
        $mail_cfg = new Config_Lite("$lbsconfigdir/mail.cfg",LOCK_EX,INI_SCANNER_RAW);
        $email_srv = $mail_cfg['SMTP']['SMTPSERVER'];
        $email_port = $mail_cfg['SMTP']['PORT'];
        $email_user = $mail_cfg['SMTP']['SMTPUSER'];
    }
	if ($ds_sentvia == 1) {
		$tbot_token = $cfg['TELEGRAM']['TOKEN'];
		$tbot_chat = $cfg['TELEGRAM']['CHAT_ID'];
        $tbot_testurl = "https://api.telegram.org/bot".$tbot_token."/getUpdates";
	} elseif ($ds_sentvia == 2) {
        $email_srv = $cfg['EMAIL']['SERVER'];
        $email_port = $cfg['EMAIL']['PORT'];
        $email_user = $cfg['EMAIL']['USER'];
        $email_pwd = $email_stored_pwd = $cfg['EMAIL']['PWD'];
		$tbot_token = $tbot_chat = "";
	} else {
		$tbot_token = $tbot_chat = "";
		$email_srv = $email_port = $email_user = $email_pwd = "";
	}	
}

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
// List (sorted by ID) installed cameras with checkboxes to enable/disable them
$cameras = array();
$camstring = "";
$cameras = file("$lbpdatadir/cameras.dat", FILE_IGNORE_NEW_LINES) or die("Unable to open <i>cameras.dat</i>!");
//print_r($cameras); echo "<br>"; 
//print_r($cids); echo "<br>"; 
//print_r($ds_cids); echo "<br>"; 
foreach($cameras as $cam) {
    list($cid, $cmodel) = explode(':', $cam); // cid => cam id; model => description
    //echo "cmodel: ".$cmodel."<br>";
	if ( is_array($ds_cids) && in_array( $cid, $ds_cids, true ) && $cid != 0 ) {
        //echo "yes!<br>";
	    $camstring = $camstring."<input type=\"checkbox\" id=\"ds_cids_".$cid."\" name=\"ds_cids[]\" value=\"$cid\" class=\"custom\" data-mini=\"true\" data-cacheval=\"true\" checked=\"checked\"><label for=\"ds_cids_".$cid."\">$cmodel</label>";
	}
    elseif ($cid == 0) {
        $camstring = "<span class=\"hint\">".$L['TEXT.NO_CAMS']."</span>";
    }
    else {
        //echo "no!<br>";
        $camstring = $camstring."<input type=\"checkbox\" id=\"ds_cids_".$cid."\" name=\"ds_cids[]\" value=\"$cid\" class=\"custom\" data-mini=\"true\" data-cacheval=\"false\"><label for=\"ds_cids_".$cid."\">$cmodel</label>";
    }
}	

// sent_via dropdown box
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
$sent_via_select = "<select name=\"sent_via\" id=\"sent_via\" data-mini=\"true\">$options</select>";

?>

<form method="post" data-ajax="false" name="main_form" id="main_form" action="./index.php">
    <input type="hidden" name="ds_stored_pwd" id="ds_stored_pwd" value="<?=$ds_stored_pwd?>">
    <input type="hidden" name="email_stored_pwd" id="email_stored_pwd" value="<?=$email_stored_pwd?>">
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
					    <?php if (file_exists("/tmp/syno_plugin.lock")) { $pid = file_get_contents('/tmp/syno_plugin.lock'); echo "<span style=\"color:green\">".$L['TEXT.RUNNING']." (process ID: $pid)</span>"; } else { echo "<span style=\"color:red\">".$L['TEXT.NOT_RUNNING']."</span>"; } ?>
					</div>
                    <div class="divTableCell">
                        <?php if ($srv_init == 0) { 
                        echo "<a id=\"btnlogs\" data-role=\"button\" href=\"#\" data-inline=\"true\" data-mini=\"true\" data-icon=\"action\" onClick=\"$.ajax({url: 'ajax_test.php?test=snapshot', type: 'GET', data: { 'test':'snapshot'} }).success(function(data) { $( '#test_server' ).html(data).trigger('create'); });\">Test</a>";
                        } ?>
                        <div id="test_server"></div>
                </div>
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
                    <div class="divTableCell"><span class="hint"><?=$L['HELP.DSIP']?></span></div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSPORT']?></div>
                    <div class="divTableCell"><input type="number" name="ds_port" id="ds_port" value="<?=$ds_port?>" data-validation-rule="special:port"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSCAMS']?></div>
                    <div class="divTableCell"><fieldset data-role="control-group"><?=$camstring?></fieldset></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSEMAIL']?></div>
                    <div class="divTableCell"><input type="text" name="ds_mail" id="ds_mail" value="<?=$ds_mail?>" data-validation-rule="special:email"></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow">
                    <div class="divTableCell"><?=$L['TEXT.DSSNAPSHOT']?></div>
                    <div class="divTableCell"><?=$sent_via_select?></div>
                    <div class="divTableCell">&nbsp;</div>
                </div>
                <div class="divTableRow" id="tbot_1">
                    <div class="divTableCell"><h3><?=$L['TEXT.TBOT']?></h3></div>
                </div>
                <div class="divTableRow" id="tbot_2">
                    <div class="divTableCell"><?=$L['TEXT.TBOTTOKEN']?></div>
                    <div class="divTableCell"><input type="text" name="tbot_token" id="tbot_token" value="<?=$tbot_token?>"></div>
                    <div class="divTableCell">
                        <?php if ( $tbot_token != '' && $tbot_chat != '' ) { ?>
                            <a id="btnlogs" data-role="button" href="<?=$tbot_testurl?>" target="_blank" data-inline="true" data-mini="true" data-icon="action">Test Telegram</a> <?php } ?>
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
                    <div class="divTableCell"><input type="text" name="email_srv" id="email_srv" value="<?=$email_srv?>"></div>
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
                    <div class="divTableCell">&nbsp;</div>
                    <div class="divTableCell"><input type="submit" id="do" value="<?=$L['TEXT.SAVE']?>" data-mini="true"></div>
                    <div class="divTableCell"><a id="btnlogs" data-role="button" href="/admin/system/tools/logfile.cgi?logfile=plugins/synology/synology.log&header=html&format=template" target="_blank" data-inline="true" data-mini="true" data-icon="action"><?=$L['TEXT.LOGFILE']?></a></div>
                </div>
            </div>
        </div>
</form>

<script>
$('#main_form').validate();

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
    validate_enable('#email_port');
    //validate_chk_object(['#srv_port','#ds_mail','#ds_host','#ds_port','#email_srv','#email_port']);
});
</script>
 
<?php 
// Finally print the footer 
LBWeb::lbfooter();
?>
