<?php

require_once("inc/security.php");

function system_failure($reason)
{
	input_error($reason);
	show_page();
        die();
}


function input_error($reason)
{
  global $input_error;
  if (!isset($input_error))
    $input_error = array();
  array_push($input_error, $reason);
}

function warning($msg)
{
  if (!isset($_SESSION['warning']))
    $_SESSION['warning'] = array();
  array_push($_SESSION['warning'], $msg);
}

function success_msg($msg)
{
  if (!isset($_SESSION['success_msg']))
    $_SESSION['success_msg'] = array();
  array_push($_SESSION['success_msg'], $msg);
}


function we_have_an_error()
{
  global $input_error;

  return ((count($input_error) + count($_SESSION['warning'])) > 0);

}


function get_messages()
{
  $messages = '';
  global $input_error;
  if (isset($input_error))
  {
    $messages .= '<div class="error">
    <h3>Fehler</h3>
    <p>Folgende Fehler wurden festgestellt: </p>
    <ul>
    ';
    foreach ($input_error as $error)
    {
      $messages .= '<li>'.nl2br(filter_input_general($error))."</li>\n";
    }
    $messages .= '</ul>
    </div>';
  }
  if (isset($_SESSION['warning']))
  {
    $messages .= '<div class="error">
    <ul>
    ';
    foreach ($_SESSION['warning'] as $msg)
    {
      $messages .= '<li>'.nl2br(filter_input_general($msg))."</li>\n";
    }
    $messages .= '</ul>
    </div>';
    unset($_SESSION['warning']);
  }
  if (isset($_SESSION['success_msg']))
  {
    $messages .= '<div class="success">
    <ul>
    ';
    foreach ($_SESSION['success_msg'] as $msg)
    {
      $messages .= '<li>'.nl2br(filter_input_general($msg))."</li>\n";
    }
    $messages .= '</ul>
    </div>';
    unset($_SESSION['success_msg']);
  }
  return $messages;
}

function show_messages() 
{
  echo get_messages();
}

function require_role($roles)
{
  if (! is_array($roles))
    $roles = array($roles);
  $allow = false;
  foreach ($roles as $role)
    if ($role & $_SESSION['role'])
      $allow = true;
  if (! $allow)
    if ($_SESSION['role'] == ROLE_ANONYMOUS)
      login_screen("Diese Seite können Sie erst benutzen, wenn Sie sich mit Ihren Zugangsdaten anmelden.");
    else
      login_screen("Diese Seite können Sie mit Ihren aktuellen Zugriffsrechten nicht benutzen, bitte melden Sie sich mit den benötigten Zugriffsrechten an!");
}


function login_screen($why)
{
  require_once('inc/theme.php');
  if ($why) {
    warning($why);
  }
  show_page('login');
  die();
}


?>
