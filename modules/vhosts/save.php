<?php

require_once('session/start.php');

require_once('vhosts.php');

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

  $hostname = filter_input_hostname($_POST['hostname']);

  $domain_id = (int) $_POST['domain'];
  if ($domain_id != -1) {
    $domain = new Domain( (int) $_POST['domain'] );
    $domain->ensure_userdomain();
    $domain_id = $domain->id;
  }

  if (! (isset($_POST['options']) && is_array($_POST['options'])))
    $_POST['options'] = array();
  $aliaswww = in_array('aliaswww', $_POST['options']);

  $docroot = '';
  if ($_POST['vhost_type'] == 'regular' || $_POST['vhost_type'] == 'dav')
  {
    $defaultdocroot = $vhost['homedir'].'/websites/'.((strlen($hostname) > 0) ? $hostname.'.' : '').($domain->fqdn).'/htdocs';
  
    $docroot = '';
    if (isset($_POST['docroot']))
    {
      if (! check_path( $_POST['docroot'] ))
        system_failure("Eingegebener Pfad enthält ungültige Angaben");
      $docroot = $vhost['homedir'].'/websites/'.$_POST['docroot'];
    }
    if ((isset($_POST['use_default_docroot']) && $_POST['use_default_docroot'] == '1') || ($docroot == $defaultdocroot)) {
      $docroot = '';
    }
  
    DEBUG("Document-Root: ".$docroot);
  }
  $php = '';
  if ($_POST['vhost_type'] == 'regular' && isset($_POST['php']))
  {
    switch ($_POST['php']) {
      case 'mod_php':
        $php = 'mod_php';
        break;
      case 'fastcgi':
        $php = 'fastcgi';
        break;
      case 'php53':
        $php = 'php53';
        break;
      /* Wenn etwas anderes kommt, ist das "kein PHP". So einfach ist das. */
    }
  }
  $cgi = 0;
  if (isset($_POST['cgi']) && isset($_POST['cgi']) && $_POST['cgi'] == 'yes')
  {
    $cgi = 1;
  }

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

  $cert = (isset($_POST['cert']) ? (int) $_POST['cert'] : NULL);

  $ipv4 = (isset($_POST['ipv4']) ? $_POST['ipv4'] : NULL);

  if (isset($_POST['ipv6']) && $_POST['ipv6'] == 'yes')
  {
    $vhost['autoipv6'] = 1;
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
    if ($op != 'aliaswww')
      array_push($new_options, $op);
  }
  if ($aliaswww)
    array_push($new_options, 'aliaswww');

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
  $vhost['cert'] = $cert;
  $vhost['ipv4'] = $ipv4;
  $vhost['logtype'] = $logtype;
  $vhost['errorlog'] = $errorlog; 
  $vhost['options'] = $options;
    
  DEBUG($vhost);
  save_vhost($vhost);

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

  
  $hostname = filter_input_hostname($_POST['hostname']);
  $domainid = (int) $_POST['domain'];
  if ($domainid != -1) {
    $domain = new Domain( (int) $_POST['domain'] );
    $domain->ensure_userdomain();
    $domainid = $domain->id;
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
