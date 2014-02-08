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
require_once('inc/icons.php');

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);

title("System-Benutzeraccounts");


if (! customer_may_have_useraccounts())
{
  warning("Sie haben bisher keine Benutzeraccounts. Der erste (»Stamm-«)Account muss von einem Administrator angelegt werden.");
}
else
{
  $accounts = list_useraccounts();
  $shells = available_shells();
  output("<p>Folgende Benutzeraccounts haben Sie bisher:</p>");
  output("<table><tr><th>Benutzeraccount</th><th>Speicherplatz<sup>*</sup></th><th>Aktionen</th></tr>");
  foreach ($accounts as $acc)
  {
    $shell = $shells[$acc['shell']];
    $usedquota = get_used_quota($acc['uid']);
    $quota = array();
    foreach ($usedquota as $q)
    {
      $mailbar = '';
      $mailstring = '';
      $mailpercent = round(( $q['mailquota'] / $q["systemquota"]) * 100);
      $mailwidth = 2 * min($mailpercent, 100);

      if ($q["mailquota"] > 0) {
	$mailstring = "<br />(davon {$q["mailquota"]} MB für Postfächer reserviert)";
        $mailbar = "<div style=\"font-size: 1px; background-color: blue; height: 10px; width: {$mailwidth}px; margin: 0; padding: 0; float: left;\">&#160;</div>";
      }  

      $percent = round(( ($q["systemquota_used"]+$q["mailquota"]) / $q["systemquota"] ) * 100 );
      $color = ( $percent > 99 ? 'red' : ($percent > 80 ? "yellow" : "green" ));
      $width = 2 * min($percent, 100) - $mailwidth;
     
      $used_space = $q['systemquota_used'] + $q['mailquota'];
      $quota[] = "<p>Server <strong>{$q['server']}</strong><br />{$percent}%: {$used_space} MB von {$q['systemquota']} MB belegt{$mailstring}.</p> 
        <div style=\"margin: 0; padding: 0; width: 200px; border: 1px solid black;\">{$mailbar}<div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; margin-left: {$mailwidth}px; padding: 0;\">&#160;</div></div>";

    }
    $realname = $acc['name'] ? $acc['name'] : $_SESSION['customerinfo']['name'];
    $quotastring = implode('', $quota);
    output("<tr><td><p><strong>{$acc['username']}</strong> - {$realname}</p><p style=\"color: #555;\">Existiert seit {$acc['erstellungsdatum']}<br />Verwendete Shell: {$shell}</p></td>");
    output("<td>{$quotastring}</td>");
    output("<td>".internal_link('edit', other_icon('user_edit.png', 'Bearbeiten'), "uid={$acc['uid']}"));
    
    if (! customer_useraccount($acc['uid']))
    {
      output(" &#160; ".internal_link('pwchange', icon_pwchange('Passwort neu setzen'), "uid={$acc['uid']}"));
      #output(" &#160; ".internal_link('deluser', other_icon('user_delete.png', 'Benutzer löschen'), "uid={$acc['uid']}"));
    }
    output("</td></tr>\n");
  }
  output("</table><p><sup>*</sup>) Die Werte für den verbrauchten Speicherplatz werden periodisch eingelesen und hier erst verspätet angezeigt!</p>");
}


?>
