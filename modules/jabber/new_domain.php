<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
$title = "Domain für Jabber freischalten";
title("Eigene Domain für Jabber-Nutzung freischalten");


$domains = get_domain_list((int) $_SESSION['customerinfo']['customerno']);
DEBUG($domains);

if (!count($domains)) {
    system_failure("Sie haben gar keine eigenen Domains.");
}

$pending_domains = [];
$available_domains = [];

foreach ($domains as $d) {
    if ($d->jabber == 0) {
        $available_domains[$d->id] = $d->domainname . '.' . $d->tld;
    }
    if ($d->jabber == 2) {
        $pending_domains[] = $d->fqdn;
    }
}


$pending = '';
if (count($pending_domains) > 0) {
    $pending = '<h3>Wartend auf Freischaltung</h3>
<p>Folgende Domains sind bereits eingetragen und werden in der kommenden Nacht im Jabber-Server registriert:</p>
<ul>';
    foreach ($pending_domains as $d) {
        $pending .= '<li>' . $d . '</li>';
    }
    $pending .= '</ul>';
}


output('<p>Sie können hier eine Ihrer eigenen Domains für Jabber-Nutzung freischalten. Da dafür ein Neustart des Jabber-Servers nötig ist, können Sie die Domain erst ab dem darauffolgenden Tag für eigene Jabber-Accounts nutzen.</p>
<p><strong>Bitte beachten Sie:</strong> Damit beim Verbinden ihrer Jabber-Accounts kein Fehler auftritt, nutzen wir für die TLS-Verschlüsselung ein Zertifikat der Webserver-Konfiguration mit dem passenden Domainnamen, sofern dort ein gültiges Zertifikat vorhanden ist. Ohne passendes Zertifikat wird beim Verbinden eines Accounts mit einer eigenen Domain ein Zertifikat-Fehler auftreten.</p>

' . html_form('jabber_new_domain', 'save', 'action=newdomain', '
<p>Domain wählen: ' . html_select('domain', $available_domains) . '</p>
<input type="submit" name="submit" value="Freischalten" />

') . $pending);
