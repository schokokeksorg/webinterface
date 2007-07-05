<?php

require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/debug.php');


abstract class KeksData
{
  protected $default_table;
  
  protected $data = array();

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
    if (array_key_exists($key, $this->data))
      $this->data[$key] = $value;
    elseif (isset($this->$key))
      $this->$key = $value;
    else
      $this->data[$key] = $value;
  }

  protected function setup()
  {
    $fields = array();
    $res = db_query("DESCRIBE {$this->default_table}");
    while ($f = mysql_fetch_object($res))
    {
      $fields[$f->Field] = $f->Default;
    }
    $this->data = $fields;
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
    
    $res = db_query("SELECT {$fields} FROM {$table} {$where}");
    $return = array();
    while ($arr = mysql_fetch_assoc($res))
      array_push($return, $arr);
    return $return;
  }


  function loadByID($id)
  {
    $id = (int) $id;
    $res = $this->getData('*', "id={$id} LIMIT 1");
    $this->parse($res[0]);
  }


  abstract function parse($data);

}

?>
