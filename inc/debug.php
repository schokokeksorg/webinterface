<?php

require_once('config.php');
require_once('inc/base.php');
$debugmode = (isset($_GET['debug']) && config('enable_debug'));

function DEBUG($str)
{
  global $debugmode;
  if ($debugmode)
    if (is_array($str) || is_object($str))
    {
      echo "<pre>".print_r($str, true)."</pre>\n";
    }
    else
    {
      echo $str . "<br />\n";
    }
}


DEBUG("GET: ".htmlentities(print_r($_GET, true))." / POST: ".htmlentities(print_r($_POST, true)));

?>
