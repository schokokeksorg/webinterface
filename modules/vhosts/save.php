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

require_once('vhosts.php');

require_once('inc/error.php');
require_once('inc/security.php');
require_once('class/domain.php');

require_role(ROLE_SYSTEMUSER);

require_once("inc/debug.php");
global $debugmode;


if ($_GET['action'] == 'edit') {
    check_form_token('vhosts_edit_vhost');
    $id = (int) $_GET['vhost'];
    $vhost = empty_vhost();
    if ($id != 0) {
        $vhost = get_vhost_details($id);
    }
    DEBUG($vhost);

    $hostname = strtolower(trim($_POST['hostname']));

    $domainname = null;
    $domain_id = (int) $_POST['domain'];
    if ($domain_id >= 0) {
        $domain = new Domain((int) $_POST['domain']);
        $domain->ensure_userdomain();
        $domain_id = $domain->id;
        $domainname = $domain->fqdn;
    } elseif ($domain_id == -1) {
        # use configured user_vhosts_domain
        $userdomain = userdomain();
        $domain = new Domain((int) $userdomain['id']);
        $domain_id = $domain->id;
        $domainname = $domain->fqdn;
        $hostname = $hostname.'.'.$_SESSION['userinfo']['username'];
        $hostname = trim($hostname, " .-");
    } elseif ($domain_id == -2) {
        # use system masterdomain
        $domainname = $_SESSION['userinfo']['username'].".".config('masterdomain');
    }

    $aliaswww = (isset($_POST['aliaswww']) && $_POST['aliaswww'] == 'aliaswww');
    $forwardwww = null;
    if ($aliaswww && isset($_POST['forwardwww'])) {
        if ($_POST['forwardwww'] == 'forwardwww') {
            $forwardwww = 'forwardwww';
        } elseif ($_POST['forwardwww'] == 'forwardnowww') {
            $forwardwww = 'forwardnowww';
        }
    }

    $fqdn = ($hostname !== "" ? $hostname."." : "").$domainname;
    verify_input_hostname_utf8($fqdn);
    if ($aliaswww) {
        verify_input_hostname_utf8("www.".$fqdn);
    }

    $docroot = '';
    if ($_POST['vhost_type'] == 'regular' || $_POST['vhost_type'] == 'dav') {
        $defaultdocroot = $vhost['homedir'].'/websites/'.((strlen($hostname) > 0) ? $hostname.'.' : '').($domainname).'/htdocs';

        $docroot = '';
        if (isset($_POST['docroot'])) {
            if (!check_path($_POST['docroot'])) {
                system_failure("Eingegebener Pfad enthält ungültige Angaben");
            }
            $docroot = $vhost['homedir'].'/websites/'.$_POST['docroot'];
        }
        if ((isset($_POST['use_default_docroot']) && $_POST['use_default_docroot'] == '1') || ($docroot == $defaultdocroot)) {
            $docroot = null;
        }

        DEBUG("Document-Root: ".$docroot);
    }
    $php = null;
    if ($_POST['vhost_type'] == 'regular' && isset($_POST['php'])) {
        $phpinfo = valid_php_versions();
        if ($_POST['php'] == 'default' || array_key_exists($_POST['php'], $phpinfo)) {
            $php = $_POST['php'];
        } else {
            $php = null;
        }
    }
    $cgi = 1;
    if (isset($_POST['safemode']) && $_POST['safemode'] == 'yes') {
        $cgi = 0;
    }

    if (isset($_POST['suexec_user'])) {
        $vhost['suexec_user'] = $_POST['suexec_user'];
    }

    if (isset($_POST['server'])) {
        $vhost['server'] = $_POST['server'];
    }

    if ($_POST['vhost_type'] == 'regular') {
        $vhost['is_dav'] = 0;
        $vhost['is_svn'] = 0;
        $vhost['is_webapp'] = 0;
    } elseif ($_POST['vhost_type'] == 'dav') {
        $vhost['is_dav'] = 1;
        $vhost['is_svn'] = 0;
        $vhost['is_webapp'] = 0;
    } elseif ($_POST['vhost_type'] == 'svn') {
        $vhost['is_dav'] = 0;
        $vhost['is_svn'] = 1;
        $vhost['is_webapp'] = 0;
    } elseif ($_POST['vhost_type'] == 'webapp') {
        $vhost['is_dav'] = 0;
        $vhost['is_svn'] = 0;
        $vhost['is_webapp'] = 1;
        $vhost['webapp_id'] = (int) $_POST['webapp'];
    }


    $ssl = null;
    switch ($_POST['ssl']) {
        case 'http':
            $ssl = 'http';
            break;
        case 'https':
            $ssl = 'https';
            break;
        case 'forward':
            $ssl = 'forward';
            break;
            /* Wenn etwas anderes kommt, ist das "beides". So einfach ist das. */
    }

    $hsts = null;
    $hsts_subdomains = false;
    $hsts_preload = false;
    if (isset($_POST['hsts'])) {
        if (is_numeric($_POST['hsts']) && (int) $_POST['hsts'] > -2) {
            $hsts = (int) $_POST['hsts'];
        } else {
            system_failure('Es wurde ein ungültiger HSTS-Wert eingegeben. Dort sind nur Sekunden erlaubt.');
        }
        if (isset($_POST['hsts_subdomains']) and $_POST['hsts_subdomains'] == 1) {
            $hsts_subdomains = true;
            if (isset($_POST['hsts_preload']) and $_POST['hsts_preload'] == 1) {
                $hsts_preload = true;
            }
        }
    }

    $cert = (isset($_POST['cert']) ? (int) $_POST['cert'] : null);

    $ipv4 = ($_POST['ipv4'] ?? null);

    if (isset($_POST['ipv6']) && $_POST['ipv6'] == 'yes') {
        $vhost['autoipv6'] = 1;
        if (isset($_POST['ipv6_separate']) && $_POST['ipv6_separate'] = 'yes') {
            $vhost['autoipv6'] = 2;
        }
    } else {
        $vhost['autoipv6'] = 0;
    }


    $logtype = '';
    switch ($_POST['logtype']) {
        case 'anonymous':
            $logtype = 'anonymous';
            break;
        case 'default':
            $logtype = 'default';
            break;
            /* Wenn etwas anderes kommt, ist das "kein Logging". So einfach ist das. */
    }

    $errorlog = 0;
    if (isset($_POST['errorlog']) and ($_POST['errorlog'] == 1)) {
        $errorlog = 1;
    }


    DEBUG("PHP: {$php} / Logging: {$logtype}");

    $old_options = explode(',', $vhost['options']);
    if ($vhost['options'] == '') {
        $old_options = [];
    }
    $new_options = [];
    foreach ($old_options as $op) {
        if (!in_array($op, ['aliaswww', 'forwardwww', 'forwardnowww', 'hsts_subdomains', 'hsts_preload'])) {
            array_push($new_options, $op);
        }
    }
    if ($aliaswww) {
        array_push($new_options, 'aliaswww');
        if ($forwardwww) {
            array_push($new_options, $forwardwww);
        }
    }
    if ($hsts_subdomains) {
        array_push($new_options, 'hsts_subdomains');
    }
    if ($hsts_preload) {
        array_push($new_options, 'hsts_preload');
    }
    $letsencrypt = ($cert == 0 ? false : ($cert == -1 || cert_is_letsencrypt($cert)));

    if ($letsencrypt) {
        array_push($new_options, 'letsencrypt');
        if ($vhost['cert'] != 0) {
            # Wenn der User manuell von einem gültigen Cert auf "letsencrypt" umgestellt hat,
            # dann sollte das alte Cert noch so lange eingetragen bleiben bis das neue da ist.
            $cert = $vhost['cert'];
        } elseif ($cert > 0) {
            # Das Cert was der user gewählt hat, ist von Lets encrypt
            # tu nix, $cert ist schon korrekt
        } else {
            # Wenn vorher kein Zertifikat aktiv war, dann setze jetzt auch keines.
            # Der letsencrypt-Automatismus macht das dann schon.
            $cert = 0;
        }
    } else {
        # Wenn kein Letsencrypt gewünscht ist, entferne die Letsencrypt-Option
        $key = array_search('letsencrypt', $new_options);
        if ($key !== false) {
            unset($new_options[$key]);
        }
    }


    DEBUG($old_options);
    DEBUG($new_options);
    $options = implode(',', $new_options);
    DEBUG('New options: '.$options);

    $vhost['hostname'] = $hostname;
    $vhost['domain_id'] = $domain_id;
    $vhost['docroot'] = $docroot;
    $vhost['php'] = $php;
    $vhost['cgi'] = $cgi;
    $vhost['ssl'] = $ssl;
    $vhost['hsts'] = $hsts;
    $vhost['cert'] = $cert;
    $vhost['ipv4'] = $ipv4;
    $vhost['logtype'] = $logtype;
    $vhost['errorlog'] = $errorlog;
    $vhost['options'] = $options;

    DEBUG($vhost);
    save_vhost($vhost);
    success_msg("Ihre Einstellungen wurden gespeichert. Es dauert jedoch einige Minuten bis die Änderungen wirksam werden.");

    if (!$debugmode) {
        header('Location: vhosts');
    }
} elseif ($_GET['action'] == 'addalias') {
    check_form_token('vhosts_add_alias');
    $id = (int) $_GET['vhost'];
    $vhost = get_vhost_details($id);
    DEBUG($vhost);

    $alias = empty_alias();
    $alias['vhost'] = $vhost['id'];


    $hostname = strtolower(trim($_POST['hostname']));

    $domain_id = (int) $_POST['domain'];
    if ($domain_id >= 0) {
        $domain = new Domain((int) $_POST['domain']);
        $domain->ensure_userdomain();
        $domain_id = $domain->id;
        $domainname = $domain->fqdn;
    } elseif ($domain_id == -1) {
        # use configured user_vhosts_domain
        $userdomain = userdomain();
        $domain = new Domain((int) $userdomain['id']);
        $domain_id = $domain->id;
        $domainname = $domain->fqdn;
        $hostname = $hostname.'.'.$_SESSION['userinfo']['username'];
        $hostname = trim($hostname, " .-");
    } elseif ($domain_id == -2) {
        # use system masterdomain
        $domainname = $_SESSION['userinfo']['username'].".".config('masterdomain');
    }

    if (!is_array($_POST['options'])) {
        $_POST['options'] = [];
    }
    $aliaswww = in_array('aliaswww', $_POST['options']);
    $forward = in_array('forward', $_POST['options']);

    $fqdn = ($hostname !== "" ? $hostname."." : "").$domainname;
    verify_input_hostname_utf8($fqdn);
    if ($aliaswww) {
        verify_input_hostname_utf8("www.".$fqdn);
    }

    $new_options = [];
    if ($aliaswww) {
        array_push($new_options, 'aliaswww');
    }
    if ($forward) {
        array_push($new_options, 'forward');
    }
    DEBUG($new_options);
    $options = implode(',', $new_options);
    DEBUG('New options: '.$options);

    $alias['hostname'] = $hostname;
    $alias['domain_id'] = $domain_id;

    $alias ['options'] = $options;

    save_alias($alias);

    if (!$debugmode) {
        header('Location: aliases?vhost='.$vhost['id']);
    }
} elseif ($_GET['action'] == 'deletealias') {
    $title = "Website-Alias löschen";
    $section = 'vhosts_vhosts';

    $alias = get_alias_details((int) $_GET['alias']);
    DEBUG($alias);
    $alias_string = $alias['fqdn'];

    $vhost = get_vhost_details($alias['vhost']);
    DEBUG($vhost);
    $vhost_string = $vhost['fqdn'];

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=deletealias&alias={$_GET['alias']}", "Möchten Sie das Alias »{$alias_string}« für die Website »{$vhost_string}« wirklich löschen?");
    } elseif ($sure === true) {
        delete_alias($alias['id']);
        if (!$debugmode) {
            header('Location: aliases?vhost='.$vhost['id']);
        }
    } elseif ($sure === false) {
        if (!$debugmode) {
            header('Location: aliases?vhost='.$vhost['id']);
        }
    }
} elseif ($_GET['action'] == 'delete') {
    $title = "Website löschen";
    $section = 'vhosts_vhosts';

    $vhost = get_vhost_details((int) $_GET['vhost']);
    $vhost_string = $vhost['fqdn'];

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=delete&vhost={$_GET['vhost']}", "Möchten Sie die Website »{$vhost_string}« wirklich löschen?");
    } elseif ($sure === true) {
        delete_vhost($vhost['id']);
        if (!$debugmode) {
            header("Location: vhosts");
        }
    } elseif ($sure === false) {
        if (!$debugmode) {
            header("Location: vhosts");
        }
    }
} else {
    system_failure("Unimplemented action");
}

output('');
