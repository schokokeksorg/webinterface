<?php
require_once('inc/base.php');
require_once('inc/icons.php');

require_once('useraccounts.php');

require_role(ROLE_SYSTEMUSER);

title("Ihr Benutzeraccount");
$section = "systemuser_account";


$shells = available_shells();
output("<p>Daten zu Ihrem Benutzeraccount:</p>");
$acc = get_account_details($_SESSION['userinfo']['uid'], $_SESSION['userinfo']['customerno']);
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
$customer = get_customer_info($_SESSION['userinfo']['customerno']);
$realname = $acc['name'] ? $acc['name'] : $customer['name'];
$quotastring = implode('', $quota);
output("<h5>Stammdaten</h5><div style=\"margin-left: 2em;\"><p>Benutzername: <strong>{$acc['username']}</strong></p><p>Name: {$realname}</p><p>Existiert seit {$acc['erstellungsdatum']}</p><p>Verwendete Shell: {$shell}</p>");
output("<p>".internal_link('edit', other_icon('user_edit.png', 'Bearbeiten').' Daten bearbeiten').'</p>');
output("</div>\n");
output("<h5>Speicherplatz</h5><div style=\"margin-left: 2em;\">{$quotastring}</div>");
    
output("<p><sup>*</sup>) Die Werte für den verbrauchten Speicherplatz werden periodisch eingelesen und hier erst verspätet angezeigt!</p>");


?>
