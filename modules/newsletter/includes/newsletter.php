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

function set_newsletter_address($address) {
  $cid = $_SESSION['customerinfo']['customerno'];
  db_query("UPDATE kundendaten.kunden SET email_newsletter=:address WHERE id=:cid", array(":address" => $address, ":cid" => $cid));
}

function get_newsletter_address() {
  $cid = $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT email_newsletter FROM kundendaten.kunden WHERE id=?", array($cid));
  $r = $result->fetch();
  return $r['email_newsletter'];
}


function get_latest_news() {
  $result = db_query("SELECT id, date, subject, content FROM misc.news WHERE date > CURDATE() - INTERVAL 2 YEAR ORDER BY date DESC");
  $ret = array();
  while ($item = $result->fetch()) {
    $ret[] = $item;
  }
  DEBUG($ret);
  return $ret;
}


function get_news_item($id) {
  $id = (int) $id;
  $result = db_query("SELECT date, subject, content FROM misc.news WHERE id=?", array($id));
  $ret = $result->fetch();
  DEBUG($ret);
  return $ret;
}


