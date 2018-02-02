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

require_once('inc/security.php');
require_once('inc/icons.php');
require_once('class/domain.php');

require_once('domains.php');
require_once('domainapi.php');
require_role(ROLE_CUSTOMER);
use_module('contacts');
require_once('contacts.php');


$dom = NULL;
if (isset($_REQUEST['domain'])) {
    $domain = $_REQUEST['domain'];
    if (strpos($domain, ' ') !== false) {
        system_failure('Leerzeichen sind in Domainnamen nicht erlaubt.');
    }
    $dom = new Domain();
    if ($dom->loadByName($domain) === false) {
        // Eintragen mit DNS und Mail
        $id = insert_domain_external($domain, true, true);
        $dom->loadByName($domain);
    } 
    $dom->ensure_customerdomain();
    if ($dom->provider == 'terions') {
        system_failure("Diese Domain ist bereits auf Ihr Kundenkonto registriert.");
    }
    
    // An diesem Punkt ist die Domain eingetragen als extern und ggf. mit Mailserver-Lock
    // Bei der Reg-Bestätigung wird das Lock entfernt und die Daten entsprechend gesetzt, inklusive Preise.

    $_SESSION['domains_domainreg_owner'] = NULL;
    $_SESSION['domains_domainreg_admin_c'] = NULL;
    $_SESSION['domains_domainreg_domainname'] = $domain;
    $_SESSION['domains_domainreg_detach'] = 0;
    
} elseif (isset($_SESSION['domains_domainreg_domainname'])) {
    $domain = $_SESSION['domains_domainreg_domainname'];
    $dom = new Domain($domain);
    $dom->ensure_customerdomain();
}

if (!$dom) {
    system_failure("Keine Domain");
}

$mode=NULL;

$avail = api_domain_available($dom->fqdn);
if ($avail == 'available') {
    set_domain_prereg($dom->id);
    $mode = 'reg';
    title("Domain registrieren");
} elseif ($avail == 'registered' || $avail == 'alreadyRegistered') {
    // FIXME: alreadyRegistered bedeutet, dass die Domain bereits über uns verwaltet wird. Das wird dann hier nicht funktionieren
    set_domain_pretransfer($dom->id);
    $mode = 'transfer';
    title("Domain-Transfer vornehmen");
}


output("<p>Domainname: <strong>".$dom->fqdn."</strong></p>");

$section='domains_domains';


output('<h4>Inhaber der Domain</h4>');
output('<p>Legen Sie hier einen neuen Inhaber für diese Domain fest.</p>');

if ($_SESSION['domains_domainreg_detach'] == 0) {
    $_SESSION['domains_domainreg_admin_c'] = $_SESSION['domains_domainreg_owner'];
} 

// Behandlung für "keinen extra Verwalter"
if (isset($_GET['admin_c']) && $_GET['admin_c'] == 'none') {
    $_SESSION['domains_domainreg_admin_c'] = $_SESSION['domains_domainreg_owner'];
    $_SESSION['domains_domainreg_detach'] = 0;
}

if ($_SESSION['domains_domainreg_owner']) {
    $owner = get_contact($_SESSION['domains_domainreg_owner']);

    $function = 'Inhaber';
    if ($_SESSION['domains_domainreg_admin_c'] == $_SESSION['domains_domainreg_owner']) {
        $function .= ' und Verwalter';
    }
    output('<p><strong>'.$function.':</strong></p>'.display_contact($owner, ''));
} else {
    output('<p><strong>Inhaber und Verwalter:</strong></p><p><em>Bisher kein Inhaber ausgewählt</em>');
}
addnew('choose', 'Neuen Inhaber wählen', "type=owner&backto=domainreg");
if ($_SESSION['domains_domainreg_admin_c'] != $_SESSION['domains_domainreg_owner']) {
    $admin_c = get_contact($_SESSION['domains_domainreg_admin_c']);
    output('<p><strong>Verwalter:</strong></p>'.display_contact($admin_c, ''));
    addnew('choose', 'Neuen Verwalter wählen', "type=admin_c&backto=domainreg");
    output('<p class="delete">'.internal_link('', 'Keinen separaten Verwalter festlegen', 'admin_c=none').'</p>');
} else {
    addnew('choose', 'Einen separaten Verwalter wählen', "type=admin_c&detach=1&backto=domainreg");
}







