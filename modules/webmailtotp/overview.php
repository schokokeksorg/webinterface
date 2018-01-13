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

require_once('totp.php');
require_role(ROLE_SYSTEMUSER);

title("Zwei-Faktor-Anmeldung am Webmailer");

output('<p>Sie können bei '.config('company_name').' den Zugang zum Webmailer mit einem Zwei-Faktor-Prozess mit abweichendem Passwort schützen.</p>
<p>Dieses System schützt Sie vor mitgelesenen Tastatureingaben in nicht vertrauenswürdiger Umgebung z.B. in einem Internet-Café.</p>
<p>Beim Zwei-Faktor-Prozess müssen Sie zum Login ein festes Webmail-Passwort und zusätzlich ein variabler Code, den beispielsweise Ihr Smartphone erzeugen kann, eingeben. Da sich dieser Code alle 30 Sekunden ändert, kann ein Angreifer sich nicht später mit einem abgehörten Passwort noch einmal anmelden. Zum Erzeugen des Einmal-Codes benötigen Sie ein Gerät, das <strong>TOTP-Einmalcodes nach RFC 6238</strong> erzeugt. Beispiele dafür sind <a href="https://code.google.com/p/google-authenticator/">Google-Authenticator</a> oder <a href="http://f-droid.org/repository/browse/?fdfilter=motp&fdid=org.cry.otp&fdpage=1">mOTP</a>. Meist ist dies ein Smartphone mit einer entsprechenden App.</p>
<p><strong>Beachten Sie:</strong> Die Zwei-Faktor-Anmeldung funktioniert nur für Webmail, beim Login via IMAP wird weiterhin nur das Passwort Ihres Postfachs benötigt. Damit dieses Passwort von einem Angreifer nicht mitgelesen werden kann, müssen Sie zur Zwei-Faktor-Anmeldung unbedingt ein separates Passwort festlegen.</p>
<h3>Fügen Sie Zwei-Faktor-Anmeldung zu Ihren bestehenden Postfächern hinzu</h3>
');


require_once('modules/email/include/hasaccount.php');
require_once('modules/email/include/vmail.php');

if (! (user_has_accounts() || count(get_vmail_accounts())>0)) {
  
  output('<p><em>Bisher haben Sie kein Postfach. Bitte erstellen sie zunächst ein Postfach.</em></p>');
}
else
{

/* VMAIL */

$domains = get_vmail_domains();
$vmail_accounts = get_vmail_accounts();

$sorted_by_domains = array();
foreach ($vmail_accounts AS $account)
{
  if ($account['password'] == '') {
    continue;
  }
  if (array_key_exists($account['domain'], $sorted_by_domains))
    array_push($sorted_by_domains[$account['domain']], $account);
  else
    $sorted_by_domains[$account['domain']] = array($account);
}

DEBUG($sorted_by_domains);

if (count($sorted_by_domains) > 0)
{
  foreach ($sorted_by_domains as $accounts_on_domain)
  {
      if (count($sorted_by_domains) > 2) {
	      output('<h4>'.$accounts_on_domain[0]['domainname'].'</h4>');
      }

	    foreach ($accounts_on_domain AS $this_account)
	    {
        $username = $this_account['local'].'@'.$this_account['domainname'];  
        output('<div style="margin-left: 2em;"><p style="margin-left: -2em;"><strong>'.$username.'</strong></p>');
        $id = account_has_totp($username);
        if ($id) {
          output(addnew('delete', 'Zwei-Faktor-Anmeldung für dieses Postfach abschalten', 'id='.$id, 'style="background-image: url('.$prefix.'images/delete.png); color: red;"'));
        } else {
          output(addnew('setup', 'Zwei-Faktor-Anmeldung für dieses Postfach aktivieren', 'username='.urlencode($username)));
        }
        output('</div>');
	    }
  }
}

/* Mailaccounts */

require_once('modules/email/include/mailaccounts.php');

$accounts = mailaccounts($_SESSION['userinfo']['uid']);

if (count($accounts) > 0) {

if (count($sorted_by_domains) > 0) {
  output('<h3>IMAP/POP3-Accounts</h3>');
}


foreach ($accounts AS $acc) {
  if ($acc['mailbox']) {
    output('<div style="margin-left: 2em;"><p style="margin-left: -2em;"><strong>'.$acc['account'].'</strong></p>');
    $username = $acc['account'];
    $id = account_has_totp($username);
    if ($id) {
      output(addnew('delete', 'Zwei-Faktor-Anmeldung für dieses Postfach abschalten', 'id='.$id, 'style="background-image: url('.$prefix.'images/delete.png); color: red;"'));
    } else {
      output(addnew('setup', 'Zwei-Faktor-Anmeldung für dieses Postfach aktivieren', 'username='.urlencode($username)));
    }
    output('</div>');
  }
}


}

}

?>
