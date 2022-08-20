<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('mailman.php');
require_role(ROLE_SYSTEMUSER);

$title = "Neue Mailingliste erstellen";
$domains = get_mailman_domains();

$maildomains = ['0' => config('mailman_host')];
foreach ($domains as $domain) {
    $maildomains[$domain['id']] = $domain['fqdn'];
}
DEBUG("maildomains");
DEBUG($maildomains);

if ($_GET['action'] == 'new') {
    $maildomain = $_POST['maildomain'];
    DEBUG("maildomain: ".$maildomain);
    if ($maildomain == null) {
        system_failure('Ihre Domain-Auswahl scheint ungültig zu sein');
    } elseif ('0' === (string) $maildomain) {
        DEBUG("maildomain == 0");
        $maildomain = null;
    } elseif (isset($maildomains[$maildomain])) {
        // regular, OK
        DEBUG("maildomain in \$maildomains");
    } else {
        DEBUG("possible new maildomain");
        $possible = get_possible_mailmandomains();
        $found = false;
        foreach ($possible as $domain) {
            if ($maildomain == 'd'.$domain['id']) {
                // lege Mailman-Domain neu an
                $found = true;
                $maildomain = insert_mailman_domain('lists', $domain['id']);
                warning('Die Domain '.$domain['fqdn'].' wurde erstmals für eine Mailingliste benutzt. Aufgrund der dafür nötigen Änderungen kann es bis zu 1 Stunde dauern, bis Mails an diese Adresse korrekt zugestellt werden.');
            }
        }
        if (! $found) {
            system_failure('Ihre Domain-Auswahl scheint ungültig zu sein');
        }
    }

    create_list($_POST['listname'], $maildomain, $_POST['admin']);
    redirect('lists');
} elseif ($_GET['action'] == 'newpw') {
    $list = get_list($_GET['id']);
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure('action=newpw&id='.$list['id'], 'Möchten Sie für die Mailingliste »<strong>'.$list['listname'].'</strong>@'.$list['fqdn'].'« ein neues Passwort anfordern? (Das neue Passwort wird dem Listenverwalter zugeschickt.)');
    } elseif ($sure === true) {
        request_new_password($list['id']);
        redirect('lists');
    } elseif ($sure === false) {
        redirect('lists');
    }
} elseif ($_GET['action'] == 'delete') {
    $list = get_list($_GET['id']);
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure('action=delete&id='.$list['id'], 'Möchten Sie die Mailingliste »<strong>'.$list['listname'].'</strong>@'.$list['fqdn'].'« wirklich löschen?');
    } elseif ($sure === true) {
        delete_list($list['id']);
        redirect('lists');
    } elseif ($sure === false) {
        redirect('lists');
    }
} else {
    system_failure('Function not implemented');
}
