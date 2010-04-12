<?php
require_once('inc/base.php');
require_once('inc/icons.php');

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);

$title = "System-Benutzeraccounts";


output("<h3>System-Benutzeraccounts</h3>");

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
      $percent = round(( $q["used"] / $q["quota"] ) * 100 );
      $color = ( $percent > 99 ? 'red' : ($percent > 80 ? "yellow" : "green" ));
      $width = 2 * min($percent, 100);
      $quota[] = "<p>Server <strong>{$q['server']}</strong><br />{$percent}%: {$q['used']} MB von {$q['quota']} MB belegt.</p> 
        <div style=\"margin: 0; padding: 0; width: 200px; border: 1px solid black;\"><div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; padding: 0;\">&#160;</div></div>";

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
