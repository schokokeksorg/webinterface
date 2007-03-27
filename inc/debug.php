<?php

$debugmode = false;
if (isset($_GET['debug']))
	$debugmode = true;

function DEBUG($str)
{
	global $debugmode;
	if ($debugmode)
		echo $str."<br />\n";
}

?>
