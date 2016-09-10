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
require_once('inc/icons.php');

require_once('vhosts.php');

title("Subdomains");
$error = '';

require_role(ROLE_SYSTEMUSER);

global $prefix;

output("<p>Mit dieser Funktion legen Sie fest, welche Domains und Subdomains als Webserver-Ressource verfügbar sein sollen und welches Verzeichnis die Dateien enthalten soll.</p>
<p>Änderungen an Ihren Einstellungen werden im 5-Minuten-Takt auf dem Server übernommen.</p>
");


$domain = NULL;
if (isset($_REQUEST['domain']) && $_REQUEST['domain'] != '') {
  $domain = $_REQUEST['domain'];
  output('<p class="warning"><strong>Filter aktiv!</strong> Momentan werden nur Einstellungen für die Domain <strong>'.filter_input_general($domain).'</strong> angezeigt. Klicken Sie '.internal_link('', 'hier', 'domain=').' um alle Einstellungen anzuzeigen.</p>');
}
$vhosts = list_vhosts($domain);
$traffic_sum = 0;
$letsencrypt = false;
foreach ($vhosts as $vh) {
  if (strstr($vh['options'], 'letsencrypt')) {
    $letsencrypt = true;
  }
}

if (count($vhosts) > 0)
{
  /*
  if ($letsencrypt) {
    warning("Sie haben für eine oder mehrere Domains die Nutzung eines Let's-Encrypt-Zertifikats aktiviert. Wir haben diese Funktion nach allgemeiner Verfügbarkeit von Let's Encrypt umgehend freigeschaltet und sind mit der ersten Erfahrungen sehr zufrieden. Dennoch befindet sich Let's Encrypt momentan im Beta-Betrieb (d.h. Testbetrieb). Störungen sind daher nicht auszuschließen.");
    warning("Die Bereitstellung eines Zertifikats von Let's Encrypt kann momentan bis zu 15 Minuten in Anspruch nehmen.");
  }
  */
  if (count($vhosts) > 10) {
    addnew('edit', 'Neue Domain bzw. Subdomain einrichten');
  }
  output("<table><tr><th>(Sub-)Domain</th><th></th><th>Zusätzliche Alias-Namen</th><th>Protokoll</th><th>SSL</th><th>Traffic<sup>*</sup></th><th>PHP</th><th>Lokaler Pfad<sup>**</sup></th></tr>\n");

  $even = True;

  foreach ($vhosts as $vhost)
  {
    $even = ! $even;
    $fqdn = $vhost['fqdn'];
    $class = 'odd';
    if ($even) $class = 'even';
    $proto = 'http';
    if ($vhost['ssl'] == 'https' || $vhost['ssl'] == 'forward') {
      $proto = 'https';
    }
    $linkuri = $vhost['fqdn'];
    if (strstr($vhost['options'], 'aliaswww')) {
      $linkuri = "www.".$vhost['fqdn'];
    }
    output("<tr class=\"{$class}\"><td>".internal_link('edit', $fqdn, "vhost={$vhost['id']}", 'title="Einstellungen bearbeiten"')."</td><td><a href=\"{$proto}://{$linkuri}\">".other_icon('world_link.png', 'Website aufrufen')."</a> ".internal_link('save', icon_delete("»{$vhost['fqdn']}« löschen"), 'action=delete&vhost='.$vhost['id'] )."</td><td>");
    $aliases = get_all_aliases($vhost);
    $tmp = '';
    if (count($aliases) > 0)
    {
      foreach ($aliases as $alias)
      {
        $tmp .= $alias['fqdn'].'<br />';
      }
    } else {
      $tmp = '<em>- keine -</em>';
    }
    output(internal_link('aliases', $tmp, 'vhost='.$vhost['id'], 'title="Aliase verwalten"'));
    output('</td>');
    $logfiles = 'Kein Log';
    if ($vhost['logtype'] == 'default')
      $logfiles = 'Zugriffe ';
    elseif ($vhost['logtype'] == 'anonymous')
      $logfiles = 'Anonym';
    if ($vhost['errorlog'] == 1)
    {
      if ($vhost['logtype'] == NULL)
        $logfiles = 'Fehler';
      else
        $logfiles .= ' + Fehler';
    }
    $stats = $vhost['stats'] ? internal_link("showstats", other_icon("chart_bar.png", "Statistiken anzeigen"), "vhost={$vhost['id']}").' ' : '';
    output("<td>{$stats}{$logfiles}</td>");
  
    if ($vhost['ssl'] == 'http')
    {
      output("<td>".icon_disabled('SSL ausgeschaltet')."</td>");
    }
    elseif (strstr($vhost['options'], "letsencrypt") && $vhost['cert'])
    {
      $forward = '';
      if ($vhost['ssl'] == 'forward') {
        $forward = " ".other_icon("refresh.png", 'Auf SSL umleiten');
      } else {
        $forward = " ".other_icon("warning.png", 'Ungeschützter Aufruf weiterhin möglich');
      }
      output("<td>".other_icon("letsencrypt.png", "Automatische Zertifikatsverwaltung mit Let's Encrypt").$forward."</td>");
    }
    elseif ($vhost['cert'])
    {
      output("<td>".other_icon("secure.png", "SSL mit eigenem Zertifikat")."</td>");
    }
    elseif (strstr($vhost['options'], "letsencrypt")) {
      // Letsencrypt gewählt aber noch nicht aktiv
      $message = "Let's Encrypt-Zertifikat ist noch nicht bereit";
      output("<td>".other_icon("letsencrypt.png", $message).icon_warning($message)."</td>");
    }
    else
    {
      output("<td>".icon_enabled('SSL eingeschaltet')."</td>");
    }

    $traffic = traffic_month($vhost['id']);
    $traffic_sum += (int) $traffic;
    $traffic_string = $traffic.' MB';
    if ($traffic > 1024) {
      $traffic_string = round($traffic / 1024, 2).' GB';
    }
    if ($traffic === NULL) {
      $traffic_string = '--';
    }
    output("<td style=\"text-align: right;\">{$traffic_string}</td>");

    if ($vhost['is_webapp'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> Vorinstallierte Webanwendung</em></td>');
    }
    elseif ($vhost['is_dav'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> WebDAV</em></td>');
    }
    elseif ($vhost['is_svn'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> Subversion-Server</em></td>');
    }
    else {
      $php = $vhost['php'];
      switch ($php)
      {
        case NULL:
          $php = icon_disabled('PHP ausgeschaltet');
          break;
        case 'php56':
          $php = icon_enabled_phpxx('PHP in Version 5.6 eingeschaltet', 5, 6);
          break;
        case 'php70':
        case 'fpm70':
          $php = icon_enabled_phpxx('PHP in Version 7.0 eingeschaltet', 7, 0);
          break;
        case 'php71':
        case 'fpm71':
          $php = icon_enabled_phpxx('PHP in Version 7.1 eingeschaltet', 7, 1);
          break;
      }
      output("<td>{$php}</td>");
      if ($vhost['docroot_is_default'] == 1)
        output("<td><span style=\"color:#777;\">{$vhost['docroot']}</span></td>");
      else
        output("<td><strong>{$vhost['docroot']}</strong></td>");
    }
    output("</tr>\n");
  }
  output('</table>');
  if ($traffic_sum > 0) {
    $traffic_string = $traffic_sum.' MB';
    if ($traffic_sum > 1024) {
      $traffic_string = round($traffic_sum / 1024, 2).' GB';
    }
    output('<p><strong>Traffic insgesamt: '.$traffic_string.'</strong> in den letzten 30 Tagen</p>');
  }
  output('<p style="font-size: 90%;"><sup>*</sup>)&#160;Dieser Wert stellt den Datenverkehr dieser Website für die letzten 30 Tage dar.</p>');
  output('<p style="font-size: 90%;"><sup>**</sup>)&#160;schwach geschriebene Pfadangaben bezeichnen die Standardeinstellung. Ist ein Pfad fett dargestellt, so haben Sie einen davon abweichenden Wert eingegeben.</p>');
}
else // keine VHosts vorhanden
{
  output("<p><strong><em>Bisher haben Sie keine Domain bzw. Subdomain eingerichtet.</em></strong></p>");
}

addnew('edit', 'Neue Domain bzw. Subdomain einrichten');

?>
