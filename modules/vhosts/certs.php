<?php

require_once("certs.php");
require_role(ROLE_SYSTEMUSER);

title("SSL-Zertifikate");

output('<p>Bei schokokeks.org können Sie Ihre eigenen SSL-Zertifikate nutzen. Wir verwenden dafür (wenn nicht anders vereinbart) die <a href="https://wiki.schokokeks.org/SNI">SNI-Technik</a>.
Beim Anlegen von Webserver-Konfigurationen können Sie dann eines Ihrer Zertifikate für jede Konfiguration auswählen.</p>

<h4>Ihre bisher vorhandenen Zertifikate</h4>
');

$certs = user_certs();

if (count($certs) > 0)
{
  output("<table><tr><th>Name/Details</th><th>CommonName</th><th>Gültig ab</th><th>Gültig bis</th><th>&#160;</th></tr>");
  foreach ($certs as $c)
  {
    output("<tr><td>".internal_link('showcert', $c['subject'], "mode=cert&id={$c['id']}")."</td><td>{$c['cn']}</td><td>{$c['valid_from']}</td><td>{$c['valid_until']}</td><td>".internal_link('refreshcert', '<img src="'.$prefix.'images/refresh.png" title="Neue Version des Zertifikats einspielen" />', 'id='.$c['id'])." &#160; ".internal_link('savecert', '<img src="'.$prefix.'images/delete.png" />', 'action=delete&id='.$c['id'])."</td></tr>");
  } 
  output("</table>");
}
else
{
  output('<p><em>Bisher haben Sie keine Zertifikate eingetragen</em></p>');
}

addnew('newcert', 'Neues Zertifikat eintragen');

output('<h3>offene CSRs</h3>');

$csr = user_csr();
if (count($csr) > 0)
{
  output("<table><tr><th>Host-/Domainname</th><th>Bitlänge</th><th>Erzeugt am</th><th>&#160;</th></tr>");
  foreach ($csr AS $c)
  {
    output("<tr><td>".internal_link('showcert', $c['hostname'], 'mode=csr&id='.$c['id'])."</td><td>{$c['bits']}</td><td>{$c['created']}</td><td>".internal_link('savecert', '<img src="'.$prefix.'images/delete.png" />', 'action=deletecsr&id='.$c['id'])." &#160; ".internal_link('certfromcsr', '<img src="'.$prefix.'images/ok.png" alt="Zertifikat hinzufügen" title="Zertifikat hinzufügen" />', "id={$c['id']}")."</td></tr>");
  }
  output("</table>");
}
else
{
  output('<p><em>Es gibt keine offenen CSRs</em></p>');
}


output('
<p>Wenn Sie ein einfaches Zertifikat benötigen, können Sie mit Hilfe dieser Funktion einen CSR (»certificate signing request«) 
erstellen, mit dem Sie Ihr endgültiges Zertifikat beantragen können.</p>');

addnew('newcsr', 'Neuen CSR erzeugen');









