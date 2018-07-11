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

if (! config('http.net-apikey')) {
    system_failure("Dieses System ist nicht eingerichtet zum Hinzufügen von Domains");
}

$dom = null;
if (isset($_REQUEST['domain'])) {
    $request = idn_to_utf8($_REQUEST['domain'], 0, INTL_IDNA_VARIANT_UTS46);
    if (substr($request, 0, 4) == 'www.') {
        $request = str_replace('www.', '', $request);
    }
    verify_input_general($request);
    $punycode = idn_to_ascii($request, 0, INTL_IDNA_VARIANT_UTS46);
    if (!check_domain($punycode)) {
        warning("Ungültiger Domainname: ".filter_input_general($request));
        redirect('adddomain');
    }
    $dom = new Domain();
    if ($dom->loadByName($request) !== false && !$dom->is_customerdomain()) {
        warning('Diese Domain ist bei einem anderen Kunden von uns in Nutzung. Kontaktieren Sie den Support, wenn Sie eine Domain in ein anderes Kundenkonto übertragen möchten.');
        redirect('adddomain');
    }
    $dom = new Domain();
    if ($dom->loadByName($request) === false) {
        // Eintragen mit DNS und Mail
        $id = insert_domain_external($request, true, true);
        $dom->loadByName($request);
    }
    $dom->ensure_customerdomain();
    if ($dom->provider == 'terions') {
        system_failure("Diese Domain ist bereits auf Ihr Kundenkonto registriert.");
    }

    // An diesem Punkt ist die Domain eingetragen als extern und ggf. mit Mailserver-Lock
    // Bei der Reg-Bestätigung wird das Lock entfernt und die Daten entsprechend gesetzt, inklusive Preise.

    $_SESSION['domains_domainreg_owner'] = $dom->owner;
    $_SESSION['domains_domainreg_admin_c'] = $dom->admin_c;
    $_SESSION['domains_domainreg_domainname'] = $request;
    $_SESSION['domains_domainreg_detach'] = 0;
} elseif (isset($_SESSION['domains_domainreg_domainname'])) {
    $domain = $_SESSION['domains_domainreg_domainname'];
    $dom = new Domain($domain);
    $dom->ensure_customerdomain();
}

if (!$dom) {
    system_failure("Keine Domain");
}

$avail = api_domain_available($dom->fqdn);
$tld = $avail['extension'];
if ($tld != $dom->tld) {
    system_failure("Fehler in den Daten! Bitte Support informieren");
}

$pricedata = get_domain_offer($tld);
if (!$pricedata) {
    // Hier kommen wir hin, wenn eine externe Domain umgezogen wird, deren Endung wir nicht automatisch anbieten
    warning('Die Domain '.$dom->fqdn.' kann nicht über dieses Webinterface umgezogen werden weil bei dieser Endung Besonderheiten zu beachten sind. Bitte kontaktieren Sie den Support.');
    redirect('domains');
}
$mode=null;

if ($avail['status'] == 'available') {
    set_domain_prereg($dom->id);
    $mode = 'reg';
    title("Domain registrieren");
} elseif ($avail['status'] == 'registered' || $avail['status'] == 'alreadyRegistered') {
    // FIXME: alreadyRegistered bedeutet, dass die Domain bereits über uns verwaltet wird. Das wird dann hier nicht funktionieren
    set_domain_pretransfer($dom->id);
    $mode = 'transfer';
    title("Domain-Transfer vornehmen");
}

output("<p>Domainname: <strong>".$dom->fqdn."</strong></p>");

$section='domains_domains';


output('<h4>Inhaber der Domain</h4>');
output('<p>Legen Sie hier einen neuen Inhaber für diese Domain fest.</p>');

if ($_SESSION['domains_domainreg_owner'] === null) {
    $kundenkontakte = get_kundenkontakte();
    $customer = get_contact($kundenkontakte['kunde']);
    if (possible_domainholder($customer)) {
        $_SESSION['domains_domainreg_owner'] = $kundenkontakte['kunde'];
    } else {
        $list = array_keys(possible_domainholders());
        if (count($list) > 0) {
            $_SESSION['domains_domainreg_owner'] = $list[0];
        }
    }
}

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
addnew('choose', 'Inhaber wählen', "type=owner&backto=domainreg");
if ($_SESSION['domains_domainreg_admin_c'] != $_SESSION['domains_domainreg_owner']) {
    $admin_c = get_contact($_SESSION['domains_domainreg_admin_c']);
    output('<p><strong>Verwalter:</strong></p>'.display_contact($admin_c, ''));
    addnew('choose', 'Anderen Verwalter wählen', "type=admin_c&backto=domainreg");
    output('<p class="delete">'.internal_link('', 'Keinen separaten Verwalter festlegen', 'admin_c=none').'</p>');
} else {
    addnew('choose', 'Einen separaten Verwalter wählen', "type=admin_c&detach=1&backto=domainreg");
}


$form = '';
if ($mode == 'transfer') {
    $form .= '<h4>Auth-Info-Code für den Transfer</h4>';
    $form .= '<p><label for="authinfo">Auth-Info-Code für den Domainumzug:</label> <input type="text" name="authinfo" id="authinfo"></p>';
}

$form .= '<h4>Kosten</h4>';

$form .= '<p>Für die Verwaltung der Domain fallen folgende Kosten an:</p>
<table>
<tr><td>Domainname:</td><td><strong>'.$dom->fqdn.'</strong></td></tr>
<tr><td>Jahresgebühr:</td><td style="text-align: right;">'.$pricedata['gebuehr'].' €'.footnote('Preis für Deutschland, inkl. 19% USt. Preise für andere Länder entsprechend. Bitte beim Support anfragen').'</td></tr>';
if ($pricedata['setup']) {
    $form .= '<tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">'.$pricedata['setup'].' €'.footnote('Preis für Deutschland, inkl. 19% USt. Preise für andere Länder entsprechend. Bitte beim Support anfragen').'</td></tr>';
}
$form .='</table>';
$form .= '<p>Mit dieser Bestellung geben Sie eine verbindliche Willenserklärung ab, diese Domain registrieren zu wollen. Sie treten in ein Vertragsverhältnis zu '.config('company_name').' unter dem Vorbehalt, dass die Domain registriert werden kann. Die Hoheit über die Vergabe der Domains hat die jeweils zuständige Registrierungsstelle. Es gelten die Vergabe-Bedingungen der jeweils zuständigen Registrierungsstelle.</p>
<p>Der Domain-Vertrag beginnt mit Zuteilung der Domain durch die Regisrierungsstelle und läuft jeweils '.$pricedata['interval'].' Monate. Er verlängert sich stets automatisch um weitere '.$pricedata['interval'].' Monate, wenn nicht bis 14 Tage vor Ende der Laufzeit eine Kündigung vorliegt.</p>';

$form .= '<p><input type="hidden" name="domain" value="'.filter_input_general($dom->fqdn).'">
<input type="submit" name="submit" value="Kostenpflichtigen Vertrag abschließen"></p>';
output(html_form('domains_domainreg', 'domainreg_save', '', $form));
output('<p>'.internal_link('domains', 'Zurück').'</p>');
