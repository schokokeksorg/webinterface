<?php

require_once('inc/db_connect.php');
require_once('inc/debug.php');

function get_domain_names($customerno, $uid = NULL)
{
  $customerno = (int) $customerno;
  $query = "SELECT id,CONCAT_WS('.', domainname, tld) AS domainname, registrierungsdatum, kuendigungsdatum FROM kundendaten.domains WHERE";
  if ($uid !== NULL)
  {
    $uid = (int) $uid;
    $query .= " useraccount={$uid};";
  }
  else
  {
    $query .= " kunde={$customerno};";
  }
  DEBUG('Datenbank-Query (get_domain_names): '.$query."<br />\n");

  $result = @mysql_query($query);
  if (@mysql_error())
    system_failure('Die Domains zu Ihrem Account konnten nicht ermittelt werden. Bitte melden Sie diesen Fehler an einen Administrator. Die Fehlermeldung der Datenbank ist: '.mysql_error());

  $domains = array();
  DEBUG('Result set is '.mysql_num_rows($result)." rows.<br />\n");
  if (mysql_num_rows($result) > 0)
    while ($domain = mysql_fetch_object($result))
      array_push($domains, array('id' => $domain->id,
                              'domainname'  => $domain->domainname,
                              'reg_date' => $domain->registrierungsdatum,
                              'cancel_date' => $domain->kuendigungsdatum));

	return $domains;	
}


/*
function get_mail_virtualdomain($domain)
{
	$config = array();
	$lines = file('/home/webadmin/cache/virtualdomains');
	foreach ($lines as $line)
	{
		$line = chop($line);
		$fields = explode(':', $line, 3);
		if ($fields[0] == $domain)
			array_push($config, array('subdomain' => '', 
						'user' => $fields[1],
						'prefix' => $fields[2]));
		if (ereg('^.*\.'.$domain, $fields[0]))
			array_push($config, array('subdomain' => ereg_replace('^(.*)\.'.$domain, '\1', $fields[0]),
						'user' => $fields[1],
						'prefix' => $fields[2]));
	}
	return $config;
}
*/



?>
