<?php

function system_failure($reason)
{
        include('inc/top.php');
        echo '<div class="error">
        <h3>Fehler</h3>
        <p>Es ist ein Fehler aufgetreten: '.$reason.'</p>
        </div>';
        include('inc/bottom.php');
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
  global $warning;
  if (!isset($warning))
    $warning = array();
  array_push($warning, $msg);
}

function success_msg($msg)
{
  global $success_msg;
  if (!isset($success_msg))
    $success_msg = array();
  array_push($success_msg, $msg);
}


function show_messages()
{
  global $input_error;
  global $success_msg;
  global $warning;
  if (isset($input_error))
  {
    echo '<div class="error">
    <h3>Fehler</h3>
    <p>Folgende Fehler wurden festgestellt: </p>
    <ul>
    ';
    foreach ($input_error as $error)
    {
      echo '<li>'.$error."</li>\n";
    }
    echo '</ul>
    </div>';
  }
  if (isset($warning))
  {
    echo '<div class="error">
    <ul>
    ';
    foreach ($warning as $msg)
    {
      echo '<li>'.$msg."</li>\n";
    }
    echo '</ul>
    </div>';
  }
  if (isset($success_msg))
  {
    echo '<div class="success">
    <ul>
    ';
    foreach ($success_msg as $msg)
    {
      echo '<li>'.$msg."</li>\n";
    }
    echo '</ul>
    </div>';
  }
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
        $title = 'Login';
        include('inc/top.php');
        echo '<h3>Sie sind nicht am System angemeldet!</h3>';
        if ($why != "")
        {
                echo '<p class="warning"><b>Fehler:</b> '.$why.'</p>';
        }
        echo '<form action="" method="post">
        <p><span class="login_label">Benutzer<sup>*</sup>:</span> <input type="text" name="username" size="30" /></p>
        <p><span class="login_label">Passwort:</span> <input type="password" name="password" size="30" /></p>
        <p><span class="login_label">&nbsp;</span> <input type="submit" value="Anmelden" /></p>
        </form>
        <p><sup>*</sup> Sie können sich hier mit Ihrem System-Benutzernamen, Ihrer System-User-ID oder Ihrer Kundennummer anmelden. Je nach gewählten Daten erhalten Sie unterschiedliche Zugriffsrechte.</p>
        <p>Sofern Sie für Ihren Kundenaccount noch kein Passwort festgelegt haben oder Ihres vergessen haben, klicken Sie bitte <a href="new_password.php">hier</a></p>
        <p>Sollten Sie als Benutzer Ihr Passwort vergessen haben, wenden Sie sich bitte an den Inhaber des Kundenaccounts.</p>';
        include('inc/bottom.php');
        die();

}


?>
