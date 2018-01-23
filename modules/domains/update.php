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

require_once('inc/debug.php');
require_once('inc/icons.php');

require_once('class/domain.php');
require_once('domains.php');
require_once('domainapi.php');

require_role(ROLE_CUSTOMER);
use_module('contacts');
require_once('contacts.php');

$dom = NULL;
if (isset($_REQUEST['id'])) {
    api_update_domain($_REQUEST['id']);
    $dom = new Domain( (int) $_REQUEST['id']);
    if ($dom->provider == 'external' || $dom->provider != 'terions') {
        system_failure("<p>Diese Domain ist extern registriert!</p>");
    }
    $_SESSION['domains_update_domainname'] = $dom->fqdn;
    $_SESSION['domains_update_owner'] = $dom->owner;
    $_SESSION['domains_update_admin_c'] = $dom->admin_c;
} else {
    $dom = new Domain($_SESSION['domains_update_domainname']);
}
if (!$dom) {
    system_failure("Keine Domain gewählt!");
}
if (!update_possible($dom->id)) {
    system_failure("Diese Domain verwendet eine unübliche Endung. Daher kann der Inhaber nicht auf diesem Weg verändert werden. Bitte kontaktieren Sie den Support.");
}

if ($_SESSION['domains_update_admin_c'] == $dom->admin_c && 
    $_SESSION['domains_update_owner'] != $dom->owner && 
    (!isset($_SESSION['domains_update_detach']) || $_SESSION['domains_update_detach'] == 0)) {
    // Wenn der Owner geändert wurde, der Admin aber nicht und das detach-Flag 
    // nicht gesetzt ist, dann wird der Admin gleich dem Owner gesetzt
    $_SESSION['domains_update_admin_c'] = $_SESSION['domains_update_owner'];
}

if (isset($_GET['admin_c']) && $_GET['admin_c'] == 'none') {
    $_SESSION['domains_update_admin_c'] = $_SESSION['domains_update_owner'];
    unset($_SESSION['domains_update_detach']);
}



title("Änderung der Domain {$dom->fqdn}");
$section = 'domains_domains';
output('<p>Legen Sie hier einen neuen Inhaber für diese Domain fest.</p>');


$owner = get_contact($_SESSION['domains_update_owner']);
$admin_c = get_contact($_SESSION['domains_update_admin_c']);
$function = 'Inhaber';
if ($owner['id'] == $admin_c['id']) {
    $function .= ' und Verwalter';
}
$cssclass = '';
if ($owner['id'] != $dom->owner) {
    $cssclass = 'modified';
}
output('<p><strong>'.$function.':</strong></p>'.display_contact($owner, '', $cssclass));
addnew('choose', 'Neuen Inhaber wählen', "type=owner");
if ($owner['id'] != $admin_c['id']) {
    $cssclass = '';
    if ($admin_c['id'] != $dom->admin_c) {
        $cssclass = 'modified';
    }
    output('<p><strong>Verwalter:</strong></p>'.display_contact($admin_c, '', $cssclass));
    addnew('choose', 'Neuen Inhaber wählen', "type=admin_c");
    output('<p class="delete">'.internal_link('update', 'Keinen separaten Verwalter festlegen', 'admin_c=none').'</p>');
} else {
    addnew('choose', 'Einen separaten Verwalter wählen', "type=admin_c&detach=1");
}


if ($owner['id'] != $dom->owner || $admin_c['id'] != $dom->admin_c) {
    if (isset($_GET['error']) && $_GET['error'] == '1') {
        input_error('Sie müssen der Übertragung explizit zustimmen!');
    }
    $form = '<p>Es sind Änderungen vorgenommen worden, die noch nicht gespeichert wurden</p>';
    $form .= '<p><input type="checkbox" name="accept" value="1" id="accept"><label for="accept"> Ich bestätige, dass ich die nachfolgenden Hinweise zur Kenntnis genommen habe.</p>
    <p>Mit Speichern dieser Änderungen führen Sie möglicherweise einen Inhaberwechsel bei der Domain '.$dom->fqdn.' aus. Inhaberwechsel sind bei einigen Domainendungen (z.B. com/net/org) zustimmungspflichtig vom alten und vom neuen Inhaber. Die Registrierungsstelle kann nach eigenem Ermessen diese Zustimmung per separater E-Mail einfordern. Wird diese Zustimmung nicht oder verspätet erteilt, kann eine Domain gesperrt werden. Dieser Vorgang wird nicht von '.config('company_name').' kontrolliert.</p>
    <p>Sie sind ferner darüber informiert, dass die Adresse des Domaininhabers öffentlich abrufbar ist.</p>';
    $form .= '<p><input type="submit" name="sumbit" value="Änderungen speichern und Domaininhaber ändern"></p>';
    output(html_form('domains_update', 'update_save', "id=".$dom->id, $form));
} 

output('<p>'.internal_link('domains', 'Ohne Änderungen zurück').'</p>');
