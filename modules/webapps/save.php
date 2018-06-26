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

require_once('inc/security.php');

require_once('modules/vhosts/include/vhosts.php');
require_once('class/domain.php');

$url = '';
$docroot = '';

if ($_POST['target'] == 'new') {
    check_form_token('webapp_install');
    $vhost = empty_vhost();

    $hostname = filter_input_hostname($_POST['hostname']);

    $domainid = (int) $_POST['domain'];
    $domainname = null;
    if ($domainid != -1) {
        $domain = new Domain((int) $_POST['domain']);
        $domain->ensure_userdomain();
        $domainid = $domain->id;
        $domainname = $domain->fqdn;
    }
    DEBUG('Domain: '.$domainid.' / '.$domainname);

    if (! isset($_POST['options']) || ! is_array($_POST['options'])) {
        $_POST['options'] = array();
    }
    $aliaswww = in_array('aliaswww', $_POST['options']);

    $vhost['is_dav'] = 0;
    $vhost['is_svn'] = 0;
    $vhost['is_webapp'] = 0;
  
    $ssl = '';
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

    DEBUG("Logging: {$logtype}");

    $old_options = explode(',', $vhost['options']);
    $new_options = array();
    foreach ($old_options as $op) {
        if ($op != 'aliaswww') {
            array_push($new_options, $op);
        }
    }
    if ($aliaswww) {
        array_push($new_options, 'aliaswww');
    }

    DEBUG($old_options);
    DEBUG($new_options);
    $options = implode(',', $new_options);
    DEBUG('New options: '.$options);

    $vhost['hostname'] = $hostname;
    $vhost['domain_id'] = $domainid;
    $vhost['domain'] = $domainname;
    $vhost['docroot'] = '';
    $vhost['php'] = 'php56';
    $vhost['ssl'] = $ssl;
    $vhost['logtype'] = $logtype;
    $vhost['errorlog'] = $errorlog;
    $vhost['options'] = $options;
  
    $domain = $domainname;
    if ($domainid == -1) {
        $domain = $_SESSION['userinfo']['username'].'.'.config('masterdomain');
    }

    $url = ($ssl == 'forward' || $ssl == 'https' ? 'https://' : 'http://').($aliaswww ? 'www.' : '').((strlen($hostname) > 0) ? $hostname.'.' : '').$domain;
    $docroot = $vhost['homedir'].'/websites/'.((strlen($hostname) > 0) ? $hostname.'.' : '').($domain).'/htdocs';
    DEBUG($vhost);
    DEBUG("New Vhost: {$url} / {$docroot}");
    save_vhost($vhost);
} elseif ($_POST['target'] == 'vhost') {
    $docroot = $_POST['vhost'];

    $vhosts = list_vhosts();
    foreach ($vhosts as $vhost) {
        if ($docroot == $vhost['docroot']) {
            $url = $vhost['fqdn'];
            if (strstr($vhost['options'], 'aliaswww')) {
                $url = 'www.'.$url;
            }

            if ($vhost['ssl'] == 'forward' || $vhost['ssl'] == 'https') {
                $url = 'https://'.$url;
            } else {
                $url = 'http://'.$url;
            }
        }
    }
    if (! $url) {
        system_failure('Datenchaos, so geht das nicht.');
    }
    DEBUG("Existing Vhost: {$url} / {$docroot}");
} else {
    input_error('Fehler im System');
}

if ($docroot && $url) {
    $application = $_POST['application'];
    if (! $application) {
        system_failure('Keine Web-Anwendung ausgewählt');
    }

    if (! check_path($application)) {
        system_failure('HTML-Krams im Namen der Anwendung');
    }

    if (! file_exists(dirname(__FILE__).'/install/'.$application.'.php')) {
        system_failure('Unbekannte Web-Anwendung.');
    }

    $_SESSION['webapp_docroot'] = $docroot;
    $_SESSION['webapp_url'] = $url;
  
    if (!$debugmode) {
        header('Location: install/'.$application);
    }
}
