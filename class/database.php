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
require_once('inc/error.php');
require_once('inc/debug.php');


class DB extends PDO {
  function __construct() {
    $dsn = "mysql:host=".config('db_host', true);
    if (config('db_port', true)) {
      $dsn .= ';port='.config('db_port', true);
    }
    if (config('db_socket', true)) {
      $dsn = "mysql:unix_socket=".config('db_socket', true);
    }
    $username = config('db_user', true);
    $password = config('db_pass', true);
    parent::__construct($dsn, $username, $password);
  }


  /*
    Wenn Parameter übergeben werden, werden Queries immer als Prepared statements übertragen
  */
  function query($stmt, $params = NULL) {
    if (is_array($params)) {
      if (config("enable_debug")) {
        foreach (array_values($params) as $p) {
          if ($p === '') {
            DEBUG("Potential bug, empty string found in database parameters");
            warning("Potential bug, empty string found in database parameters");
          }
        }
      }
      $response = parent::prepare($stmt);
      $response->execute($params);
      return $response;
    } else {
      if (strtoupper(substr($stmt, 0, 6)) == "INSERT" ||
          strtoupper(substr($stmt, 0, 7)) == "REPLACE" ||
          strpos(strtoupper($stmt), "WHERE") > 0) { // Das steht nie am Anfang
        $backtrace = debug_backtrace();
        if (config("enable_debug")) {
          warning("Unsafe SQL statement in {$backtrace[1]['file']} line {$backtrace[1]['line']}");
        }
      }
      return parent::query($stmt);
    }
  }
}


/* FIXME 
   Das ist etwas unelegant. Soll nur übergangsweise verwendet werden bis alles auf prepared statements umgestellt ist
*/
function db_escape_string($string)
{
  if (config("enable_debug")) {
    $backtrace = debug_backtrace();
    warning("call to db_escape_string() in {$backtrace[0]['file']} line {$backtrace[0]['line']}");
  }
  global $_db;
  __ensure_connected();
  $quoted = $_db->quote($string);
  // entferne die quotes, damit wird es drop-in-Kompatibel zu db_escape_string()
  $ret = substr($quoted, 1, -1);
  return $ret;
}


function db_insert_id()
{
  global $_db;
  __ensure_connected();
  return $_db->lastInsertId();
}


function __ensure_connected()
{
  /*
    Dieses Kontrukt ist vermultich noch schlimmer als ein normales singleton
    aber es hilft uns in unserem prozeduralen Kontext
  */
  global $_db;
  if (! isset($_db)) {
    try {
      DEBUG("Neue Datenbankverbindung!");
      $_db = new DB();
      $_db->query("SET NAMES utf8");
      $_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $_db->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
    } catch (PDOException $e) {
      global $debugmode;
      if ($debugmode) {
        system_failure("MySQL-Fehler: ".$e->getMessage());
      } else {
        system_failure("Fehler bei der Datenbankverbindung!");
      }
    }
  }
}


function db_query($stmt, $params = NULL)
{
  global $_db;
  __ensure_connected();
  $backtrace = debug_backtrace();
  DEBUG($backtrace[0]['file'].':'.$backtrace[0]['line'].': '.$stmt);
  if ($params) {
    DEBUG($params);
  }
  try {
    $result = $_db->query($stmt, $params);
    DEBUG('=> '.$result->rowCount().' rows');
  } catch (PDOException $e) {
    global $debugmode;
    if ($debugmode) {
      system_failure("MySQL-Fehler: ".$e->getMessage());
    } else {
      system_failure("Datenbankfehler");
    }
  }
  return $result;
}


