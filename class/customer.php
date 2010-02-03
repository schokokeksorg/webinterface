<?php

require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/debug.php');

require_once('class/keksdata.php');


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
    if (! $this->data['email_rechnung'])
      $this->data['email_rechnung'] = $this->data['email'];
  }

}


