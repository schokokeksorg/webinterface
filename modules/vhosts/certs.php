<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("certs.php");
require_role(ROLE_SYSTEMUSER);

title("Zertifikate");

output('<p>Bei '.config('company_name').' können Sie Ihre eigenen Zertifikate nutzen. Wir verwenden dafür (wenn nicht anders vereinbart) die SNI-Technik.
Beim Anlegen von Webserver-Konfigurationen können Sie dann eines Ihrer Zertifikate für jede Konfiguration auswählen.</p>

<h4>Ihre bisher vorhandenen Zertifikate</h4>
');

$certs = user_certs();

if (count($certs) > 0) {
    output("<table><tr><th>Name/Details</th><th>CommonName</th><th>Gültig ab</th><th>Gültig bis</th><th>&#160;</th></tr>");
    foreach ($certs as $c) {
        $style = "";
        if ($c['valid_until'] <= date('Y-m-d')) {
            $style = ' style="background-color: #f88;" ';
        } elseif ($c['valid_until'] <= date('Y-m-d', time() + (30 * 24 * 3600)) && !cert_is_letsencrypt($c['id'])) {
            $style = ' style="background-color: #ff8;" ';
        }
        output("<tr><td{$style}>".internal_link('showcert', filter_output_html($c['subject']), "mode=cert&id={$c['id']}")."</td><td{$style}>".filter_output_html($c['cn'])."</td><td{$style}>{$c['valid_from']}</td><td{$style}>{$c['valid_until']}</td><td>".internal_link('newcert', '<img src="'.$prefix.'images/refresh.png" title="Neue Version des Zertifikats einspielen" />', 'replace='.$c['id'])." &#160; ".internal_link('savecert', '<img src="'.$prefix.'images/delete.png" />', 'action=delete&id='.$c['id'])."</td></tr>");
    }
    output("</table>");
} else {
    output('<p><em>Bisher haben Sie keine Zertifikate eingetragen</em></p>');
}

addnew('newcert', 'Neues Zertifikat erzeugen / eintragen');



$csr = user_csr();
if (count($csr) > 0) {
    output('<h3>offene CSRs</h3>');
    output("<table><tr><th>Host-/Domainname</th><th>Bitlänge</th><th>Erzeugt am</th><th>&#160;</th></tr>");
    foreach ($csr as $c) {
        output("<tr><td>".internal_link('showcert', $c['hostname'], 'mode=csr&id='.$c['id'])."</td><td>{$c['bits']}</td><td>{$c['created']}</td><td>".internal_link('savecert', '<img src="'.$prefix.'images/delete.png" />', 'action=deletecsr&id='.$c['id'])." &#160; ".internal_link('certfromcsr', '<img src="'.$prefix.'images/ok.png" alt="Zertifikat hinzufügen" title="Zertifikat hinzufügen" />', "id={$c['id']}")."</td></tr>");
    }
    output("</table>");
}
