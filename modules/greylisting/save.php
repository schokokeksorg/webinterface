<?php
require_once('inc/debug.php');
require_once('inc/security.php');

require_once('greylisting.php');


if ($_GET['action'] == 'delete')
{
  $entry = get_whitelist_details($_GET['id']);
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&amp;id={$entry['id']}", "Möchten Sie die E-Mail-Adresse »{$entry['local']}@{$entry['domain']}« von der Ausnahmeliste entfernen?");
  }
  elseif ($sure === true)
  {
    delete_from_whitelist($entry['id']);
    if (! $debugmode)
      header("Location: whitelist");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: whitelist");
  }
}
elseif ($_GET['action'] == 'add')
{
	check_form_token('greylisting_add');
	if ( !filter_var($_POST['address'], FILTER_VALIDATE_EMAIL )
		&& !filter_var("x@".$_POST['address'], FILTER_VALIDATE_EMAIL) )
		system_failure("Sie haben eine ungültige Mailadresse eingegeben.");
	$local = false;
	$domain = '';
	$at = strrpos($_POST['address'], '@');
	if ($at === false)
		$domain = $_POST['address'];
	else
	{
		$local = substr($_POST['address'], 0, $at);
		$domain = substr($_POST['address'], $at+1);
	}
	DEBUG("Whitelisting {$local}@{$domain} for {$_POST['expire']} minutes");
	new_whitelist_entry($local, $domain, $_POST['expire']);
	if (! $debugmode)	
		header("Location: whitelist");

}

?>
