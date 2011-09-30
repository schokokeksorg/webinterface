<?php
include('ftpusers.php');

require_role(ROLE_SYSTEMUSER);

if (isset($_GET['regular_ftp']))
{
  check_form_token('regular_ftp', $_REQUEST['token']);
  if ($_GET['regular_ftp'] == 'yes')
    enable_regular_ftp();
  else
    disable_regular_ftp();
  redirect('accounts'); 
}


if (isset($_GET['delete']))
{
  $ftpuser = load_ftpuser($_GET['delete']);

  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("delete={$ftpuser['id']}", "Möchten Sie den FTP-Zugang »{$ftpuser['username']}« wirklich löschen?");
    return;
  }
  elseif ($sure === true)
  {
    delete_ftpuser($ftpuser['id']);
  }
  redirect('accounts');
}

$ftpuser = empty_ftpuser();

if (isset($_GET['id']))
{
  check_form_token('ftpusers_edit');
  $ftpuser = load_ftpuser($_GET['id']);
}

 
$ftpuser['username'] = $_REQUEST['ftpusername'];
$ftpuser['password'] = $_REQUEST['password'];
$ftpuser['homedir'] = $_REQUEST['homedir'];
if (isset($_REQUEST['active']))
  $ftpuser['active'] = $_REQUEST['active'];
else
  $ftpuser['active'] = 0;

if (isset($_REQUEST['forcessl']))
  $ftpuser['forcessl'] = $_REQUEST['forcessl'];
else
  $ftpuser['forcessl'] = 0;

if (isset($_REQUEST['server']))
  $ftpuser['server'] = $_REQUEST['server'];

  
save_ftpuser($ftpuser);
  
redirect('accounts');


