<?php

function whitelist_entries() 
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$res = db_query("SELECT id,local,domain,date,expire FROM mail.greylisting_manual_whitelist WHERE uid={$uid};");
	$return = array();
	while ($line = mysql_fetch_assoc($res))
		array_push($return, $line);
	return $return;
}


function get_whitelist_details($id)
{
	$id = (int) $id;
	$uid = (int) $_SESSION['userinfo']['uid'];
	$res = db_query("SELECT id,local,domain,date,expire FROM mail.greylisting_manual_whitelist WHERE uid={$uid} AND id={$id};");
	if (mysql_num_rows($res) != 1)
		system_failure('Kann diesen Eintrag nicht finden');
	return mysql_fetch_assoc($res);
}


function delete_from_whitelist($id)
{
	$id = (int) $id;
	// Check if the ID is valid: This will die if not.
	$entry = get_whitelist_details($id);

	db_query("DELETE FROM mail.greylisting_manual_whitelist WHERE id={$id} LIMIT 1;");
}


function valid_entry($local, $domain)
{
	if ($domain == 'schokokeks.org')
	{
		if (($local != $_SESSION['userinfo']['username']) && 
		    (strpos($local, $_SESSION['userinfo']['username'].'-') !== 0))
			system_failure('Diese E-Mail-Adresse gehört Ihnen nicht!');
		return true;
	}
	$d = mysql_real_escape_string($domain);
	$res = db_query("SELECT id FROM mail.v_domains WHERE domainname='{$d}' AND user={$_SESSION['userinfo']['uid']} LIMIT 1");
	if (mysql_num_rows($res) != 1)
		system_failure('Diese domain gehört Ihnen nicht!');
	return true;
}


function new_whitelist_entry($local, $domain, $minutes)
{
	valid_entry($local, $domain);
	$uid = (int) $_SESSION['userinfo']['uid'];
	$local = maybe_null($local);
	$domain = mysql_real_escape_string($domain);
	
	$expire = '';
	if ($minutes == 'none')
		$expire = 'NULL';
	else
		$expire = "NOW() + INTERVAL ". (int) $minutes ." MINUTE";
	db_query("INSERT INTO mail.greylisting_manual_whitelist (local,domain,date,expire,uid) VALUES ".
	         "({$local}, '{$domain}', NOW(), {$expire}, $uid);");
}


?>
