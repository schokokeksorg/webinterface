<?php

require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

require_once('vmail.php');

$accounts = get_vmail_accounts();

output('<h3>E-Mail-Accounts</h3>
<p>Folgende E-Mail-Konten sind eingerichtet:</p>
<table style="margin-bottom: 1em;">
<tr><th>Adresse</th><th>...</th><th>&#160;</th></tr>
');

        foreach ($accounts as $account)
        {
            output('<tr>
            <td>'.internal_link('edit.php', $account['local'].'@'.$account['domainname'], 'id='.$account['id']).'</td>
            <td><a href="save.php?action=delete&amp;id='.$account['id'].'">löschen</a></td></tr>');
        }
        output('</table>
<p><a href="edit.php">Neuen Account anlegen</a></p>

');



?>
