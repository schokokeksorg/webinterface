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

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

title("Aliasnamen für Subdomain bearbeiten");
$section = 'vhosts_vhosts';

require_role(ROLE_SYSTEMUSER);

$id = (int) $_GET['vhost'];

$vhost = get_vhost_details($id);
DEBUG($vhost);

$aliases = get_aliases($id);
DEBUG($aliases);

$mainalias = (strstr($vhost['options'], 'aliaswww') ? '<br /><strong>www.'.$vhost['fqdn'].'</strong>' : '');

$form = "
  <table>
    <tr><th>Adresse</th><th>Verhalten</th><th>&#160;</th></tr>
    <tr><td><strong>{$vhost['fqdn']}</strong>{$mainalias}</td><td>Haupt-Adresse</td><td>&#160;</td></tr>
";

foreach ($aliases AS $alias) {
  $aliastype = 'Zusätzliche Adresse';
  if (strstr($alias['options'], 'forward')) {
    $aliastype = 'Umleitung auf Haupt-Adresse';
  }
  $formtoken = generate_form_token('aliases_toggle');
  $havewww = '<br />www.'.$alias['fqdn'].' &#160; ('.internal_link('aliasoptions', 'WWW-Alias entfernen', "alias={$alias['id']}&aliaswww=0&formtoken={$formtoken}").')';
  $nowww = '<br />'.internal_link('aliasoptions', 'Auch mit WWW', "alias={$alias['id']}&aliaswww=1&formtoken={$formtoken}");
  $wwwalias = (strstr($alias['options'], 'aliaswww') ? $havewww : $nowww);

  $to_forward = internal_link('aliasoptions', 'In Umleitung umwandeln', "alias={$alias['id']}&forward=1&formtoken={$formtoken}");
  $remove_forward = internal_link('aliasoptions', 'In zusätzliche Adresse umwandeln', "alias={$alias['id']}&forward=0&formtoken={$formtoken}");
  $typetoggle = (strstr($alias['options'], 'forward') ? $remove_forward : $to_forward);

    
  $form .= "<tr>
    <td>{$alias['fqdn']}{$wwwalias}</td>
    <td>{$aliastype}<br />{$typetoggle}</td>
    <td>".internal_link('save', 'Aliasname löschen', "action=deletealias&alias={$alias['id']}")."</td></tr>
  ";
}

$form .= "
<tr>
  <td>
    <strong>Neuen Aliasnamen hinzufügen</strong><br />
    <input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" value=\"\" />
      <strong>.</strong>".domainselect()."<br />
    <input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" />
      <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label>
  </td>
  <td>
    <select name=\"options[]\">
      <option value=\"\">zusätzliche Adresse</option>
      <option value=\"forward\">Umleitung auf Haupt-Adresse</option>
    </select>
  </td>
  <td>
    <input type=\"submit\" value=\"Hinzufügen\" />
  </td>
</tr>
</table>";

output(html_form('vhosts_add_alias', 'save', 'action=addalias&vhost='.$vhost['id'], $form));
    
output("<p>
  ".internal_link("vhosts", "Zurück zur Übersicht")."
</p>");


?>
