<?php

require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/debug.php');

require_once('class/keksdata.php');


class Domain extends KeksData
{
  function __construct($init = NULL)
  {
    $this->default_table = 'kundendaten.domains';
    $this->setup();
    switch (gettype($init))
    {
      case 'string':
        $this->loadByName($init);
        break;
      case 'integer':
        $this->loadByID($init);
        break;
      case 'NULL':
        break;
    }
  }

  function loadByName($name)
  {
    $name = mysql_real_escape_string($name);
    $res = $this->getData("*", "CONCAT_WS('.', domainname, tld)='{$name}' LIMIT 1");
    if (count($res) < 1)
      return false;
    $this->parse($res[0]);
  }

  function parse($data)
  {
    foreach (array_keys($this->data) as $key)
      if (array_key_exists($key, $data))
        $this->data[$key] = $data[$key];
    $this->data['fqdn'] = $data['domainname'].'.'.$data['tld'];
    $this->data['reg_date'] = $data['registrierungsdatum'];
    $this->data['cancel_date'] = $data['kuendigungsdatum'];
  }

}







function get_domain_list($customerno, $uid = NULL)
{
  $customerno = (int) $customerno;
  $query = "SELECT id FROM kundendaten.domains WHERE";
  if ($uid !== NULL)
  {
    $uid = (int) $uid;
    $query .= " useraccount={$uid}";
  }
  else
  {
    $query .= " kunde={$customerno}";
  }
  $query .= " ORDER BY domainname,tld";
  $result = db_query($query);
  $domains = array();
  DEBUG('Result set is '.mysql_num_rows($result)." rows.<br />\n");
  if (mysql_num_rows($result) > 0)
    while ($domain = mysql_fetch_object($result)->id)
      array_push($domains, new Domain((int) $domain));
  DEBUG($domains);
	return $domains;	
}



function get_jabberable_domains()
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];

  $domains = get_domain_list($customerno);
  DEBUG($domains);
  $result = array( new Domain() );
  $result[0]->id = 0;
  $result[0]->fqdn = "schokokeks.org";
  foreach ($domains as $dom)
  {
    if ($dom->jabber)
      $result[] = $dom;
  }
  return $result;

}

?>
