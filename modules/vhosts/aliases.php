<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

$title = "Aliasnamen für Subdomain bearbeiten";
$section = 'vhosts_vhosts';

require_role(ROLE_SYSTEMUSER);

$id = (int) $_GET['vhost'];

$vhost = get_vhost_details($id);
DEBUG($vhost);

$aliases = get_aliases($id);
DEBUG($aliases);

output("<h3>Aliasnamen für Subdomain bearbeiten</h3>");

$mainalias = (strstr($vhost['options'], 'aliaswww') ? '<br /><strong>www.'.$vhost['fqdn'].'</strong>' : '');

output("
  <table>
    <tr><th>Adresse</th><th>Verhalten</th><th>&nbsp;</th></tr>
    <tr><td><strong>{$vhost['fqdn']}</strong>{$mainalias}</td><td>Haupt-Adresse</td><td>&nbsp;</td></tr>
");

foreach ($aliases AS $alias) {
  $aliastype = 'Zusätzliche Adresse';
  if (strstr($alias['options'], 'forward')) {
    $aliastype = 'Umleitung auf Haupt-Adresse';
  }
  $formtoken = generate_form_token('aliases_toggle');
  $havewww = '<br />www.'.$alias['fqdn'].' &nbsp; ('.internal_link('aliasoptions.php', 'WWW-Alias entfernen', "alias={$alias['id']}&aliaswww=0&formtoken={$formtoken}").')';
  $nowww = '<br />'.internal_link('aliasoptions.php', 'Auch mit WWW', "alias={$alias['id']}&aliaswww=1&formtoken={$formtoken}");
  $wwwalias = (strstr($alias['options'], 'aliaswww') ? $havewww : $nowww);

  $to_forward = internal_link('aliasoptions.php', 'In Umleitung umwandeln', "alias={$alias['id']}&forward=1&formtoken={$formtoken}");
  $remove_forward = internal_link('aliasoptions.php', 'In zusätzliche Adresse umwandeln', "alias={$alias['id']}&forward=0&formtoken={$formtoken}");
  $typetoggle = (strstr($alias['options'], 'forward') ? $remove_forward : $to_forward);

    
  output("<tr>
    <td>{$alias['fqdn']}{$wwwalias}</td>
    <td>{$aliastype}<br />{$typetoggle}</td>
    <td>".internal_link('save.php', 'Aliasname löschen', "action=deletealias&alias={$alias['id']}")."</td></tr>
  ");
}

output('</table>');


output(html_form('vhosts_add_alias', 'save.php', 'action=addalias&vhost='.$vhost['id'], "
<div style=\"margin-top: 1em; padding: 1em;\">
<h4>Neuen Aliasnamen hinzufügen</h4>
<p>
  <input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" value=\"\" />
    <strong>.</strong>".domainselect()."<br />
  <input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" />
    <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label><br />
  Modus: <select name=\"options[]\">
    <option value=\"\">zusätzliche Adresse</option>
    <option value=\"forward\">Umleitung auf Haupt-Adresse</option>
  </select>
</p>
<p>
  <input type=\"submit\" value=\"Hinzufügen\" /></p>
</div>
"));


?>
