<?php

require_once('inc/db_connect.php');


function config($key)
{
  global $config;
  if (array_key_exists($key, $config))
    return $config[$key];
  else
    logger("inc/base", "config", "Request to read nonexistant config option »{$key}«.");
    return '';
}


function db_query($query)
{
  DEBUG($query);
  $result = @mysql_query($query);
  if (mysql_error())
  {
    $error = mysql_error();
    logger("inc/base", "dberror", "mysql error: {$error}");
    system_failure('Interner Datenbankfehler: »'.iconv('ISO-8859-1', 'UTF-8', $error).'«.');
  }
  $count = @mysql_num_rows($result);
  if (! $count)
    $count = 'no';
  DEBUG("=> {$count} rows");
  return $result; 
}



function maybe_null($value)
{
  if ($value == NULL)
    return 'NULL';

  if (strlen( (string) $value ) > 0)
    return "'".mysql_real_escape_string($value)."'";
  else
    return 'NULL';
}



function logger($scriptname, $scope, $message)
{
  if (config('logging') == false)
    return;

  $user = 'NULL';
  if ($_SESSION['role'] & ROLE_SYSTEMUSER)
    $user = "'{$_SESSION['userinfo']['username']}'";
  elseif ($_SESSION['role'] & ROLE_CUSTOMER)
    $user = "'{$_SESSION['customerinfo']['customerno']}'";
  
  $remote = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);

  $scriptname = mysql_real_escape_string($scriptname);
  $scope = mysql_real_escape_string($scope);
  $message = mysql_real_escape_string($message);

  db_query("INSERT INTO misc.scriptlog (remote, user,scriptname,scope,message) VALUES ('{$remote}', {$user}, '{$scriptname}', '{$scope}', '{$message}');");
}

function html_header($arg)
{
  global $html_header;
  $html_header .= $arg;
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
  $query_string = encode_querystring($query_string);
  $token = random_string(20);
  $_SESSION['are_you_sure_token'] = $token;
  output("<h3>Sicherheitsabfrage</h3>
    <form action=\"{$query_string}\" method=\"post\">
    <div class=\"confirmation\">
      <div class=\"question\">{$question}</div>
      <p class=\"buttons\">
        <input type=\"hidden\" name=\"random_token\" value=\"{$token}\" />
        <input type=\"submit\" name=\"really\" value=\"Ja\" />
        &#160; &#160;
        <input type=\"submit\" name=\"not_really\" value=\"Nein\" />
      </p>
    </div>");
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
  return hash('sha256', $sessid.$form_id.$_SESSION['session_token']);
}


function check_form_token($form_id, $formtoken = NULL)
{
  if ($formtoken == NULL)
    $formtoken = $_POST['formtoken'];
  $sessid = session_id();
  if ($sessid == "") 
  {
    DEBUG("Uh? Session not running? Wtf?");
    system_failure("Internal error! (Session not running)");
  }

  $correct_formtoken = hash('sha256', $sessid.$form_id.$_SESSION['session_token']);

  if (! ($formtoken == $correct_formtoken))
    system_failure("Possible cross-site-request-forgery!");
}


function have_module($modname)
{
  return in_array($modname, config('modules'));
}


function encode_querystring($querystring)
{
  global $debugmode;
  if ($debugmode)
    $querystring = 'debug&'.$querystring;
  DEBUG($querystring);
  $query = explode('&', $querystring);
  DEBUG($query);
  $new_query = array();
  foreach ($query AS $item)
    if ($item != '')
    {
      list($key, $val) = explode('=', $item, 2);
      if ($val == '')
        $new_query[] = $key;
      else
        $new_query[] = $key.'='.urlencode($val);
    }
  DEBUG($new_query);
  $querystring = implode('&amp;', $new_query);
  if ($querystring)
    $querystring = '?'.$querystring;
  DEBUG($querystring);
  return $querystring;
}



function internal_link($file, $label, $querystring = '', $attribs = '')
{
  global $prefix;
  if (strpos($file, '/') === 0)
  {
    $file = $prefix.substr($file, 1);
  }
  $querystring = encode_querystring($querystring);
  return "<a href=\"{$file}{$querystring}\" {$attribs} >{$label}</a>";
}


function html_form($form_id, $scriptname, $querystring, $content)
{
  $querystring = encode_querystring($querystring);
  $ret = '';
  $ret .= '<form action="'.$scriptname.$querystring.'" method="post">'."\n";
  $ret .= '<p style="display: none;"><input type="hidden" name="formtoken" value="'.generate_form_token($form_id).'" /></p>'."\n";
  $ret .= $content;
  $ret .= '</form>';
  return $ret;  
}


function html_select($name, $options, $default='', $free='')
{
  require_once('inc/security.php');
  $ret = "<select name=\"{$name}\" id=\"{$name}\" size=\"1\" {$free} >\n";
  foreach ($options as $key => $value)
  {
    $selected = '';
    if ($default == $key)
      $selected = ' selected="selected" ';
    $key = filter_input_general($key);
    $value = filter_input_general($value);
    $ret .= "  <option value=\"{$key}\"{$selected}>{$value}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}



?>
