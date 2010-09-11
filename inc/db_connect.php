<?php

require_once('inc/error.php');

if (!@mysql_connect(config('db_host'), config('db_user'), config('db_pass')))
	die('Konnte nicht zur Datenbank verbinden. Wenn dieser Fehler wiederholt auftritt, beachrichtigen Sie bitte den Administrator.');
	
if (!@mysql_query('SET NAMES utf8'))
	die('Fehler bei der Auswahl der Zeichencodierung. Bitte melden Sie diesen Fehler einem Administrator!');

?>
