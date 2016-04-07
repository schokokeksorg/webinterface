<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('x509.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_SUBUSER, ROLE_VMAIL_ACCOUNT));

title('Anmeldung über Client-Zertifikat');
output('<p>Sie können Sie an diesem Webinterface wahlweise auch über ein SSL-Client-Zertifikat anmelden. Dazu muss das gewünschte Zertifikat <em>vorher</em> in Ihrem Browser installiert werden und kann dann hier hinzugefügt werden.</p>
<p>Wenn Sie ein Zertifikat mit der entsprechenden Funktion unten auf dieser Seite hinzufügen, wird Sie Ihr Browser fragen, welches Zertifikat verwendet werden soll. Sollte Ihr Browser nicht fragen, ist entweder kein Zertifikat im Browser installiert oder Sie haben Ihren Browser auf <em>Niemals fragen</em> (o.Ä.) eingestellt.</p>
');



if (isset($_GET['clear']))
{
  unset($_SESSION['clientcert_cert']);
  unset($_SESSION['clientcert_dn']);
  unset($_SESSION['clientcert_issuer']);
  unset($_SESSION['clientcert_serial']);
}

$username = NULL;
if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  $username = $_SESSION['userinfo']['username'];
  if (isset($_SESSION['subuser'])) {
    $username = $_SESSION['subuser'];
  }
} elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
  $username = $_SESSION['mailaccount'];
}

if (isset($_SESSION['clientcert_cert']))
{
  // FIXME: Es gibt keine Duplikaterkennung.
  global $menu;
  output('<div style="margin: 1em; padding: 1em; border: 2px solid green;">');
  output('<p>Es wurde folgendes Client-Zertifikat von Ihrem Browser gesendet:</p>
<div style="margin-left: 2em;"><strong>DN:</strong> '.filter_input_general($_SESSION['clientcert_dn']).'<br />
<strong>Aussteller-DN:</strong> '.filter_input_general($_SESSION['clientcert_issuer']).'<br />
<strong>Seriennummer:</strong> '.filter_input_general($_SESSION['clientcert_serial']).'</div>
<p>Soll dieses Zertifikat für den Zugang für <strong>'.$username.'</strong> verwendet werden?</p>');
  output(html_form('clientcert_add', 'certsave.php', 'action=new', '<p><input type="submit" name="submit" value="Ja, dieses Zertifikat einrichten" /> &#160; '.internal_link('cert', 'Nein', 'clear').'</p>'));
  output('</div>');
}


DEBUG($username);
$certs = get_certs_by_username($username);
if ($certs != NULL) {
  output('<p>Sie haben bereits Zertifikate für den Zugang eingerichtet.</p>
  <ul>');
  foreach ($certs AS $cert) {
   	output('<li>'.$cert['dn'].' / Seriennummer '.$cert['serial'].'<br /><em>ausgestellt von </em>'.$cert['issuer']);
    output('<br />'.internal_link('certsave', 'Dieses Zertifikat löschen', 'action=delete&id='.$cert['id']));
    output('</li>');
  }
  output('</ul>');
}
else
{
  output('<p><em>Bisher sind keine Zertifikate für Ihren Zugang eingerichtet</em></p>');
}

$backurl = 'go/index/cert';

addnew('../../certlogin/index.php', 'Neues Client-Zertifikat hinzufügen', 'record&backto='.$backurl);



