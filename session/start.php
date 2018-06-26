<?php
/*

  Session-Start-Script wird vom dispatcher eingebunden

*/

require_once('session/checkuser.php');
require_once('inc/error.php');
require_once('inc/debug.php');

require_once('inc/base.php');

session_name(config('session_name'));

if ($_SERVER['HTTPS']) {
    session_set_cookie_params(0, '/', '', true, true);
}

if (!session_start()) {
    logger(LOG_ERR, "session/start", "session", "Die session konnte nicht gestartet werden!");
    system_failure('Die Sitzung konnte nicht gestartet werden, bitte benachrichtigen Sie den Administrator!');
}

DEBUG("<pre>POST-DATA: ".htmlspecialchars(print_r($_POST, true))."\nSESSION_DATA: ".htmlspecialchars(print_r($_SESSION, true))."</pre>");

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
        show_page('webmailtotp-login');
        die();
    } else {
        setup_session($role, $_SESSION['totp_username']);
    }
    unset($_POST['webinterface_totpcode']);
    unset($_SESSION['totp']);
    unset($_SESSION['totp_username']);
}

if (isset($_POST['webinterface_username']) && isset($_POST['webinterface_password'])) {
    $role = find_role($_POST['webinterface_username'], $_POST['webinterface_password']);
    if ($role === null) {
        $_SESSION['role'] = ROLE_ANONYMOUS;
        logger(LOG_WARNING, "session/start", "login", "wrong user data (username: »{$_POST['webinterface_username']}«)");
        login_screen('Ihre Anmeldung konnte nicht durchgeführt werden. Vermutlich haben Sie falsche Zugangsdaten eingegeben.');
    } else {
        setup_session($role, $_POST['webinterface_username']);
    }
    unset($_POST['webinterface_username']);
    unset($_POST['webinterface_password']);
} elseif (isset($_SESSION['role'])) {
    /* User ist eingeloggt (aber vielleicht als ROLE_ANONYMOUS!) */
} else {
    $_SESSION['role'] = ROLE_ANONYMOUS;
}
// Wenn wir hier sind, ist der Benutzer eingeloggt. Möglicherweise nur als ANONYMOUS


DEBUG("Role: ".$_SESSION['role']);
