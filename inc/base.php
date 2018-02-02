<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('class/database.php');
require_once('inc/debug.php');

function config($key, $localonly = false)
{
  global $config;

  if ($key == "modules") {
    // Stelle sicher, dass das "index"-Modul immer aktiv ist!
    if (! in_array("index", $config['modules'])) {
      $config['modules'][] = "index";
    }
    // Stelle sicher, dass das "about"-Modul immer aktiv ist!
    if (! in_array("about", $config['modules'])) {
      $config['modules'][] = "about";
    }
  }

  if ($key == 'modules' && isset($_SESSION['restrict_modules']))
  {
    $modules = array();
    foreach ($config['modules'] as $mod)
    {
      if (in_array($mod, $_SESSION['restrict_modules']))
        $modules[] = $mod;
    }
    return $modules;
  }

  if (array_key_exists($key, $config))
    return $config[$key];
  
  if ($localonly) {
    return NULL;
  }  

  /* read configuration from database */
  $result = db_query( "SELECT `key`, value FROM misc.config" );
  
  while( $object = $result->fetch() ) {
    if (!array_key_exists($object['key'], $config)) {
	    $config[$object['key']]=$object['value'];
    }
  }
  // Sonst wird das Passwort des webadmin-Users mit ausgegeben
  $debug_config = $config;
  unset($debug_config['db_pass']);
  DEBUG($debug_config);
  if (array_key_exists($key, $config))
    return $config[$key];
  else
    logger(LOG_ERR, "inc/base", "config", "Request to read nonexistant config option »{$key}«.");
    return NULL;
}

function get_server_by_id($id) {
  $id = (int) $id;
  $result = db_query("SELECT hostname FROM system.servers WHERE id=?", array($id));
  $ret = $result->fetch();
  return $ret['hostname'];
}


function redirect($target)
{
  global $debugmode;
  if (! $debugmode) {
    header("Location: {$target}");
  } else {
      if (strpos($target, '?') === false) {
        print 'REDIRECT: '.internal_link($target, $target);
      } else {
          list($file, $qs) = explode('?', $target, 2);
          print 'REDIRECT: '.internal_link($file, $target, $qs);
      }
  }
  die();
}


function my_server_id()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT server FROM system.useraccounts WHERE uid=?", array($uid));
  $r = $result->fetch();
  DEBUG($r);
  return $r['server'];
}


function additional_servers()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT server FROM system.user_server WHERE uid=?", array($uid));
  $servers = array();
  while ($s = $result->fetch())
    $servers[] = $s['server'];
  DEBUG($servers);
  return $servers;
}


function server_names()
{
  $result = db_query("SELECT id, hostname FROM system.servers");
  $servers = array();
  while ($s = $result->fetch())
    $servers[$s['id']] = $s['hostname'];
  DEBUG($servers);
  return $servers;
}


function maybe_null($value)
{
  if (! $value)
    return NULL;

  if (strlen( (string) $value ) > 0)
    return (string) $value;
  else
    return NULL;
}


#define('LOG_ERR', 3);
#define('LOG_WARNING', 4);
#define('LOG_INFO', 6);

function logger($severity, $scriptname, $scope, $message)
{
  if (config('logging') <= $severity)
    return;

  $user = NULL;
  if ($_SESSION['role'] & ROLE_SYSTEMUSER)
    $user = $_SESSION['userinfo']['username'];
  elseif ($_SESSION['role'] & ROLE_CUSTOMER)
    $user = $_SESSION['customerinfo']['customerno'];
  
  $args = array(":user" => $user,
                ":remote" => $_SERVER['REMOTE_ADDR'],
                ":scriptname" => $scriptname,
                ":scope" => $scope,
                ":message" => $message);

  db_query("INSERT INTO misc.scriptlog (remote, user,scriptname,scope,message) VALUES (:remote, :user, :scriptname, :scope, :message)", $args);
}

function html_header($arg)
{
  global $html_header;
  $html_header .= $arg;
}

function title($arg)
{
  global $title;
  $title = $arg;
}

function headline($arg)
{
  global $headline;
  $headline = $arg;
}

function output($arg)
{
  global $output;
  $output .= $arg;
}

function footnote($explaination)
{
    global $footnotes;
    if (!isset($footnotes) || !is_array($footnotes)) {
        $footnotes = array();
    }
    $fnid = array_search($explaination, $footnotes);
    DEBUG($footnotes);
    if ($fnid === FALSE) {
        DEBUG("Footnote »{$explaination}« is not in footnotes!");
        $footnotes[] = $explaination;
    }
    $fnid = array_search($explaination, $footnotes);
    return str_repeat('*', ($fnid+1));
}

function random_string($len) 
{
  $s = str_replace('+', '.', base64_encode(random_bytes(ceil($len*3/4))));
  return substr($s, 0, $len);
}


function are_you_sure($query_string, $question)
{
  $query_string = encode_querystring($query_string);
  $token = random_string(20);
  $_SESSION['are_you_sure_token'] = $token;
  title('Sicherheitsabfrage');
  output("
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
  if (! isset($_SESSION['session_token'])) {
    $_SESSION['session_token'] = random_string(10);
  }
  return hash('sha256', $sessid.$form_id.$_SESSION['session_token']);
}


function check_form_token($form_id, $formtoken = NULL)
{
  if ($formtoken == NULL)
    $formtoken = $_REQUEST['formtoken'];
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


function use_module($modname) 
{
    global $prefix, $needed_modules;
    if (! isset($needed_modules)) {
        $needed_modules = array();
    }
    if (in_array($modname, $needed_modules)) {
        return;
    }
    $needed_modules[] = $modname;
    if (! have_module($modname)) {
        system_failure("Soll nicht verfügbares Modul laden!");
    }
    /* setup module include path */
    ini_set('include_path',ini_get('include_path').':./modules/'.$modname.'/include:');
    $style = 'modules/'.$modname.'/style.css';
    if (file_exists($style)) {
        html_header('<link rel="stylesheet" href="'.$prefix.$style.'" type="text/css" />'."\n");
    }

}


function encode_querystring($querystring)
{
  global $debugmode;
  if ($debugmode)
    $querystring = 'debug&'.$querystring;
  $query = explode('&', $querystring);
  $new_query = array();
  foreach ($query AS $item)
    if ($item != '')
    {
      $split = explode('=', $item, 2);
      if (count($split) == 1)
        $new_query[] = $split[0];
      else
        $new_query[] = $split[0].'='.urlencode($split[1]);
    }
  $querystring = implode('&amp;', $new_query);
  if ($querystring)
    $querystring = '?'.$querystring;
  return $querystring;
}


function addnew($file, $label, $querystring = '', $attribs = '')
{
  output('<p class="addnew">'.internal_link($file, $label, $querystring, $attribs).'</p>');
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


function html_datepicker($nameprefix, $timestamp)
{
  $valid_days = array( 1 =>  1,  2 =>  2,  3 =>  3,  4 =>  4,  5 =>  5,
                       6 =>  6,  7 =>  7,  8 =>  8,  9 =>  9, 10 => 10,
                      11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15,
                      16 => 16, 17 => 17, 18 => 18, 19 => 19, 20 => 20,
                      21 => 21, 22 => 22, 23 => 23, 24 => 24, 25 => 25,
                      26 => 26, 27 => 27, 28 => 28, 29 => 29, 30 => 30,
                      31 => 31);
  $valid_months = array( 1 =>  1,  2 =>  2,  3 =>  3,  4 =>  4,  5 =>  5,
                         6 =>  6,  7 =>  7,  8 =>  8,  9 =>  9, 10 => 10,
                        11 => 11, 12 => 12);
  $current_year = (int) date('Y');
  $valid_years = array($current_year => $current_year, 
                       $current_year+1 => $current_year+1,
                       $current_year+2 => $current_year+2,
                       $current_year+3 => $current_year+3,
                       $current_year+4 => $current_year+4);
              
  $selected_day = date('d', $timestamp);
  $selected_month = date('m', $timestamp);
  $selected_year = date('Y', $timestamp);
  $ret = '';
  $ret .= html_select($nameprefix.'_day', $valid_days, $selected_day, 'style="text-align: right;"').". ";
  $ret .= html_select($nameprefix.'_month', $valid_months, $selected_month, 'style="text-align: right;"').". ";
  $ret .= html_select($nameprefix.'_year', $valid_years, $selected_year);
  return $ret;
}

function get_modules_info() 
{
  $modules = config('modules');
  $modconfig = array();
  foreach ($modules AS $name) {
    $modconfig[$name] = NULL;
    if (file_exists('modules/'.$name.'/module.info')) {
      $modconfig[$name] = parse_ini_file('modules/'.$name.'/module.info');
    }
  }
  return $modconfig;
}



?>
