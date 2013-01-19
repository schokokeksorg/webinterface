<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');


$dyndns = get_dyndns_accounts();

title("DynDNS-Accounts");
output("<p>Hier sehen Sie eine Übersicht über die angelegten DynDNS-Accounts.</p>");

output('<table><tr><th>Kürzel</th><th>Methode</th><th>aktuelle IP</th><th>letztes Update</th><th>&#160;</th></tr>
');

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
  output("<tr><td>".internal_link('dyndns_edit', $handle, "id={$entry['id']}")."</td><td>{$method}</td><td>{$entry['address']}</td><td>{$entry['lastchange']}</td><td>".internal_link('save', '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Account löschen" />', "id={$entry['id']}&type=dyndns&action=delete")."</td></tr>\n");
}
output('</table>');

addnew('dyndns_edit', 'Neuen DynDNS-Account anlegen');

?>
