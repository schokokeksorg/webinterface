<?php

require_once('inc/db_connect.php');

function logger($scriptname, $scope, $message)
{
  $user = 'NULL';
  if ($_SESSION['role'] == ROLE_SYSTEMUSER)
    $user = "'{$_SESSION['userinfo']['username']}'";
  elseif ($_SESSION['role'] == ROLE_CUSTOMER)
    $user = "'{$_SESSION['customerinfo']['customerno']}'";
  
  $remote = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);

  $scriptname = mysql_real_escape_string($scriptname);
  $scope = mysql_real_escape_string($scope);
  $message = mysql_real_escape_string($message);

  $query = "INSERT INTO misc.scriptlog (remote, user,scriptname,scope,message) VALUES ('{$remote}', {$user}, '{$scriptname}', '{$scope}', '{$message}');";
  DEBUG($query);
  @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());

}


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
  global $debugmode;
  if ($debugmode)
    $query_string = 'debug&amp;'.$query_string;
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
    system_failure("Internal error!");
  }
  if (! isset($_SESSION['session_token']))
    $_SESSION['session_token'] = random_string(10);
  $formtoken = hash('sha256', $sessid.$form_id.$_SESSION['session_token']);
  return '<input type="hidden" name="formtoken" value="'.$formtoken.'" />'."\n";
}


function check_form_token($form_id)
{
  $formtoken = $_POST['formtoken'];
  $sessid = session_id();
  if ($sessid == "") 
  {
    DEBUG("Uh? Session not running? Wtf?");
    system_failure("Internal error!");
  }

  $correct_formtoken = hash('sha256', $sessid.$form_id.$_SESSION['session_token']);

  if (! ($formtoken == $correct_formtoken))
    system_failure("Possible cross-site-request-forgery!");
}



function internal_link($file, $label, $querystring = '')
{
  $debugstr = '';
  global $debugmode;
  if ($debugmode)
    $debugstr = 'debug&amp;';
  $querystring = str_replace('&', '&amp;', $querystring);

  return "<a href=\"{$file}?{$debugstr}${querystring}\">{$label}</a>";
}


function html_form($form_id, $scriptname, $querystring, $content)
{
  $debugstr = '';
  global $debugmode;
  if ($debugmode)
    $debugstr = 'debug&amp;';
  $querystring = str_replace('&', '&amp;', $querystring);
  $ret = '';
  $ret .= '<form action="'.$scriptname.'?'.$debugstr.$querystring.'" method="post">'."\n";
  $ret .= generate_form_token($form_id);
  $ret .= $content;
  $ret .= '</form>';
  return $ret;  
}




?>
