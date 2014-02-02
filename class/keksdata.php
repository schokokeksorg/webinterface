<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');


abstract class KeksData
{
  protected $default_table;
  
  protected $raw_data = array();
  protected $data = array();
  protected $changes = array();

  function __get($key)
  {
    if (array_key_exists($key, $this->data))
      return $this->data[$key];
    elseif (isset($this->$key))
      return $this->$key;
    // else werfe fehler
  }

  function __set($key, $value)
  {
    if (array_key_exists($key, $this->raw_data))
    {
      $this->raw_data[$key] = $value;
      $this->changes[$key] = $value;
      $this->parse($this->raw_data);
    }
    elseif (array_key_exists($key, $this->data))
      $this->data[$key] = $value;
      // return false;
    elseif (isset($this->$key))
      $this->$key = $value;
    else
      $this->data[$key] = $value;
  }

  protected function setup()
  {
    $fields = array();
    $res = db_query("DESCRIBE {$this->default_table}");
    while ($f = $res->fetch(PDO::FETCH_OBJ))
    {
      $fields[$f->Field] = $f->Default;
    }
    $this->raw_data = $fields;
    $this->raw_data['id'] = NULL;
    $this->data = $fields;
    $this->data['id'] = NULL;
  }


  function getData($fields, $restriction = NULL, $table = NULL)
  {
    $where = '';
    if ($restriction)
      $where = 'WHERE '.$restriction;
    if (! $table)
      $table = $this->default_table;
    if (is_array($fields))
      $fields = implode(',', $fields);
    
    $res = db_query("SELECT {$fields} FROM {$table} {$where}", array()); // FIXME Übergebe leeren array um die Warnung zu unterdrücken
    $return = array();
    while ($arr = $res->fetch())
      array_push($return, $arr);
    return $return;
  }


  function loadByID($id)
  {
    $id = (int) $id;
    DEBUG("requested to load ID »{$id}«");
    $res = $this->getData('*', "id={$id} LIMIT 1");
    if (count($res) < 1)
      return false;
    $this->parse($res[0]);
  }


  function save()
  {
    $upd = array();
    foreach ($this->changes as $key => $value)
    {
      $value = db_escape_string($value);
      array_push($upd, "`{$key}`='{$value}'");
    }
    db_query("UPDATE {$this->default_table} SET ".implode(', ', $upd)." WHERE id=?", array($this->data['id']));
  }

  abstract function parse($data);

}

?>
