<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

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
    $name = db_escape_string($name);
    $res = $this->getData("*", "CONCAT_WS('.', domainname, tld)='{$name}' LIMIT 1");
    if (count($res) < 1)
      return false;
    $this->parse($res[0]);
  }

  function ensure_customerdomain()
  {
    if (! $this->is_customerdomain() )
      system_failure('Die Domain »'.$this->data['fqdn'].'« gehört nicht Ihrem Kundenaccount.');
  }

  function ensure_userdomain()
  {
    if (! $this->is_userdomain() )
      system_failure('Die Domain »'.$this->data['fqdn'].'« gehört nicht Ihrem Benutzeraccount.');
  }

  function is_customerdomain()
  {
    if (! isset($_SESSION['customerinfo']) )
      return false;
    $customerno = (int) $_SESSION['customerinfo']['customerno'];
    return ($this->kunde == $customerno);
  }

  function is_userdomain()
  {
    if (! isset($_SESSION['userinfo']) )
      return false;
    $uid = (int) $_SESSION['userinfo']['uid'];
    return ($this->useraccount == $uid);
  }

  function parse($data)
  {
    DEBUG($data);
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
  $result = db_query($query, array()); // FIXME Übergebe leeren array um die Warnung zu unterdrücken
  $domains = array();
  DEBUG('Result set is '.$result->rowCount()." rows.<br />\n");
  if ($result->rowCount() > 0)
    while ($domain = $result->fetch(PDO::FETCH_OBJ))
      array_push($domains, new Domain((int) $domain->id));
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
  $result[0]->fqdn = config('masterdomain');
  foreach ($domains as $dom)
  {
    if ($dom->jabber)
      $result[] = $dom;
  }
  return $result;

}

?>
