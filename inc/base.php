<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('class/database.php');
require_once('inc/debug.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('vendor/autoload.php');

function config($key, $localonly = false)
{
    global $config;

    if ($key == "modules") {
        // Stelle sicher, dass das "index"-Modul immer aktiv ist!
        if (!in_array("index", $config['modules'])) {
            $config['modules'][] = "index";
        }
        // Stelle sicher, dass das "about"-Modul immer aktiv ist!
        if (!in_array("about", $config['modules'])) {
            $config['modules'][] = "about";
        }
    }

    if ($key == 'modules' && isset($_SESSION['restrict_modules'])) {
        $modules = [];
        foreach ($config['modules'] as $mod) {
            if (in_array($mod, $_SESSION['restrict_modules'])) {
                $modules[] = $mod;
            }
        }
        return $modules;
    }

    if (array_key_exists($key, $config)) {
        return $config[$key];
    }

    if ($localonly) {
        return null;
    }

    /* read configuration from database */
    $result = db_query("SELECT `key`, value FROM misc.config");

    while ($object = $result->fetch()) {
        if (!array_key_exists($object['key'], $config)) {
            $config[$object['key']] = $object['value'];
        }
    }
    // Sonst wird das Passwort des webadmin-Users mit ausgegeben
    $debug_config = $config;
    unset($debug_config['db_pass']);
    DEBUG($debug_config);
    if (array_key_exists($key, $config)) {
        return $config[$key];
    } else {
        logger(LOG_ERR, "inc/base", "config", "Request to read nonexistent config option »{$key}«.");
    }
    return null;
}

function have_role($role)
{
    $have = $_SESSION['role'] & $role;
    if ($have) {
        DEBUG("Current user has role " . $role);
    } else {
        DEBUG("Current user does not have role " . $role);
    }
    return $have;
}

function get_server_by_id($id)
{
    $id = (int) $id;
    $result = db_query("SELECT hostname FROM system.servers WHERE id=?", [$id]);
    $ret = $result->fetch();
    return $ret['hostname'];
}


function redirect($target)
{
    global $debugmode;
    if ($target == '') {
        $target = $_SERVER['REQUEST_URI'];
    }
    if (!$debugmode) {
        header("Location: {$target}");
    } else {
        if (strpos($target, '?') === false) {
            print 'REDIRECT: ' . internal_link($target, $target);
        } else {
            [$file, $qs] = explode('?', $target, 2);
            print 'REDIRECT: ' . internal_link($file, $target, $qs);
        }
    }
    die();
}


function my_server_id()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT server FROM system.useraccounts WHERE uid=?", [$uid]);
    $r = $result->fetch();
    DEBUG($r);
    return $r['server'];
}


function additional_servers()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT server FROM system.user_server WHERE uid=?", [$uid]);
    $servers = [];
    while ($s = $result->fetch()) {
        $servers[] = $s['server'];
    }
    DEBUG($servers);
    return $servers;
}


function server_names()
{
    $result = db_query("SELECT id, hostname FROM system.servers");
    $servers = [];
    while ($s = $result->fetch()) {
        $servers[$s['id']] = $s['hostname'];
    }
    DEBUG($servers);
    return $servers;
}


function maybe_null($value)
{
    if (!$value) {
        return null;
    }

    if (strlen((string) $value) > 0) {
        return (string) $value;
    } else {
        return null;
    }
}


#define('LOG_ERR', 3);
#define('LOG_WARNING', 4);
#define('LOG_INFO', 6);

function logger($severity, $scriptname, $scope, $message)
{
    if (config('logging') < $severity) {
        DEBUG("NOT LOGGING $scriptname:$scope:$message");
        return;
    }

    DEBUG("LOGGING $scriptname:$scope:$message");
    $user = null;
    if (array_key_exists("role", $_SESSION)) {
        if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
            $user = $_SESSION['userinfo']['username'];
        } elseif ($_SESSION['role'] & ROLE_CUSTOMER) {
            $user = $_SESSION['customerinfo']['customerno'];
        }
    }

    $args = [":user" => $user,
        ":remote" => $_SERVER['REMOTE_ADDR'],
        ":scriptname" => $scriptname,
        ":scope" => $scope,
        ":message" => $message, ];

    db_query("INSERT INTO misc.scriptlog (remote, user,scriptname,scope,message) VALUES (:remote, :user, :scriptname, :scope, :message)", $args);
}

function count_failed_logins()
{
    if (config('logging') < LOG_WARNING) {
        DEBUG("logging is disabled, no brute force check possible");
        return;
    }
    $result = db_query("SELECT count(*) AS num FROM misc.scriptlog WHERE user IS NULL AND scriptname='session/start' AND scope='login' AND message LIKE 'wrong user data%' AND remote=:remote AND `timestamp` > NOW() - INTERVAL 10 MINUTE", [":remote" => $_SERVER['REMOTE_ADDR']]);
    $data = $result->fetch();
    DEBUG('seen ' . $data['num'] . ' failed logins from this address within 10 minutes');
    return $data['num'];
}

function html_header($arg)
{
    global $html_header;
    $html_header .= $arg;
}

function title($arg)
{
    global $title;
    $title = $arg;
}

function headline($arg)
{
    global $headline;
    $headline = $arg;
}

function output($arg)
{
    global $output;
    $output .= $arg;
}

function footnote($explanation)
{
    global $footnotes;
    if (!isset($footnotes) || !is_array($footnotes)) {
        $footnotes = [];
    }
    $fnid = array_search($explanation, $footnotes);
    DEBUG($footnotes);
    if ($fnid === false) {
        DEBUG("Footnote »{$explanation}« is not in footnotes!");
        $footnotes[] = $explanation;
    }
    $fnid = array_search($explanation, $footnotes);
    return str_repeat('*', ($fnid + 1));
}

function random_string($len)
{
    $s = str_replace('+', '.', base64_encode(random_bytes(ceil($len * 3 / 4))));
    return substr($s, 0, $len);
}


function are_you_sure($query_string, $question)
{
    $query_string = encode_querystring($query_string);
    $token = random_string(20);
    $_SESSION['are_you_sure_token'] = $token;
    title('Sicherheitsabfrage');
    output("
    <form action=\"{$query_string}\" method=\"post\">
    <div class=\"confirmation\">
      <div class=\"question\">{$question}</div>
      <p class=\"buttons\">
        <input type=\"hidden\" name=\"random_token\" value=\"{$token}\">
        <input type=\"submit\" name=\"really\" value=\"Ja\">
        &#160; &#160;
        <input type=\"submit\" name=\"not_really\" value=\"Nein\">
      </p>
    </div>");
    output("</form>\n");
}


function user_is_sure()
{
    if (isset($_POST['really'])) {
        if (array_key_exists('random_token', $_POST) &&
            ($_POST['random_token'] == $_SESSION['are_you_sure_token'])) {
            return true;
        } else {
            system_failure("Possible Cross-site-request-forgery detected!");
        }
    } elseif (isset($_POST['not_really'])) {
        return false;
    } else {
        return null;
    }
}



function generate_form_token($form_id)
{
    require_once("inc/debug.php");
    $sessid = session_id();
    if ($sessid == "") {
        DEBUG("Uh? Session not running? Wtf?");
        system_failure("Internal error!");
    }
    if (!isset($_SESSION['session_token'])) {
        $_SESSION['session_token'] = random_string(10);
    }
    return hash('sha256', $sessid . $form_id . $_SESSION['session_token']);
}


function check_form_token($form_id, $formtoken = null)
{
    if ($formtoken == null && isset($_REQUEST['formtoken'])) {
        $formtoken = $_REQUEST['formtoken'];
    }
    $sessid = session_id();
    if ($sessid == "") {
        DEBUG("Uh? Session not running? Wtf?");
        system_failure("Internal error! (Session not running)");
    }

    if (!isset($_SESSION['session_token'])) {
        $_SESSION['session_token'] = random_string(10);
    }
    $correct_formtoken = hash('sha256', $sessid . $form_id . $_SESSION['session_token']);

    if (!($formtoken == $correct_formtoken)) {
        system_failure("Possible cross-site-request-forgery!");
    }
}


function have_module($modname)
{
    return in_array($modname, config('modules'));
}


function use_module($modname)
{
    global $prefix, $needed_modules;
    if (!isset($needed_modules)) {
        $needed_modules = [];
    }
    if (in_array($modname, $needed_modules)) {
        return;
    }
    $needed_modules[] = $modname;
    if (!have_module($modname)) {
        system_failure("Soll nicht verfügbares Modul laden!");
    }
    /* setup module include path */
    ini_set('include_path', ini_get('include_path') . ':./modules/' . $modname . '/include:');
    $style = 'modules/' . $modname . '/style.css';
    if (file_exists($style)) {
        html_header('<link rel="stylesheet" href="' . $prefix . $style . '">' . "\n");
    }
}


function encode_querystring($querystring)
{
    global $debugmode;
    if ($debugmode) {
        $querystring = 'debug&' . $querystring;
    }
    $query = explode('&', $querystring);
    $new_query = [];
    foreach ($query as $item) {
        if ($item != '') {
            $split = explode('=', $item, 2);
            if (count($split) == 1) {
                $new_query[] = $split[0];
            } else {
                $new_query[] = $split[0] . '=' . urlencode($split[1]);
            }
        }
    }
    $querystring = implode('&amp;', $new_query);
    if ($querystring) {
        $querystring = '?' . $querystring;
    }
    return $querystring;
}


function beta_notice()
{
    output('<div class="beta"><h4>Achtung: Testbetrieb</h4><p>Diese Funktion ist im Testbetrieb. Bei Fehlfunktionen, Unklarheiten oder Verbesserungsvorschlägen bitten wir um kurze Nachricht an <a href="mailto:root@schokokeks.org">root@schokokeks.org</a></p></div>');
}


function addnew($file, $label, $querystring = '', $attribs = '')
{
    output('<p class="addnew">' . internal_link($file, $label, $querystring, $attribs) . '</p>');
}


function internal_link($file, $label, $querystring = '', $attribs = '')
{
    global $prefix;
    if (strpos($file, '/') === 0) {
        $file = $prefix . substr($file, 1);
    }
    $querystring = encode_querystring($querystring);
    return "<a href=\"{$file}{$querystring}\" {$attribs} >{$label}</a>";
}


function html_form($form_id, $scriptname, $querystring, $content, $extraid = "")
{
    $querystring = encode_querystring($querystring);
    $ret = '';
    $ret .= '<form id="' . $form_id . $extraid . '" ';
    if ($scriptname . $querystring !== "") {
        $ret .= 'action="' . $scriptname . $querystring . '" ';
    }
    $ret .= 'method="post">' . "\n";
    $ret .= '<p style="display: none;"><input type="hidden" name="formtoken" value="' . generate_form_token($form_id) . '"></p>' . "\n";
    $ret .= $content;
    $ret .= '</form>';
    return $ret;
}


function html_select($name, $options, $default = '', $free = '')
{
    require_once('inc/security.php');
    $ret = "<select name=\"{$name}\" id=\"{$name}\" size=\"1\" {$free} >\n";
    foreach ($options as $key => $value) {
        $selected = '';
        if ($default == $key) {
            $selected = ' selected="selected" ';
        }
        $key = filter_output_html($key);
        $value = filter_output_html($value);
        $ret .= "  <option value=\"{$key}\"{$selected}>{$value}</option>\n";
    }
    $ret .= '</select>';
    return $ret;
}


function get_modules_info()
{
    $modules = config('modules');
    $modconfig = [];
    foreach ($modules as $name) {
        $modconfig[$name] = null;
        if (file_exists('modules/' . $name . '/module.info')) {
            $modconfig[$name] = parse_ini_file('modules/' . $name . '/module.info');
        }
    }
    return $modconfig;
}


function send_mail($address, $subject, $body, $msgtype = "adminmail")
{
    if (strstr($subject, "\n") !== false) {
        die("Zeilenumbruch im subject!");
    }
    if (config("smtp_server")) {
        // If we have smtp credentials we use phpmailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = config("smtp_server");
            $mail->SMTPAuth = true;
            $mail->Username = config("smtp_user");
            $mail->Password = config("smtp_pass");
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            $mail->setFrom(config("adminmail"), config('company_name') . " Web Administration");
            $mail->addAddress($address);
            if ($address !== config('adminmail')) {
                $mail->addCC(config('adminmail'));
            }
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->addCustomHeader("X-schokokeks-org-message", $msgtype);
            $mail->XMailer = ' ';
            $mail->send();
        } catch (Exception $e) {
            $adminmsg = "PHPMailer error:\n" . $mail->ErrorInfo . "\n\n";
            $adminmsg .= "SERVER info:\n" . print_r($_SERVER, 1);
            mail(config('adminmail'), $_SERVER['SERVER_NAME'] . ": error sending mail", $adminmsg);
            system_failure("Mail konnte nicht verschickt werden, die Administratoren werden informiert.");
        }
    } else {
        $header = [];
        $header["From"] = config('company_name') . " Web Administration <" . config('adminmail') . ">";
        if ($address !== config('adminmail')) {
            $header["Cc"] = config('adminmail');
        }
        $header["X-schokokeks-org-message"] = $msgtype;
        $header["Content-Type"] = "text/plain; charset=\"utf-8\"";
        $header["Content-Transfer-Encoding"] = "quoted-printable";
        $header["MIME-Version"] = "1.0";
        $subject = mb_encode_mimeheader($subject, "utf-8", "Q");
        $body = quoted_printable_encode($body);
        mail($address, $subject, $body, $header);
    }
}

function handle_exception($e)
{
    if (config('enable_debug')) {
        print_r($e->getMessage() . "<br>");
        debug_print_backtrace();
        echo("<br>");
        print_r(serialize($_POST) . "<br>");
        print_r(serialize($_SERVER));
    } else {
        $msg = "Exception caught:\n" . $e->getMessage() . "\n" . serialize($_POST) . "\n" . serialize($_SERVER);
        send_mail(config("adminmail"), "Exception on configinterface", $msg);
    }
}
