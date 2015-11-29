<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

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


if ($_GET['action'] == 'edit')
{
  check_form_token('vhosts_edit_vhost');
  $id = (int) $_GET['vhost'];
  $vhost = empty_vhost();
  if ($id != 0)
    $vhost = get_vhost_details( $id );
  DEBUG($vhost);

  $hostname = filter_input_hostname($_POST['hostname'], true);

  $domainname = NULL;
  $domain_id = (int) $_POST['domain'];
  if ($domain_id >= 0) {
    $domain = new Domain( (int) $_POST['domain'] );
    $domain->ensure_userdomain();
    $domain_id = $domain->id;
    $domainname = $domain->fqdn;
  }
  elseif ($domain_id == -1) {
    # use configured user_vhosts_domain
    $userdomain = userdomain();
    $domain = new Domain( (int) $userdomain['id'] );
    $domain_id = $domain->id;
    $domainname = $domain->fqdn;
    $hostname = $hostname.'.'.$_SESSION['userinfo']['username'];
    $hostname = trim($hostname, " .-");
  }
  elseif ($domain_id == -2) {
    # use system masterdomain
    $domainname = $_SESSION['userinfo']['username'].".".config('masterdomain');
  }

  if (! (isset($_POST['options']) && is_array($_POST['options'])))
    $_POST['options'] = array();
  $aliaswww = in_array('aliaswww', $_POST['options']);

  $docroot = '';
  if ($_POST['vhost_type'] == 'regular' || $_POST['vhost_type'] == 'dav')
  {
    $defaultdocroot = $vhost['homedir'].'/websites/'.((strlen($hostname) > 0) ? $hostname.'.' : '').($domainname).'/htdocs';
  
    $docroot = '';
    if (isset($_POST['docroot']))
    {
      if (! check_path( $_POST['docroot'] ))
        system_failure("Eingegebener Pfad enthält ungültige Angaben");
      $docroot = $vhost['homedir'].'/websites/'.$_POST['docroot'];
    }
    if ((isset($_POST['use_default_docroot']) && $_POST['use_default_docroot'] == '1') || ($docroot == $defaultdocroot)) {
      $docroot = NULL;
    }
  
    DEBUG("Document-Root: ".$docroot);
  }
  $php = NULL;
  if ($_POST['vhost_type'] == 'regular' && isset($_POST['php']))
  {
    switch ($_POST['php']) {
      case 'php54':
        $php = 'php54';
        break;
      case 'php55':
        $php = 'php55';
        break;
      case 'php56':
        $php = 'php56';
        break;
      case 'php70':
        $php = 'php70';
        break;
      /* Wenn etwas anderes kommt, ist das "kein PHP". So einfach ist das. */
    }
  }
  $cgi = 1;
  if (isset($_POST['safemode']) && $_POST['safemode'] == 'yes')
  {
    $cgi = 0;
  }

  if (isset($_POST['suexec_user']))
    $vhost['suexec_user'] = $_POST['suexec_user'];

  if (isset($_POST['server']))
    $vhost['server'] = $_POST['server'];

  if ($_POST['vhost_type'] == 'regular') {
    $vhost['is_dav'] = 0;
    $vhost['is_svn'] = 0;
    $vhost['is_webapp'] = 0;
  }
  elseif ($_POST['vhost_type'] == 'dav') {
    $vhost['is_dav'] = 1;
    $vhost['is_svn'] = 0;
    $vhost['is_webapp'] = 0;
  }
  elseif ($_POST['vhost_type'] == 'svn') {
    $vhost['is_dav'] = 0;
    $vhost['is_svn'] = 1;
    $vhost['is_webapp'] = 0;
  }
  elseif ($_POST['vhost_type'] == 'webapp') {
    $vhost['is_dav'] = 0;
    $vhost['is_svn'] = 0;
    $vhost['is_webapp'] = 1;
    $vhost['webapp_id'] = (int) $_POST['webapp'];
  }

  
  $ssl = NULL;
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

  $hsts = NULL;
  if (isset($_POST['hsts'])) {
    if (is_numeric($_POST['hsts']) && (int) $_POST['hsts'] > -2) {
      $hsts = (int) $_POST['hsts'];
    } else {
      system_failure('Es wurde ein ungültiger HSTS-Wert eingegeben. Dort sind nur Sekunden erlaubt.');
    }
  }
 
  $cert = (isset($_POST['cert']) ? (int) $_POST['cert'] : NULL);

  $ipv4 = (isset($_POST['ipv4']) ? $_POST['ipv4'] : NULL);

  if (isset($_POST['ipv6']) && $_POST['ipv6'] == 'yes')
  {
    $vhost['autoipv6'] = 1;
    if (isset($_POST['ipv6_separate']) && $_POST['ipv6_separate'] = 'yes')
    {
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
  if (isset($_POST['errorlog']) and ($_POST['errorlog'] == 1))
    $errorlog = 1;


  if (isset($_POST['stats']) && $_POST['stats'] == 1)
  {
    if ($vhost['stats'] == NULL)
      $vhost['stats'] = 'private';
  }
  else
    $vhost['stats'] = NULL;

  if ($logtype == '')
    $vhost['stats'] = NULL;
  
  DEBUG("PHP: {$php} / Logging: {$logtype}");

  $old_options = explode(',', $vhost['options']);
  $new_options = array();
  foreach ($old_options AS $op)
  {
    if ($op != 'aliaswww') {
      array_push($new_options, $op);
    }
  }
  if ($aliaswww){
    array_push($new_options, 'aliaswww');
  }
  if ($cert == -1) {
    array_push($new_options, 'letsencrypt');
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

  if (! $debugmode)
    header('Location: vhosts');

}
elseif ($_GET['action'] == 'addalias')
{
  check_form_token('vhosts_add_alias');
  $id = (int) $_GET['vhost'];
  $vhost = get_vhost_details( $id );
  DEBUG($vhost);

  $alias = empty_alias();
  $alias['vhost'] = $vhost['id'];

  
  $hostname = filter_input_hostname($_POST['hostname'], true);
  $domainid = (int) $_POST['domain'];
  if ($domainid >= 0) {
    $domain = new Domain( (int) $_POST['domain'] );
    $domain->ensure_userdomain();
    $domainid = $domain->id;
  }
  if ($domainid == -1) {
    # use configured user_vhosts_domain
    $userdomain = userdomain();
    $domain = new Domain( (int) $userdomain['id'] );
    $domainid = $domain->id;
    $hostname = $hostname.'.'.$_SESSION['userinfo']['username'];
    $hostname = trim($hostname, " .-");
  }

  if (! is_array($_POST['options']))
    $_POST['options'] = array();
  $aliaswww = in_array('aliaswww', $_POST['options']);
  $forward = in_array('forward', $_POST['options']);

  $new_options = array();
  if ($aliaswww)
    array_push($new_options, 'aliaswww');
  if ($forward)
    array_push($new_options, 'forward');
  DEBUG($new_options);
  $options = implode(',', $new_options);
  DEBUG('New options: '.$options);

  $alias['hostname'] = $hostname;
  $alias['domain_id'] = $domainid;
    
  $alias ['options'] = $options;
    
  save_alias($alias);

  if (! $debugmode)
    header('Location: aliases?vhost='.$vhost['id']);

}
elseif ($_GET['action'] == 'deletealias')
{
  $title = "Subdomain löschen";
  $section = 'vhosts_vhosts';
  
  $alias = get_alias_details( (int) $_GET['alias'] );
  DEBUG($alias);
  $alias_string = $alias['fqdn'];
  
  $vhost = get_vhost_details( $alias['vhost'] );
  DEBUG($vhost);
  $vhost_string = $vhost['fqdn'];
  
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=deletealias&alias={$_GET['alias']}", "Möchten Sie das Alias »{$alias_string}« für die Subdomain »{$vhost_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_alias($alias['id']);
    if (! $debugmode)
      header('Location: aliases?vhost='.$vhost['id']);
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header('Location: aliases?vhost='.$vhost['id']);
  }
}
elseif ($_GET['action'] == 'delete')
{
  $title = "Subdomain löschen";
  $section = 'vhosts_vhosts';
  
  $vhost = get_vhost_details( (int) $_GET['vhost'] );
  $vhost_string = $vhost['fqdn'];
  
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&vhost={$_GET['vhost']}", "Möchten Sie die Subdomain »{$vhost_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_vhost($vhost['id']);
    if (! $debugmode)
      header("Location: vhosts");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: vhosts");
  }
}
else
  system_failure("Unimplemented action");

output('');


?>
