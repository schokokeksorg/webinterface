<?php

require_once('config.php');
global $config;
$debugmode = (isset($_GET['debug']) && $config['enable_debug']);


function DEBUG($str)
{
	global $debugmode;
	if ($debugmode)
    if (is_array($str))
    {
      echo "<pre>".print_r($str, true)."</pre>\n";
    }
    else
    {
	  	echo $str."<br />\n";
    }
}

?>
