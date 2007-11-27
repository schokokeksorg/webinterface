<?php

function user_has_vmail_domain() 
{
	$role = $_SESSION['role'];
	if (! ($role & ROLE_SYSTEMUSER)) {
		return false;
	}
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT COUNT(*) FROM mail.v_vmail_domains WHERE useraccount='{$uid}'");
	$row = mysql_fetch_array($result);
	$count = $row[0];
	DEBUG("User has {$count} vmail-domains");
	return ( (int) $count > 0 );
}


?>
