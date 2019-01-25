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
require_once('x509.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_SUBUSER, ROLE_VMAIL_ACCOUNT));


if ($_GET['action'] == 'new') {
    check_form_token('clientcert_add');
    if (! isset($_SESSION['clientcert_cert'])) {
        system_failure('Kein Zertifikat');
    }

    add_clientcert(

      $_SESSION['clientcert_cert'],

      $_SESSION['clientcert_dn'],

      $_SESSION['clientcert_issuer'],
                 $_SESSION['clientcert_serial'],

      $_SESSION['clientcert_valid_from'],

      $_SESSION['clientcert_valid_until']

  );

    // Räume session auf
    unset($_SESSION['clientcert_cert']);
    unset($_SESSION['clientcert_dn']);
    unset($_SESSION['clientcert_issuer']);
    unset($_SESSION['clientcert_serial']);
    unset($_SESSION['clientcert_valid_from']);
    unset($_SESSION['clientcert_valid_until']);
    header('Location: cert');
} elseif ($_GET['action'] == 'delete') {
    $cert = get_cert_by_id($_GET['id']);
    if (! $cert) {
        system_failure('no ID');
    }
    $username = null;
    if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
        $username = $_SESSION['userinfo']['username'];
        if (isset($_SESSION['subuser'])) {
            $username = $_SESSION['subuser'];
        }
    } elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
        $username = $_SESSION['mailaccount'];
    }
    if (! ($cert['username'] == $username)) {
        system_failure('Das Zertifikat ist nicht für Ihren Zugang eingerichtet');
    }
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=delete&id={$cert['id']}", filter_input_general("Möchten Sie das Zertifikat »{$cert['dn']}« (Seriennummer {$cert['serial']}, Gültig von {$cert['valid_from']} bis {$cert['valid_until']}) wirklich löschen?"));
    } elseif ($sure === true) {
        delete_clientcert($cert['id']);
        if (! $debugmode) {
            header("Location: cert");
        }
    } elseif ($sure === false) {
        if (! $debugmode) {
            header("Location: cert");
        }
    }
} else {
    system_failure('Kein Kommando');
}
