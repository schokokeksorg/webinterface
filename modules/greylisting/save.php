<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('greylisting.php');


if ($_GET['action'] == 'delete')
{
  $entry = get_whitelist_details($_GET['id']);
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&id={$entry['id']}", "Möchten Sie die E-Mail-Adresse »{$entry['local']}@{$entry['domain']}« von der Ausnahmeliste entfernen?");
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
