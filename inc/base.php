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
  $_SESSION['are_you_sure_token'] = $token;
  output("<form action=\"?{$query_string}\" method=\"post\">\n");
  output("<p class=\"confirmation\">{$question}<br />\n");
  output("<input type=\"hidden\" name=\"random_token\" value=\"{$token}\" />\n");
  output("<input type=\"submit\" name=\"really\" value=\"Ja\" />\n<input type=\"submit\" name=\"not_really\" value=\"Nein\" /></p>\n");
  output("</form>\n");
}


function user_is_sure()
{
  if (isset($_POST['really']))
  {
    if ($_POST['random_token'] == $_SESSION['are_you_sure_token'])
      return true;
    else
      system_failure("Possible Cross-site-request-forgery detected!");
  }
  elseif (isset($_POST['not_really']))
    return false;
  else
    return NULL;
}



function generate_form_token($form_id)
{
  require_once("inc/debug.php");
  $sessid = session_id();
  if ($sessid == "") 
  {
    DEBUG("Uh? Session not running? Wtf?");
    return '';
  }
  if (! isset($_SESSION['session_token']))
    $_SESSION['session_token'] = random_string(10);
  $session_token = $_SESSION['session_token'];
  $formtoken = hash('sha256', $sessid.$form_id.$session_token);
  return '<input type="hidden" name="formtoken" value="'.$formtoken.'" />'."\n";
}


function check_form_token($form_id)
{
  $formtoken = $_POST['formtoken'];
  $sessid = session_id();
  if ($sessid == "") 
  {
    DEBUG("Uh? Session not running? Wtf?");
    return '';
  }

  $session_token = $_SESSION['session_token'];
  $correct_formtoken = hash('sha256', $sessid.$form_id.$session_token);

  if (! ($formtoken == $correct_formtoken))
    system_failure("Possible cross-site-request-forgery!");
}

?>
