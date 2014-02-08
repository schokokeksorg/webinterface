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

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dyndns';

$id = NULL;
if (isset($_REQUEST['id']))
  $id = (int) $_REQUEST['id'];


if ($_GET['type'] == 'dyndns') {
  if ($_GET['action'] == 'delete') {
    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=dyndns&action=delete&id={$id}", "Möchten Sie den DynDNS-Account wirklich löschen?");
    }
    elseif ($sure === true)
    {
      delete_dyndns_account($id);
      if (! $debugmode)
        header("Location: dyndns");
    }
    elseif ($sure === false)
    {
      if (! $debugmode)
        header("Location: dyndns");
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
      header('Location: dyndns');
  }
}




