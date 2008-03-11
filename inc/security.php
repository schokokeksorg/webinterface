<?php

require_once('inc/error.php');


function strong_password($password)
{
  include("config.php");
  if (isset($config['use_cracklib']) and $config['use_cracklib'] == false) {
    DEBUG('Cracklib deaktiviert');
    return true;
  }
  DEBUG("Öffne Wörterbuch: {$config['cracklib_dict']}");
  if (! ($dict = crack_opendict($config['cracklib_dict'])))
  {
    logger("inc/security.php", "cracklib", "could not open cracklib-dictionary »{$config['cracklib_dict']}«");
    system_failure("Kann Crack-Lib-Wörterbuch nicht öffnen: {$config['cracklib_dict']}");
  }
  // Führe eine Überprüfung des Passworts durch
  $check = crack_check($dict, $password);

  $message = crack_getlastmessage();
  crack_closedict($dict);

  if ($check === True)
  {
    DEBUG("Passwort ok");
    return true;
  }
  else
  {
    DEBUG("Passwort nicht ok: {$message}");
    return $message;
  }
}


function filter_input_general( $input )
{
  return htmlspecialchars(iconv('UTF-8', 'UTF-8', $input), ENT_QUOTES, 'UTF-8');
}


function verify_input_general( $input )
{
  if (filter_input_general($input) != $input) {
    system_failure("Ihre Daten enthielten ungültige Zeichen!");
    logger('inc/security.php', 'verify_input_general', 'Ungültige Daten: '.$input);
  }
}


function filter_input_username( $input )
{
  return ereg_replace("[^[:alnum:]\_\.\+\-]", "", $input );
}

function verify_input_username( $input )
{
  if (filter_input_username( $input ) != $input) {
    system_failure("Ihre Daten enthielten ungültige Zeichen!");
    logger('inc/security.php', 'verify_input_username', 'Ungültige Daten: '.$input);
  }
}



function filter_input_hostname( $input )
{
  $input = str_replace(array('Ä', 'Ö', 'Ü'), array('ä', 'ö', 'ü'), strtolower($input));
  $input = rtrim($input, "\t\n\r\x00 .");
  $input = ltrim($input, "\t\n\r\x00 .");
  if (ereg_replace("[^[:alnum:]äöü\.\-]", "", $input ) != $input)
    system_failure("Ihre Daten enthielten ungültige Zeichen!");
  if (strstr($input, '..'))
    system_failure("Ungültiger Hostname");
  return $input;
}



function filter_quotes( $input )
{
  return ereg_replace('["\'`]', '', $input );
}



function filter_shell( $input )
{
  return ereg_replace('[^-[:alnum:]\_\.\+ßäöüÄÖÜ/%§=]', '', $input );
}

function verify_shell( $input )
{
  if (filter_shell($input) != $input)
    system_failure("Ihre Daten enthielten ungültige Zeichen!");
}



function check_path( $input )
{
  DEBUG("checking {$input} for valid path name");
  if ($input != filter_input_general($input))
  {
    logger('inc/security.php', 'check_path', 'HTML-Krams im Pfad: '.$input);
    DEBUG("HTML-Krams im Pfad");
    return False;
  }
  $components = explode("/", $input);
  foreach ($components AS $item)
  {
    if ($item == '..')
    {
      logger('inc/security.php', 'check_path', '»..« im Pfad: '.$input);
      DEBUG("»..« im Pfad");
      return False;
    }
  }
  return (preg_match('/^[A-Za-z0-9.@\/_-]*$/',$input) == 1);
}


function check_emailaddr( $input )
{
  return (bool) preg_match('/^[a-z0-9][a-z0-9%\[\]\.\-\_+]*@[a-z0-9\.\-]+\.[a-z]{2,4}/i', $input);
}

function check_domain( $input )
{
  return (bool) preg_match("/[a-z0-9\.\-]+\.[a-z]{2,4}$/i", $input );
}


?>
