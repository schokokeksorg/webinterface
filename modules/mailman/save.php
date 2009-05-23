<?php

require_once('mailman.php');
require_role(ROLE_SYSTEMUSER);

$title = "Neue Mailingliste erstellen";
$domains = get_mailman_domains();

$maildomains = array('0' => config('mailman_host'));
foreach ($domains AS $domain)
{
  $maildomains[$domain['id']] = $domain['fqdn'];
}


if ($_GET['action'] == 'new')
{
  $maildomain = $_POST['maildomain'];
  if ($maildomain == 0)
    $maildomain = NULL;
  else
    if (! isset($maildomains[$maildomain]))
      system_failure('Ihre Domain-Auswahl scheint ungültig zu sein');

  create_list($_POST['listname'], $maildomain, $_POST['admin']);
  if (! $debugmode)
    header('Location: lists');
}

elseif ($_GET['action'] == 'delete')
  $list = get_list($_GET['id']);
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure('action=delete&id='.$list['id'], 'Möchten Sie die Mailingliste »<strong>'.$list['listname'].'</strong>@'.$list['fqdn'].'« wirklich löschen?');
  }
  elseif ($sure === true)
  {
    delete_list($list['id']);
    if (! $debugmode)
      header('Location: lists');
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header('Location: lists');
  }

else
{
  system_failure('Function not implemented');
}


