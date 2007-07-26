<?php
$title = "Bei schokokeks.org registrieren";

//require_once('inc/error.php');
//system_failure("Diese Funktion ist noch nicht fertiggestellt.");

require_once('newpass.php');

$fail = array();
$success = false;
$customerno = 0;

if (count($_POST) > 0)
{
  require_once('inc/security.php');
  foreach (array_keys($_POST) AS $key)  
  {
    $_POST[$key] = filter_input_general(chop($_POST[$key]));
  }
  if (! in_array($_POST['anrede'], array("Herr", "Frau", "Firma")))
  {
    array_push($fail, 'Was haben Sie mit dem Anrede-Feld angestellt?!');
  }
  if (! ( (strlen($_POST['nachname']) > 1 || strlen($_POST['firma']) > 1) && strlen($_POST['email']) > 1 ))
  {
    array_push($fail, 'Sie müssen mindestens die Felder »Nachname« oder »Firma« sowie »E-Mail« ausfüllen!');
  }
  elseif (! $_POST['agb_gelesen'] == 1)
  {
    array_push($fail, 'Sie müssen die AGB lesen und diesen zustimmen');
  }
  elseif (! check_emailaddr($_POST['email']))
  {
    array_push($fail, 'Die E-Mail-Adresse scheint nicht korrekt zu sein!');
  }
  if (empty($fail))
  {
    require_once('register.php');
    $customerno = create_customer($_POST);
    if ($customerno == NULL)
    {
      array_push($fail, 'Diese E-Mail-Adresse ist bereits in unserer Datenbank vorhanden!');
    }
    elseif (create_token($customerno))
    {
      require_once('inc/base.php');
      send_initial_customer_token($customerno);
      notify_admins_about_new_customer($customerno);
      logger("modules/register/index.php", "register", "token sent for customer »{$customerno}«");
      $success = true;
      #success_msg('Die angegebenen Daten wurden gespeichert, Sie sollten umgehend eine E-Mail erhalten.');
    }
  }
}


if ($success)
{
  output('<h3>Neues Konto eingerichtet</h3>
  <p>Wir bestätigen hiermit die Einrichtung eines Kundenkontos und bedanken uns für Ihr Vertrauen.</p>

  <h4>Was jetzt?</h4>
  <p>Sie erhalten jetzt von uns eine E-Mail an die soeben eingegebene E-Mail-Adresse (»'.$_POST['email'].'«). Beachten Sie bitte, dass manche E-Mail-Spamfilter die Zustellung um eine gewisse Zeit verzögern können. Sofern Sie nach ca. einer Stunde noch keine E-Mail erhalten haben, schreiben Sie bitte <a href="mailto:root@schokokeks.org">an die Administratoren.</a></p>

  <p>In der E-Mail finden Sie einen Link. Wenn Sie diesen aufrufen, dann erhalten Sie die Möglichkeit, ein Passwort zu setzen. Mit diesem Passwort und der Kundennummer <strong>'.(string) $customerno.'</strong> können Sie sich daraufhin an unserem Web-Interface anmelden.');
}
else
{
  output('<h3>Bei schokokeks.org registrieren</h3>
  <p>Hier können Sie sich bei schokokeks.org anmelden. Eine Anmeldung ist kostenlos und unverbindlich, erlaubt Ihnen aber, kostenpflichtige Dienste von schokokeks.org in Anspruch zu nehmen.</p>
  <p><strong>Gehen Sie daher sorgfältig mit den Anmeldedaten um!</strong></p>
  <p>Um Sie als Kunden identifizieren zu können, benötigen wir den Namen und die E-Mail-Adresse. Die eingegebenen Daten werden manuell bearbeitet und bei Spass-Eintragungen wird der Zugang gesperrt. Je nach dem, welche späteren Dienste Sie bei uns in Anspruch nehmen, kann es notwendig sein, dass Sie weitere Daten eingeben (z.B. Adresse bei Domainregistrierung).</p>
  
  <h4>Anmeldung</h4>
  <p>Um sich jetzt bei schokokeks.org anzumelden, müssen Sie hier zuerst Ihren Namen und Ihre E-Mail-Adresse eingeben.</p>');
  
  foreach ($fail as $f)
    output('<p class="warning"><b>Fehler:</b> '.$f.'</p>');
  
  /* FIXME:
   * Hier werden POST-Variablen benutzt, die es eventuell gar nicht gibt. Das erlaubt PHP zwar, ist aber nicht elegant.
   */

  output(html_form("register_index", "", "", '<p><span class="login_label">Anrede:</span>
  '.html_select('anrede', array('Herr' => 'Herr', 'Frau' => 'Frau', 'Firma' => 'Firma'), $_POST['anrede']).'
  <p><span class="login_label">Firma:</span> <input type="text" name="firma" size="30" value="'.$_POST['firma'].'" /></p>
  <p><span class="login_label">Vorname:</span> <input type="text" name="vorname" size="30" value="'.$_POST['vorname'].'" /></p>
  <p><span class="login_label">Nachname:</span> <input type="text" name="nachname" size="30" value="'.$_POST['nachname'].'" /></p>
  <p><span class="login_label">E-Mail-Adresse:</span> <input type="text" name="email" size="30" value="'.$_POST['email'].'" /></p>
  <p><span class="login_label">AGB:</span> <input type="checkbox" name="agb_gelesen" value="1"'.($_POST['agb_gelesen'] == 1 ? ' checked="checked"' : '').'" /> Ja, ich habe <a href="http://schokokeks.org/agb">die Allgemeinen Geschäftsbedingungen von schokokeks.org Webhosting</a> gelesen und erkläre mich damit einverstanden.</p>
  <p><span class="login_label">&nbsp;</span> <input type="submit" value="Zugang erstellen" />'));
  
}

?>
