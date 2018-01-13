<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('handles.php');
require_once('inc/debug.php');

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));

title("Adressen verwalten");


$handles = get_handles();
$kundenhandles = get_kundenhandles();

output('<p>Sie haben aktuell diese Adressen gespeichert:</p>
<table>
<tr><th>#</th><th>Name</th><th>Adresse</th><th>E-Mail</th><th>Verwendung</th><th>Aktionen</th></tr>
');
foreach ($handles as $id => $handle) {
    $adresse = nl2br($handle['address']."\n".$handle['country'].'-'.$handle['zip'].' '.$handle['city']);
    $usage = '';
    if ($id == $kundenhandles['kunde']) {
        $usage .= 'Stamm-Adresse';
    }
    if ($id == $kundenhandles['extern']) {
        $usage .= 'Ersatz-Adresse';
    }
    if ($id == $kundenhandles['rechnung']) {
        $usage .= 'Rechnungs-Adresse';
    }
    $email = $handle['email'];
    $new_email = update_pending($id);
    if ($new_email) {
        $email = "<strike>$email</strike><br/>".$new_email.footnote('Die E-Mail-Adresse wurde noch nicht bestätigt');
    }
    output("<tr><td>{$handle['id']}</td><td><strong>{$handle['name']}</strong></td><td>$adresse</td><td>$email</td><td>$usage</td><td>...</td></tr>");
}
output('</table>');
output("<br />");



?>
