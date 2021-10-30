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

require_once('session/start.php');

require_once('vmail.php');

require_role([ROLE_SYSTEMUSER, ROLE_VMAIL_ACCOUNT]);

require_once("inc/debug.php");
global $debugmode;

$section = 'email_vmail';


if ($_GET['action'] == 'edit') {
    check_form_token('vmail_edit_mailbox');
    $accountlogin = ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT);

    if ($accountlogin) {
        $section = 'email_edit';
        $id = get_vmail_id_by_emailaddr($_SESSION['mailaccount']);
        $account = get_account_details($id, false);
        // Leere das, sonst werden die vervielfacht
        $account['forwards'] = [];
    } else {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        $account = empty_account();
        $account['id'] = null;
        if ($id) {
            $account['id'] = $id;

            $oldaccount = get_account_details($id);
            $account['local'] = $oldaccount['local'];
            $account['domain'] = $oldaccount['domain'];
        } else {
            $account['local'] = $_POST['local'];
            $account['domain'] = (int) $_POST['domain'];
        }
        $account['enableextensions'] = false;
        if (isset($_POST['enableextensions']) && $_POST['enableextensions'] == 'yes') {
            $account['enableextensions'] = true;
        }
        $account['password'] = $_POST['password'];

        if (($account['password'] == '') && (isset($_POST['mailbox']) && $_POST['mailbox'] == 'yes')) {
            system_failure("Sie haben ein leeres Passwort eingegeben!");
        }
        if ($_POST['password'] == '**********') {
            $account['password'] = '';
        }
        if (!isset($_POST['mailbox']) || (isset($_POST['mailbox']) && $_POST['mailbox'] != 'yes')) {
            $account['password'] = null;
        }
        if (isset($_POST['quota'])) {
            $account['quota'] = $_POST['quota'];
        }

        $account['quota_threshold'] = -1;
        if (isset($_POST['quota_notify']) && isset($_POST['quota_threshold']) && $_POST['quota_notify'] == 1) {
            $account['quota_threshold'] = $_POST['quota_threshold'];
        }
    }

    $ar = empty_autoresponder_config();
    $valid_from_date = time();
    $valid_until_date = null;
    if (isset($_POST['ar_valid_from']) && ($_POST['ar_valid_from'] == 'now')) {
        $valid_from_date = time();
    } else {
        if (isset($_POST['ar_startdate'])) {
            if (date('Y-m-d', strtotime($_POST['ar_startdate'])) != $_POST['ar_startdate']) {
                system_failure('Das Aktivierungs-Datum scheint ungültig zu sein.');
            } else {
                $valid_from_date = strtotime($_POST['ar_startdate']);
            }
        }
    }
    if ($valid_from_date < time()) {
        $valid_from_date = time();
        warning('Das Aktivierungs-Datum liegt in der Vergangenheit. Die Funktion wird ab sofort aktiviert.');
    }
    if ($valid_from_date > time() + 365*24*60*60) {
        warning('Das Aktivierungs-Datum liegt mehr als ein Jahr in der Zukunft. Bitte prüfen Sie ob Sie das korrekte Jahr gewählt haben.');
    }
    if (isset($_POST['ar_valid_until']) && ($_POST['ar_valid_until'] == 'infinity')) {
        $valid_until_date = null;
    } else {
        if (isset($_POST['ar_enddate'])) {
            if (date('Y-m-d', strtotime($_POST['ar_enddate'])) != $_POST['ar_enddate']) {
                system_failure('Das Deaktivierungs-Datum scheint ungültig zu sein.');
            } else {
                $valid_until_date = strtotime($_POST['ar_enddate']);
            }
        }
    }
    if (!isset($_POST['autoresponder']) || $_POST['autoresponder'] != 'yes') {
        $valid_from_date = null;
    } else {
        if ($valid_until_date && $valid_until_date < time()) {
            warning('Das Deaktivierungs-Datum liegt in der Vergangenheit, der Autoresponder wird sofort deaktiviert!');
        }
    }
    if ($valid_from_date) {
        $ar['valid_from'] = date('Y-m-d', $valid_from_date);
    } else {
        $ar['valid_from'] = null;
    }
    if ($valid_until_date) {
        $ar['valid_until'] = date('Y-m-d', $valid_until_date);
    } else {
        $ar['valid_until'] = null;
    }

    if (isset($_POST['ar_subject']) && $_POST['ar_subject'] == 'custom' && isset($_POST['ar_subject_value']) && chop($_POST['ar_subject_value']) != '') {
        $ar['subject'] = chop($_POST['ar_subject_value']);
    }

    if (isset($_POST['ar_message'])) {
        $ar['message'] = $_POST['ar_message'];
    }

    if (isset($_POST['ar_quote'])) {
        if ($_POST['ar_quote'] == 'teaser') {
            $ar['quote'] = 'teaser';
        }
        if ($_POST['ar_quote'] == 'inline') {
            $ar['quote'] = 'inline';
        }
        if ($_POST['ar_quote'] == 'attach') {
            $ar['quote'] = 'attach';
        }
    }

    if (isset($_POST['ar_from']) && $_POST['ar_from'] == 'custom' && isset($_POST['ar_fromname'])) {
        $ar['fromname'] = $_POST['ar_fromname'];
    }

    $account['autoresponder'] = $ar;



    if (isset($_POST['forward']) && $_POST['forward'] == 'yes') {
        $num = 1;
        while (true) {
            // Die ersten 50 Einträge in jedem Fall prüfen, danach nur so lange zusätzliche Einträge vorhanden
            if (! isset($_POST['forward_to_'.$num]) && $num > 50) {
                break;
            }
            if (isset($_POST['forward_to_'.$num]) && chop($_POST['forward_to_'.$num]) != '') {
                $fwd = ["destination" => chop($_POST['forward_to_'.$num])];
                array_push($account['forwards'], $fwd);
            }
            $num++;
        }
        if (count($account['forwards']) == 0) {
            system_failure("Bitte mindestens eine Weiterleitungsadresse angeben.");
        }
    }

    if ($account['password'] === null && count($account['forwards']) == 0) {
        system_failure("Entweder eine Mailbox oder eine Weiterleitung muss angegeben werden!");
    }

    DEBUG($account);

    save_vmail_account($account);

    if (! ($debugmode || we_have_an_error())) {
        if ($accountlogin) {
            header('Location: /');
        } else {
            header('Location: vmail');
        }
    }
} elseif ($_GET['action'] == 'delete') {
    $title = "E-mail-Adresse löschen";
    $section = 'vmail_vmail';

    $account = get_account_details((int) $_GET['id']);

    $domain = null;
    $domains = get_vmail_domains();
    foreach ($domains as $dom) {
        if ($dom['id'] == $account['domain']) {
            $domain = $dom['domainname'];
            break;
        }
    }
    $account_string = $account['local'] . "@" . $domain;
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=delete&id={$account['id']}", "Möchten Sie die E-Mail-Adresse »{$account_string}« wirklich löschen?");
    } elseif ($sure === true) {
        delete_account($account['id']);
        if (! $debugmode) {
            header("Location: vmail");
        }
    } elseif ($sure === false) {
        if (! $debugmode) {
            header("Location: vmail");
        }
    }
} elseif ($_GET['action'] == 'suspend') {
    $title = "E-mail-Adresse stilllegen";
    $section = 'vmail_vmail';

    $account = get_account_details((int) $_GET['id']);

    $domain = null;
    $domains = get_vmail_domains();
    foreach ($domains as $dom) {
        if ($dom['id'] == $account['domain']) {
            $domain = $dom['domainname'];
            break;
        }
    }
    $account_string = $account['local'] . "@" . $domain;

    if (!isset($_POST['smtpreply']) || !$_POST['smtpreply']) {
        system_failure('Zur Stilllegung einer Adresse müssen Sie einen Text eingeben den der Absender als Fehlermeldung erhält.');
    }
    $account['smtpreply'] = $_POST['smtpreply'];

    save_vmail_account($account);
    if (! $debugmode) {
        header("Location: vmail");
    }
} elseif ($_GET['action'] == 'unsuspend') {
    $title = "E-mail-Adresse wieder aktivieren";
    $section = 'vmail_vmail';

    $account = get_account_details((int) $_GET['id']);

    $domain = null;
    $domains = get_vmail_domains();
    foreach ($domains as $dom) {
        if ($dom['id'] == $account['domain']) {
            $domain = $dom['domainname'];
            break;
        }
    }
    $account_string = $account['local'] . "@" . $domain;

    $account['smtpreply'] = null;

    save_vmail_account($account);
    if (! $debugmode) {
        header("Location: vmail");
    }
} else {
    system_failure("Unimplemented action");
}

output('');
