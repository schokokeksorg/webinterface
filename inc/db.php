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

require_once('inc/error.php');
require_once('inc/debug.php');

class DB 
{
  public static $connected = false;
  public static $connection = NULL;
  
  function __construct() 
  {
    return false;
  }
  function __clone()
  {
    return false;
  }

  static function connect() 
  {
    DB::$connection = new mysqli(config('db_host'), config('db_user'), config('db_pass'), '', config('db_port'));
    if (mysqli_connect_errno())
	    die('Konnte nicht zur Datenbank verbinden. Wenn dieser Fehler wiederholt auftritt, beachrichtigen Sie bitte den Administrator.');
    DB::$connection->set_charset('utf8');
    if (DB::$connection->error)
    {
      DEBUG("DB-Fehler: ".DB::$connection->error);
    	die('Fehler bei der Auswahl der Zeichencodierung. Bitte melden Sie diesen Fehler einem Administrator!');
    }
    DB::$connected = true;
  }

  static function query($query) 
  {
    if (! DB::$connection) 
    {
      DB::connect();
    }
  
    DEBUG($query);
    $result = DB::$connection->query($query);
    if (DB::$connection->error)
    {
      $error = DB::$connection->error;
      logger(LOG_ERR, "inc/base", "dberror", "mysql error: {$error}");
      system_failure('Interner Datenbankfehler: »'.iconv('ISO-8859-1', 'UTF-8', $error).'«.');
    }
    $count = DB::$connection->affected_rows;
    if (! $count)
      $count = 'no';
    DEBUG("=> {$count} rows");
    return $result;
  }

  static function insert_id()
  {  
    return DB::$connection->insert_id;
  }


  static function escape($string)
  {
    return DB::$connection->real_escape_string($string);
  }

}


if (! DB::$connected ) {
  DB::connect();
}

?>
