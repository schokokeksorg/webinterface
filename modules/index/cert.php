<?php

require_once('inc/base.php');
require_once('x509.php');

require_role(ROLE_SYSTEMUSER);

$title = "Client-Zertifikate";
output('<h3>Anmeldung über Client-Zertifikat</h3>

<p>Sie können Sie an diesem Webinterface wahlweise auch über ein SSL-Client-Zertifikat anmelden. Dazu muss das gewünschte Zertifikat <em>vorher</em> in Ihrem Browser installiert werden und kann dann hier hinzugefügt werden.</p>
<p>Wenn Sie ein Zertifikat mit der entsprechenden Funktion unten auf dieser Seite hinzufügen, wird Sie Ihr Browser fragen, welches Zertifikat verwendet werden soll. Sollte Ihr Browser nicht fragen, ist entweder kein Zertifikat im Browser installiert oder Sie haben Ihren Browser auf <em>Niemals fragen</em> (o.Ä.) eingestellt.</p>
');



if (isset($_GET['clear']))
{
  unset($_SESSION['clientcert_cert']);
  unset($_SESSION['clientcert_dn']);
  unset($_SESSION['clientcert_issuer']);
}

if (isset($_SESSION['clientcert_cert']))
{
  // FIXME: Es gibt keine Duplikaterkennung.
  global $menu;
  output('<div style="margin: 1em; padding: 1em; border: 2px solid green;">');
  output('<p>Es wurde folgendes Client-Zertifikat von Ihrem Browser gesendet:</p>
<div style="margin-left: 2em;"><strong>DN:</strong> '.filter_input_general($_SESSION['clientcert_dn']).'<br />
<strong>Aussteller-DN:</strong> '.filter_input_general($_SESSION['clientcert_issuer']).'</div>
<p>Soll dieses Zertifikat für den Zugang zum Benutzerkonto <strong>'.$_SESSION['userinfo']['username'].'</strong> verwendet werden?</p>');
  output(html_form('clientcert_add', 'certsave.php', 'action=new', '<p><input type="submit" name="submit" value="Ja, dieses Zertifikat einrichten" /> &#160; '.internal_link('cert', 'Nein', 'clear').'</p>'));
  output('</div>');
}


$certs = get_certs_by_username($_SESSION['userinfo']['username']);
if ($certs != NULL) {
  output('<p>Sie haben bereits Zertifikate für den Zugang eingerichtet.</p>
  <ul>');
  foreach ($certs AS $cert) {
   	output('<li>'.$cert['dn'].'<br /><em>ausgestellt von </em>'.$cert['issuer']);
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
output('
<div class="error"><strong>Hinweis:</strong><br />
Aufgrund einer aktuellen Sicherheits-Lücke wurde in vielen Browsern die so genannte TLS-Renegotiation abgeschaltet. Ohne diese Funktion ist ein Login über Client-Zertifikate technisch nicht möglich.
Mit einigen aktuellen Browser-Versionen ist der Login mittels Client-Zertifikat momentan nicht möglich.

<a href="http://groups.google.com/group/mozilla.dev.tech.crypto/browse_thread/thread/42c17928ea4fc374">Informationen und Lösungsmöglichkeit zum Mozilla-Firefox</a>');




