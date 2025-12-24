<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("class/domain.php");
require_once("inc/debug.php");
require_once("domains.php");
require_once("domainapi.php");
require_role(ROLE_CUSTOMER);
check_form_token('domains_domainreg');

if (!(isset($_SESSION['domains_domainreg_owner']) && $_SESSION['domains_domainreg_owner'])
    || !(isset($_SESSION['domains_domainreg_admin_c']) && $_SESSION['domains_domainreg_admin_c'])
    || !(isset($_SESSION['domains_domainreg_domainname']) && $_SESSION['domains_domainreg_domainname'])) {
    system_failure("Fehler im Programmablauf!");
}

if (!(isset($_REQUEST['domain']) && $_REQUEST['domain'])
    || $_REQUEST['domain'] != $_SESSION['domains_domainreg_domainname']) {
    system_failure("Fehler im Programmablauf!");
}
// Validierung der Domain entfällt hier, weil wir nur bestehende Domain aus der Datenbank laden. Bei ungültiger Eingabe wird kein Treffer gefunden.
$dom = new Domain((string) $_REQUEST['domain']);
$dom->ensure_userdomain();

// Speichere Kontakte
domain_ownerchange($dom->fqdn, $_SESSION['domains_domainreg_owner'], $_SESSION['domains_domainreg_admin_c']);

$authinfo = null;
if ($dom->status == 'pretransfer') {
    if (!(isset($_REQUEST['authinfo']) && $_REQUEST['authinfo'])) {
        system_failure("Kein Auth-Info-Code angegeben!");
    }
    $authinfo = chop($_REQUEST['authinfo']);
}

$customerno = (int) $_SESSION['customerinfo']['customerno'];
$customer = get_customer_info($customerno);
$msg = 'Sie haben in Ihrem Kundenkonto bei ' . config('company_name') . ' eine Domainregistrierung 
in Auftrag gegeben.

Domainname: ' . $dom->fqdn . '

Die Registrierung wird umgehend ausgeführt. Bis die Domain vollständig nutzbar ist, 
können abhängig von der Domainendung und damit der zuständigen Registrierungsstelle 
ein paar Stunden vergehen. Sollten bei der Registrierung Fehler auftreten, werden 
die Administratoren direkt darüber informiert und werden sich umgehend darum kümmern.

Mit freundlichen Grüßen,
Ihre Admins von ' . config('company_name');
if ($dom->status == 'pretransfer') {
    $msg = 'Sie haben in Ihrem Kundenkonto bei ' . config('company_name') . ' einen Domaintransfer
in Auftrag gegeben.

Domainname: ' . $dom->fqdn . '

Der Transfer wird umgehend ausgeführt. Bis die Domain vollständig umgezogen ist, 
können abhängig von der Domainendung und damit der zuständigen Registrierungsstelle 
ein paar Stunden vergehen. Sollten beim Domainumzug Fehler auftreten, werden die 
Administratoren direkt darüber informiert und werden sich umgehend darum kümmern.

Mit freundlichen Grüßen,
Ihre Admins von ' . config('company_name');
}

$msg .= "\n\nDiese Bestellung haben wir am " . date("r") . " von der IP-Adresse\n{$_SERVER['REMOTE_ADDR']} erhalten.\nSofern Sie dies nicht ausgelöst haben, benachrichtigen Sie bitte den Support\ndurch eine Antwort auf diese E-Mail.";

$recipient = $customer['email'];
if ($debugmode) {
    $recipient = config('adminmail');
}
if (!$debugmode) {
    // Keine Mail im Debug-Mode versenden
    send_mail($customer['email'], 'Domainregistrierung ' . $dom->fqdn, $msg);
} else {
    warning("Im Debug-Modus wurde KEINE Bestätigungsmail versendet!");
}

api_register_domain($dom->fqdn, $authinfo);

success_msg('Die Registrierung wurde in Auftrag gegeben. Der Domain-Status sollte sich in den nächsten Minuten entsprechend ändern.');

unset($_SESSION['domains_domainreg_owner']);
unset($_SESSION['domains_domainreg_admin_c']);
unset($_SESSION['domains_domainreg_detach']);
unset($_SESSION['domains_domainreg_domainname']);

redirect('domains');
