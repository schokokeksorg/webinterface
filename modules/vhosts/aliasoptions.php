<?php

require_once('inc/base.php');
require_once('vhosts.php');

require_once('inc/debug.php');
global $debugmode;

check_form_token('aliases_toggle', $_GET['formtoken']);

if (isset($_GET['aliaswww'])) {

  $aliaswww = (bool) ( (int) $_GET['aliaswww'] );

  $alias = get_alias_details($_GET['alias']);
  DEBUG($alias);
  $old_options = explode(',', $alias['options']);
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
  $alias['options'] = implode(',', $new_options);
  DEBUG('New options: '.$options);

  $alias['domainid'] = $alias['domain_id'];
  save_alias($alias);

  if (! $debugmode)
    header('Location: aliases.php?vhost='.$alias['vhost']);
}
if (isset($_GET['forward'])) {

  $forward = (bool) ( (int) $_GET['forward'] );

  $alias = get_alias_details($_GET['alias']);
  DEBUG($alias);
  $old_options = explode(',', $alias['options']);
  $new_options = array();
  foreach ($old_options AS $op)
  {
    if ($op != 'forward')
      array_push($new_options, $op);
  }
  if ($forward)
    array_push($new_options, 'forward');
  
  DEBUG($old_options);
  DEBUG($new_options);
  $alias['options'] = implode(',', $new_options);
  DEBUG('New options: '.$options);

  $alias['domainid'] = $alias['domain_id'];
  save_alias($alias);

  if (! $debugmode)
    header('Location: aliases.php?vhost='.$alias['vhost']);
}



?>