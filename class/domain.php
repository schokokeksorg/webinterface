<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');


class Domain
{
    protected $data = array();
  function __construct($init = NULL)
  {
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

  function __set($key, $value)
  {
    if (array_key_exists($key, $this->data)) {
      $this->data[$key] = $value;
    } elseif (isset($this->$key)) {
      $this->$key = $value;
    } else {
      $this->data[$key] = $value;
    }
  }


  function __get($key)
  {
    if (array_key_exists($key, $this->data))
      return $this->data[$key];
    elseif (isset($this->$key))
      return $this->$key;
    // else werfe fehler
  }


  function loadByID($id)
  {
      $res = db_query("SELECT * FROM kundendaten.domains WHERE id=?", array($id));
      if ($res->rowCount() < 1)
         return false;
      $data = $res->fetch();
      $this->parse($data);
  }

   function loadByName($name)
  {
      $res = db_query("SELECT * FROM kundendaten.domains WHERE CONCAT_WS('.', domainname, tld)=?", array($name));
      if ($res->rowCount() < 1)
         return false;
      $data = $res->fetch();
      $this->parse($data);
  }

  function ensure_customerdomain()
  {
    if (! $this->is_customerdomain() )
      system_failure('Diese Domain gehört nicht Ihrem Kundenaccount.');
  }

  function ensure_userdomain()
  {
    if (! $this->is_userdomain() )
      system_failure('Diese Domain gehört nicht Ihrem Benutzeraccount.');
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

  function setup()
  {
    $fields = array();
    $res = db_query("DESCRIBE kundendaten.domains");
    while ($f = $res->fetch(PDO::FETCH_OBJ))
    {
      $fields[$f->Field] = $f->Default;
    }
    $this->data = $fields;
    $this->data['id'] = NULL;
  }



  function parse($data)
  {
    DEBUG($data);
    foreach (array_keys($this->data) as $key)
      if (array_key_exists($key, $data))
        $this->data[$key] = $data[$key];
    $this->data['fqdn'] = $data['domainname'].'.'.$data['tld'];
    $this->data['punycode'] = idn_to_ascii($this->data['fqdn'], 0, INTL_IDNA_VARIANT_UTS46);
    $this->data['is_idn'] = ($this->data['fqdn'] != $this->data['punycode']);
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

