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
  $result = db_query($query);
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



function get_domain_name($domid)
{
  if ($domid === NULL)
    return 'schokokeks.org';
  $domid = (int) $domid;
  static $domainlist = array();

  $result = db_query("SELECT CONCAT_WS('.', domainname, tld) AS domainname FROM kundendaten.domains WHERE id=$domid;");
  if (@mysql_num_rows($result) > 0)
    return mysql_fetch_object($result)->domainname;
  else
    return NULL;

}


function get_jabberable_domains()
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT id, CONCAT_WS('.', domainname, tld) AS name FROM kundendaten.domains WHERE jabber=1 AND kunde={$customerno}");
  
  $domains = array(array('id' => 0, 'name' => 'schokokeks.org'));
  if (mysql_num_rows($result) > 0)
    while ($domain = mysql_fetch_object($result))
      array_push($domains, array('id' => $domain->id,
                                'name' => $domain->name));

  return $domains;

}

?>
