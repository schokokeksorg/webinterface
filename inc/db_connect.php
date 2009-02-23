<?php

require_once('inc/error.php');

if (!@mysql_connect($config['db_host'], $config['db_user'], $config['db_pass']))
	system_failure('Konnte nicht zur Datenbank verbinden. Wenn dieser Fehler wiederholt auftritt, beachrichtigen Sie bitte den Administrator.');
	
if (!@mysql_query('SET NAMES utf8'))
	system_failure('Fehler bei der Auswahl der Zeichencodierung. Bitte melden Sie diesen Fehler einem Administrator!');

?>
