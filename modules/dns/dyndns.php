<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');


$dyndns = get_dyndns_accounts();

$output .= '<h3>DynDNS-Accounts</h3>
<p>Hier sehen Sie eine Übersicht über die angelegten DynDNS-Accounts.</p>';

$output .= '<table><tr><th>Kürzel</th><th>Methode</th><th>aktuelle IP</th><th>letztes Update</th><th>&#160;</th></tr>
';

foreach ($dyndns AS $entry) {
  $handle = $entry['handle'];
  if (!$handle)
    $handle = '<em>undefiniert</em>';
  $method = '';
  if ($entry['sshkey'])
    if ($entry['password'])
      $method = 'SSH, HTTP';
    else
      $method = 'SSH';
  else
    if ($entry['password'])
      $method = 'HTTP';
    else
      $method = '<em>keine</em>';
  $output .= "<tr><td>".internal_link("dyndns_edit.php", $handle, "id={$entry['id']}")."</td><td>{$method}</td><td>{$entry['address']}</td><td>{$entry['lastchange']}</td><td>".internal_link("save.php", '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Account löschen" />', "id={$entry['id']}&type=dyndns&action=delete")."</td></tr>\n";
}
$output .= '</table><br />

<p>'.internal_link('dyndns_edit.php', 'Neuen DynDNS-Account anlegen').'</p>';

?>
