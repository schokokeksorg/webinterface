<?php

function output($arg)
{
  global $output;
  $output .= $arg;
}


function random_string($nc, $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $l=strlen($a)-1; $r='';
    while($nc-->0) $r.=$a{mt_rand(0,$l)};
    return $r;
 }


function are_you_sure($query_string, $question)
{
  $token = random_string(20);
  $_SESSION['random_token'] = $token;
  output("<form action=\"?{$query_string}\" method=\"post\">\n");
  output("<p class=\"confirmation\">{$question}<br />\n");
  output("<input type=\"hidden\" name=\"random_token\" value=\"{$token}\" />\n");
  output("<input type=\"submit\" name=\"really\" value=\"Ja\" />\n<input type=\"submit\" name=\"not_really\" value=\"Nein\" /></p>");
}


function user_is_sure()
{
  if (isset($_POST['really']))
  {
    if ($_POST['random_token'] == $_SESSION['random_token'])
      return true;
    else
      system_failure("Possible Cross-site-request-forgery detected!");
  }
  elseif (isset($_POST['not_really']))
    return false;
  else
    return NULL;
}



?>
