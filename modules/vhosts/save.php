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
  $domain = new Domain( (int) $_POST['domain'] );
  if ($domain->useraccount != $_SESSION['userinfo']['uid'])
    system_failure('Ungültige Domain');


  if (! is_array($_POST['options']))
    $_POST['options'] = array();
  $aliaswww = in_array('aliaswww', $_POST['options']);

  $defaultdocroot = $vhost['homedir'].'/websites/'.((strlen($hostname) > 0) ? $hostname.'.' : '').($domain->fqdn).'/htdocs';

  if (! check_path( $_POST['docroot'] ))
    system_failure("Eingegebener Pfad enthält ungültige Angaben");
  $docroot = $vhost['homedir'].'/'.$_POST['docroot'];

  if (($_POST['use_default_docroot'] == '1') || ($docroot == $defaultdocroot)) {
    $docroot = '';
  }

  DEBUG("Document-Root: ".$docroot);

  $php = '';
  switch ($_POST['php']) {
    case 'mod_php':
      $php = 'mod_php';
      break;
    case 'fastcgi':
      $php = 'fastcgi';
      break;
    /* Wenn etwas anderes kommt, ist das "kein PHP". So einfach ist das. */
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
  $vhost['domainid'] = $domain->id;
  $vhost['docroot'] = $docroot;
  $vhost['php'] = $php;
  $vhost['logtype'] = $logtype;
    
  $vhost['options'] = $options;
    
  save_vhost($vhost);

  if (! $debugmode)
    header('Location: vhosts.php');

}
elseif ($_GET['action'] == 'delete')
{
  $vhost = get_vhost_details( (int) $_GET['vhost'] );
  $vhost_string = ((strlen($vhost['hostname']) > 0) ? $vhost['hostname'].'.' : '').$vhost['domain'];
  
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&amp;vhost={$_GET['vhost']}", "Möchten Sie die Subdomain »{$vhost_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_vhost($vhost['id']);
    if (! $debugmode)
      header("Location: vhosts.php");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: vhosts.php");
  }

  /* TODO */
}
else
  system_failure("Unimplemented action");

output('');


?>
