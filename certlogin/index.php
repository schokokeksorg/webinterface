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

// Setze das Arbeitsverzeichnis auf das Stammverzeichnis, damit die Voraussetzungen gleich sind wie bei allen anderen Requests
chdir('..');

require_once('config.php');
global $prefix;
$prefix = '../';

// Das Parent-Verzeichnis in den Include-Pfad, da wir uns jetzt in einem anderen Verzeichnis befinden.
ini_set('include_path', ini_get('include_path').':../');

require_once('session/start.php');
require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/error.php');
require_once('inc/theme.php');
require_once('modules/index/include/x509.php');


DEBUG('$_SERVER:');
DEBUG($_SERVER);

$redirect = "";
if (! isset($_SERVER['SSL_CLIENT_CERT']) && isset($_SERVER['REDIRECT_SSL_CLIENT_CERT'])) {
    $redirect = "REDIRECT_";
}

if ($_SESSION['role'] != ROLE_ANONYMOUS && isset($_REQUEST['record']) && isset($_REQUEST['backto']) && check_path($_REQUEST['backto'])) {
    DEBUG('recording client-cert');
    if (isset($_SERVER[$redirect.'SSL_CLIENT_CERT']) && isset($_SERVER[$redirect.'SSL_CLIENT_S_DN']) &&
      isset($_SERVER[$redirect.'SSL_CLIENT_I_DN']) && isset($_SERVER[$redirect.'SSL_CLIENT_M_SERIAL']) &&
      isset($_SERVER[$redirect.'SSL_CLIENT_V_START']) && isset($_SERVER[$redirect.'SSL_CLIENT_V_END'])
      ) {
        $_SESSION['clientcert_cert'] = $_SERVER[$redirect.'SSL_CLIENT_CERT'];
        $_SESSION['clientcert_dn'] = $_SERVER[$redirect.'SSL_CLIENT_S_DN'];
        $_SESSION['clientcert_issuer'] = $_SERVER[$redirect.'SSL_CLIENT_I_DN'];
        $_SESSION['clientcert_serial'] = $_SERVER[$redirect.'SSL_CLIENT_M_SERIAL'];
        $vstart = new DateTime($_SERVER[$redirect.'SSL_CLIENT_V_START']);
        $_SESSION['clientcert_valid_from'] = date_format($vstart, 'Y-m-d');
        $vend = new DateTime($_SERVER[$redirect.'SSL_CLIENT_V_END']);
        $_SESSION['clientcert_valid_until'] = date_format($vend, 'Y-m-d');
        header('Location: '.$prefix.$_REQUEST['backto'].encode_querystring(''));
        die();
    } else {
        warning('Ihr Browser hat kein Client-Zertifikat gesendet');
        header('Location: '.$prefix.$_REQUEST['backto'].encode_querystring(''));
        die();
    }
} elseif (isset($_REQUEST['type']) && isset($_REQUEST['username'])) {
    if (!isset($_SERVER[$redirect.'SSL_CLIENT_CERT'])) {
        system_failure('Ihr Browser hat kein Client-Zertifikat gesendet');
    }

    $ret = get_logins_by_cert($_SERVER[$redirect.'SSL_CLIENT_CERT']);
    DEBUG($ret);
    foreach ($ret as $account) {
        DEBUG('/'.$account['type'].'/'.$_REQUEST['type'].'/    /'.$account['username'].'/'.$_REQUEST['username'].'/    =>');
        if (($account['type'] == urldecode($_REQUEST['type'])) && ($account['username'] == urldecode($_REQUEST['username']))) {
            $uid = $account['username'];
            $role = find_role($uid, '', true);
            setup_session($role, $uid);
            $destination = 'go/index/index';
            if (check_path($account['startpage'])) {
                $destination = $account['startpage'];
            }
            if (isset($_REQUEST['destination']) && check_path($_REQUEST['destination'])) {
                $destination = $_REQUEST['destination'];
            }
            header('Location: ../'.ltrim($destination, '/'));
            die();
        }
    }
    system_failure('Der angegebene Account kann mit diesem Client-Zertifikat nicht eingeloggt werden.');
} elseif ($_SESSION['role'] != ROLE_ANONYMOUS && isset($_REQUEST['destination']) && $_REQUEST['destination'] != '') {
    # User hat sich grade eingeloggt
    header('Location: ../'.ltrim($destination, '/'));
} else {
    if (isset($_SERVER[$redirect.'SSL_CLIENT_CERT']) &&
      isset($_SERVER[$redirect.'SSL_CLIENT_S_DN']) && $_SERVER[$redirect.'SSL_CLIENT_S_DN'] != '' &&
      isset($_SERVER[$redirect.'SSL_CLIENT_I_DN']) && $_SERVER[$redirect.'SSL_CLIENT_I_DN'] != '' &&
      isset($_SERVER[$redirect.'SSL_CLIENT_M_SERIAL']) && $_SERVER[$redirect.'SSL_CLIENT_M_SERIAL'] != '') {
        $ret = get_logins_by_cert($_SERVER[$redirect.'SSL_CLIENT_CERT']);
        if ($ret === null) {
            login_screen('Ihr Browser hat ein Client-Zertifikat gesendet, dieses ist aber noch nicht für den Zugang hinterlegt. Melden Sie sich bitte per Benutzername und Passwort an.');
        }
        if (count($ret) == 1) {
            $uid = $ret[0]['username'];
            $role = find_role($uid, '', true);
            setup_session($role, $uid);
            DEBUG("Set Cookie!");
            setcookie('CLIENTCERT_AUTOLOGIN', '1', strtotime("+ 1 year"), '/', '', true, true);
            $destination = 'go/index/index';
            if (check_path($ret[0]['startpage'])) {
                $destination = $ret[0]['startpage'];
            }
            if (isset($_REQUEST['destination']) && check_path($_REQUEST['destination'])) {
                $destination = $_REQUEST['destination'];
            }
            header('Location: ../'.ltrim($destination, '/'));
            die();
        }
        output('<p>Ihr Browser hat ein gültiges SSL-Client-Zertifikat gesendet, mit dem Sie sich auf dieser Seite einloggen können. Allerdings haben Sie dieses Client-Zertifikat für mehrere Zugänge hinterlegt. Wählen Sie bitte den Zugang aus, mit dem Sie sich anmelden möchten.</p>
      <ul>');
        foreach ($ret as $account) {
            $type = 'System-Account';
            if ($account['type'] == 'email') {
                $type = 'E-Mail-Konto';
            } elseif ($account['type'] == 'subuser') {
                $type = 'Unter-Nutzer';
            } elseif ($account['type'] == 'customer') {
                $type = 'Kundenaccount';
            }
            $destination = 'go/index/index';
            if ($account['startpage'] && check_path($account['startpage'])) {
                $destination = $account['startpage'];
            }
            output('<li>'.internal_link('', $type.': <strong>'.$account['username'].'</strong>', 'type='.$account['type'].'&username='.urlencode($account['username']).'&destination='.urlencode($destination)).'</li>');
        }
        output('</ul>');
    } else {
        warning('Ihr Browser hat kein Client-Zertifikat gesendet. Eventuell müssen Sie in den Einstellungen des Browsers diese Funktion einschalten.');
        redirect('/');
        die();
    }
}

show_page('certlogin');
