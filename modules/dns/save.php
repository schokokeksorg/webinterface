<?php

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dyndns';

$id = NULL;
if ($_REQUEST['id'])
  $id = (int) $_REQUEST['id'];


if ($_GET['type'] == 'dyndns') {
  if ($_GET['action'] == 'delete') {
    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=dyndns&action=delete&amp;id={$id}", "Möchten Sie den DynDNS-Account wirklich löschen?");
    }
    elseif ($sure === true)
    {
      delete_dyndns_account($id);
      if (! $debugmode)
        header("Location: dyndns.php");
    }
    elseif ($sure === false)
    {
      if (! $debugmode)
        header("Location: dyndns.php");
    }
  }
  if ($_GET['action'] == 'edit') {
    check_form_token('dyndns_edit');
    
    if ($id) {
      edit_dyndns_account($id, $_POST['handle'], $_POST['password_http'], $_POST['sshkey']);
    } else {
      create_dyndns_account($_POST['handle'], $_POST['password_http'], $_POST['sshkey']);
    }
  
    if (! ($debugmode || we_have_an_error()))
      header('Location: dyndns.php');
  }
}




