<?php

if (! function_exists("user_has_vmail_domain"))
{
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
}

if (! function_exists("user_has_dotcourier_domain"))
{
  function user_has_dotcourier_domain() 
  {
	$role = $_SESSION['role'];
	if (! ($role & ROLE_SYSTEMUSER)) {
		return false;
	}
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("select 1 from mail.custom_mappings as c left join mail.v_domains as d on (d.id=c.domain) where d.user={$uid} or c.uid={$uid} UNION ". 
            "SELECT 1 FROM mail.v_domains AS d WHERE d.user={$uid} AND d.id != ALL(SELECT domain FROM mail.virtual_mail_domains);");
  $ret = (mysql_num_rows($result) > 0);
  if ($ret)
    DEBUG("User {$uid} has dotcourier-domains");
  return $ret;
  }  
}

?>
