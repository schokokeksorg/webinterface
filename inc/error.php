<?php

require_once("inc/security.php");

function system_failure($reason)
{
        include('inc/top.php');
        echo '
        <h3>Fehler</h3>
        <div class="error">
          <p>Es ist ein Fehler aufgetreten:<br /> '.filter_input_general($reason).'</p>
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


function show_messages()
{
  global $input_error;
  if (isset($input_error))
  {
    echo '<div class="error">
    <h3>Fehler</h3>
    <p>Folgende Fehler wurden festgestellt: </p>
    <ul>
    ';
    foreach ($input_error as $error)
    {
      echo '<li>'.nl2br(filter_input_general($error))."</li>\n";
    }
    echo '</ul>
    </div>';
  }
  if (isset($_SESSION['warning']))
  {
    echo '<div class="error">
    <ul>
    ';
    foreach ($_SESSION['warning'] as $msg)
    {
      echo '<li>'.nl2br(filter_input_general($msg))."</li>\n";
    }
    echo '</ul>
    </div>';
    unset($_SESSION['warning']);
  }
  if (isset($_SESSION['success_msg']))
  {
    echo '<div class="success">
    <ul>
    ';
    foreach ($_SESSION['success_msg'] as $msg)
    {
      echo '<li>'.nl2br(filter_input_general($msg))."</li>\n";
    }
    echo '</ul>
    </div>';
    unset($_SESSION['success_msg']);
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
  global $go;
        $title = 'Login';
        include('inc/top.php');
        if ($why != "")
        {
		// Der User hat einen deeplink benutzt (-> weiß was er tut)
        	echo '<h3>Sie sind nicht am System angemeldet!</h3>';
                echo '<p class="warning"><b>Fehler:</b> '.$why.'</p>';
        }
	else
	{
		// der User hat die Startseite aufgerufen
	        echo '<h3>'.config('company_name').' Webinterface</h3>';
		echo '<p>Auf dieser Seite können Sie diverse Einstellungen Ihres Accounts auf '.config('company_name').' festlegen. Sofern Sie noch kein Kunde von '.config('company_name').' sind, können Sie diese Seite nicht benutzen. Besuchen Sie in diesem Fall bitte unsere <a href="'.config('company_url').'">öffentliche Seite</a>.</p>';
	}
        echo '<form action="" method="post">
        <p><span class="login_label">Benutzer<sup>*</sup>:</span> <input type="text" name="username" size="30" /></p>
        <p><span class="login_label">Passwort:</span> <input type="password" name="password" size="30" /></p>
        <p><span class="login_label">&#160;</span> <input type="submit" value="Anmelden" /></p>
        </form>
        <p><sup>*</sup> Sie können sich hier mit Ihrem System-Benutzernamen, Ihrem IMAP-Account oder Ihrer Kundennummer (jeweils mit zugehörigem Passwort) anmelden. Je nach gewählten Daten erhalten Sie unterschiedliche Zugriffsrechte.</p>
        <p>Sollten Sie Ihr Passwort nicht mehr kennen, wenden Sie sich bitte unter Angabe Ihres Benutzernamens und/oder Ihrer Kundennummer an den Support. Passwörter für E-Mail-Konten kann der Eigentümer des Benutzeraccounts neu setzen.</p>

        <p><em>'.internal_link('/certlogin?destination=go/'.$go, 'Mit einem Client-Zertifikat anmelden').'</em> ('.internal_link('/go/index/certinfo', 'Wie geht das?').')</p>';
	/*
	<p>Sofern Sie für Ihren Kundenaccount noch kein Passwort festgelegt haben oder Ihres vergessen haben, klicken Sie bitte <a href="new_password.php">hier</a></p>
        <p>Sollten Sie als Benutzer Ihr Passwort vergessen haben, wenden Sie sich bitte an den Inhaber des Kundenaccounts.</p>';
	*/
        include('inc/bottom.php');
        die();

}


?>
