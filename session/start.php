<?php

/*

  Session-Start-Script wird vom dispatcher eingebunden

*/

require_once('session/checkuser.php');
require_once('inc/error.php');
require_once('inc/debug.php');

require_once('inc/base.php');

// __Host- prefix guarantees secure cookie that cannot be
// overwritten by other hosts:
// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-rfc6265bis
session_name('__Host-CONFIG_INTERFACE');

session_set_cookie_params(['path' => '/', 'secure' => true,
    'httponly' => true, 'samesite' => 'Lax', ]);

if (!session_start()) {
    logger(LOG_ERR, "session/start", "session", "Die session konnte nicht gestartet werden!");
    system_failure('Die Sitzung konnte nicht gestartet werden, bitte benachrichtigen Sie den Administrator!');
}

DEBUG("<pre>POST-DATA: " . htmlspecialchars(print_r($_POST, true)) . "\nSESSION_DATA: " . htmlspecialchars(print_r($_SESSION, true)) . "</pre>");

if (have_module('webmailtotp') && isset($_POST['webinterface_totpcode']) && isset($_SESSION['totp']) && isset($_SESSION['totp_username'])) {
    require_once('modules/webmailtotp/include/totp.php');
    $role = null;
    if (check_totp($_SESSION['totp_username'], $_POST['webinterface_totpcode'])) {
        $role = find_role($_SESSION['totp_username'], '', true);
    }
    if ($role === null) {
        $_SESSION['role'] = ROLE_ANONYMOUS;
        logger(LOG_WARNING, "session/start", "login", "wrong totp code (username: »{$_SESSION['totp_username']}«)");
        warning('Ihre Anmeldung konnte nicht durchgeführt werden. Geben Sie bitte einen neuen Code ein.');
        show_page('totp-login');
        die();
    } else {
        setup_session($role, $_SESSION['totp_username']);
    }
    unset($_POST['webinterface_totpcode']);
    unset($_SESSION['totp']);
    unset($_SESSION['totp_username']);
}

if (isset($_POST['webinterface_username']) && $_POST['webinterface_username'] != '' && isset($_POST['webinterface_password']) && $_POST['webinterface_password'] != '') {
    check_input_types($_POST, ['webinterface_username' => 'string', 'webinterface_password' => 'string']);
    $role = find_role($_POST['webinterface_username'], $_POST['webinterface_password']);
    if ($role === null) {
        $_SESSION['role'] = ROLE_ANONYMOUS;
        logger(LOG_WARNING, "session/start", "login", "wrong user data (username: »{$_POST['webinterface_username']}«)");
        login_screen('Ihre Anmeldung konnte nicht durchgeführt werden. Vermutlich haben Sie falsche Zugangsdaten eingegeben.');
    } else {
        setup_session($role, $_POST['webinterface_username']);
        if (isset($_POST['webinterface_password'])) {
            $result = strong_password($_POST['webinterface_password']);
            if ($result !== true) {
                logger(LOG_WARNING, "session/start", "login", "weak password detected for " . $_POST['webinterface_username']);
                warning('Unsere Überprüfung hat ergeben, dass Ihr Passwort in bisher veröffentlichten Passwortlisten enthalten ist, es ist daher als unsicher zu betrachten. Bitte ändern Sie Ihr Passwort bei Gelegenheit.');
                if ($role & (ROLE_VMAIL_ACCOUNT | ROLE_MAILACCOUNT)) {
                    redirect($prefix . 'go/email/chpass');
                } else {
                    redirect($prefix . 'go/index/chpass');
                }
            }
        }
    }
    unset($_POST['webinterface_username']);
    unset($_POST['webinterface_password']);
} elseif (isset($_SESSION['role'])) {
    /* User ist eingeloggt (aber vielleicht als ROLE_ANONYMOUS!) */
} else {
    $_SESSION['role'] = ROLE_ANONYMOUS;
}
// Wenn wir hier sind, ist der Benutzer eingeloggt. Möglicherweise nur als ANONYMOUS


DEBUG("Role: " . $_SESSION['role']);
