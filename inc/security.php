<?php


function strong_password($password)
{
  include("config.php");
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


function filter_input_username( $input )
{
        return ereg_replace("[^[:alnum:]\_\.\+\-]", "", $input );
}

function filter_input_hostname( $input )
{
        $input = strtolower($input);
        return ereg_replace("[^[:alnum:]äöü\.\-]", "", $input );
}

function filter_quotes( $input )
{
        return ereg_replace('["\'`]', '', $input );
}

function filter_shell( $input )
{
        return ereg_replace('[^-[:alnum:]\_\.\+ßäöüÄÖÜ/%§=]', '', $input );
}

function check_path( $input )
{
  DEBUG("checking {$input} for valid path name");
  if ($input != filter_input_general($input))
  {
    DEBUG("HTML-Krams im Pfad");
    return False;
  }
  $components = explode("/", $input);
  foreach ($components AS $item)
  {
    if ($item == '..')
    {
      DEBUG("»..« im Pfad");
      return False;
    }
  }
  return (preg_match('/^[a-z0-9.@\/_-]*$/',$input) == 1);
}


function check_emailaddr( $input )
{
        return (preg_match("/^[a-z]+[a-z0-9]*[\.|\-|_]?[a-z0-9]+@([a-z0-9]*[\.|\-]?[a-z0-9]+){1,4}\.[a-z]{2,4}$/i", $input ) == 1);
}


?>
