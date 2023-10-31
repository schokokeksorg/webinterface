<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dyndns';

$id = null;
if (isset($_REQUEST['id'])) {
    $id = (int) $_REQUEST['id'];
}


if (isset($_GET['type']) && $_GET['type'] == 'dyndns') {
    if ($_GET['action'] == 'delete') {
        $sure = user_is_sure();
        if ($sure === null) {
            are_you_sure("type=dyndns&action=delete&id={$id}", "Möchten Sie den DynDNS-Account wirklich löschen?");
        } elseif ($sure === true) {
            delete_dyndns_account($id);
            if (!$debugmode) {
                header("Location: dyndns");
            }
        } elseif ($sure === false) {
            if (!$debugmode) {
                header("Location: dyndns");
            }
        }
    }
    if ($_GET['action'] == 'edit') {
        check_form_token('dyndns_edit');

        $newid = null;
        if ($id) {
            edit_dyndns_account($id, $_POST['handle'], $_POST['password_http'], $_POST['sshkey']);
        } else {
            $newid = create_dyndns_account($_POST['handle'], $_POST['password_http'], $_POST['sshkey']);
        }

        if (!($debugmode || we_have_an_error())) {
            if ($id) {
                // Bearbeitung
                header('Location: dyndns');
            } else {
                // Neu angelegt
                header('Location: dyndns_hostnames?id=' . $newid);
            }
        }
    }
}


if (isset($_GET['dns']) && isset($_GET['dom'])) {
    $section = 'dns_dns';
    $domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);
    $dom = null;
    foreach ($domains as $d) {
        if ($d->id == $_GET['dom']) {
            $dom = $d;
            break;
        }
    }
    if (!$dom) {
        system_failure("Domain nicht gefunden!");
    }
    if ($_GET['dns'] == 0) {
        if ($dom->dns == 1) {
            $sure = user_is_sure();
            if ($sure === null) {
                are_you_sure("dom={$dom->id}&dns=0", "Möchten Sie die Domain {$dom->fqdn} aus dem DNS-Server entfernen?");
            } elseif ($sure === true) {
                remove_from_dns($dom->id);
                redirect('dns');
            } elseif ($sure === false) {
                redirect('dns');
            }
        } else {
            system_failure("Diese Domain ist nicht im DNS-Server eingetragen.");
        }
    }
    if ($_GET['dns'] == 1) {
        if ($dom->dns == 0) {
            add_to_dns($dom->id);
            redirect('dns');
        } else {
            system_failure("Diese Domain ist bereits im DNS-Server eingetragen.");
        }
    }
}
