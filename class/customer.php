<?php

require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/debug.php');

require_once('class/keksdata.php');


class ContactMethod extends KeksData
{
  function __construct($init = NULL)
  {
    $this->default_table = 'kundendaten.kundenkontakt';
    $this->setup();
    if ($init != NULL)
      switch (gettype($init))
      {
        case 'string':
          $this->loadByAddress($init);
          break;
        case 'integer':
          $this->loadByID($init);
          break;
      }
  }


  function loadByAddress($name)
  {
    $name = mysql_real_escape_string($name);
    DEBUG("Requested to load ContactMethod-object for address »{$name}«");
    $res = $this->getData("*", "wert='{$name}' LIMIT 1");
    if (count($res) < 1)
    {
      DEBUG('nothing found');
      return false;
    }
    $this->parse($res[0]);
    return true;
  }


  function loadByCustomer($cid, $comment = '')
  {
    $cid = (int) $cid;
    $comment = mysql_real_escape_string($comment);
    DEBUG("Requested to load ContactMethod-object for customer »{$cid}« (comment = {$comment})");
    $res = $this->getData("*", "kundennr='{$cid}' AND (comment='{$comment}' OR (comment IS NULL AND '{$comment}'='')) LIMIT 1");
    if (count($res) < 1)
    {
      DEBUG('nothing found');
      return false;
    }
    $this->parse($res[0]);
    return true;
  }
  
  function parse($data)
  {
    foreach (array_keys($this->data) as $key)
      if (array_key_exists($key, $data))
        $this->data[$key] = $data[$key];
  }

}



class Customer extends KeksData
{
  function __construct($init = NULL)
  {
    $this->default_table = 'kundendaten.kunden';
    $this->setup();
    if ($init != NULL)
      $this->loadByID( (int) $init);
  }

  function parse($data)
  {
    foreach (array_keys($this->data) as $key)
      if (array_key_exists($key, $data))
        $this->data[$key] = $data[$key];
    $this->data['fullname'] = $data['vorname'].' '.$data['nachname'];
    if ($this->data['fullname'] == ' ')
      $this->data['fullname'] = $data['firma'];
    $this->data['email'] = new ContactMethod();
    $this->data['email']->loadByCustomer($this->data['id']);
    $this->data['email_rechnung'] = new ContactMethod();
    if (! $this->data['email_rechnung']->loadByCustomer($this->data['id'], 'rechnung'))
      $this->data['email_rechnung'] = $this->data['email'];
    $this->data['email_extern'] = new ContactMethod();
    if (! $this->data['email_extern']->loadByCustomer($this->data['id'], 'extern'))
      $this->data['email_extern'] = $this->data['email'];
  }

}


