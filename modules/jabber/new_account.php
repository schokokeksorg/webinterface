<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
title("Neues Jabber-Konto erstellen");

$jabberdomains = get_jabberable_domains();

DEBUG($jabberdomains);

$options = '';
foreach ($jabberdomains as $dom)
{
  $options .= '<option value="'.$dom->id.'">'.$dom->fqdn.'</option>'."\n";
}


output('<p>Erstellen Sie hier ein neues Jabber-Konto. Ihre Änderungen werden nach ca. 10 Minuten automatisch in das System übertragen. Accounts funktionieren also nicht unmittelbar nach dem Anlegen.</p>

'.html_form('jabber_new_account', 'save', 'action=new', '
<table>
<tr><td>Account-Name:</td><td><input type="text" name="local" value="" />&#160;@&#160;<select name="domain" size="1">
'.$options.'
</select></td></tr>
<tr><td>Passwort:</td><td><input type="password" name="password" value="" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
'));


?>
