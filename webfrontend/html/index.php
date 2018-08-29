<?php
require_once "loxberry_web.php";
 
// This will read your language files to the array $L
$L = LBSystem::readlanguage("language.ini");
$template_title = ucfirst($lbpplugindir);
$helplink = "https://www.loxwiki.eu/display/LOXBERRY/Synology+Surveillance+Station"
$helptemplate = "help.html";
  
LBWeb::lbheader($template_title, $helplink, $helptemplate);
 
// This is the main area for your plugin
?>
<p><?=$L['TEXT.GREETING']?></p>
 
<?php 
// Finally print the footer 
LBWeb::lbfooter();
?>
