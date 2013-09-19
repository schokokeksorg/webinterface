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

function set_newsletter_address($address) {
  $cid = $_SESSION['customerinfo']['customerno'];
  $address = maybe_null(DB::escape($address));
  DB::query("UPDATE kundendaten.kunden SET email_newsletter={$address} WHERE id={$cid}");
}

function get_newsletter_address() {
  $cid = $_SESSION['customerinfo']['customerno'];
  $result = DB::query("SELECT email_newsletter FROM kundendaten.kunden WHERE id={$cid}");
  $r = $result->fetch_assoc();
  return $r['email_newsletter'];
}


function get_latest_news() {
  $today = strftime('%Y-%m-%d');
  $result = DB::query("SELECT id, date, subject, content FROM misc.news WHERE date > '{$today}' - INTERVAL 1 YEAR ORDER BY date DESC");
  $ret = array();
  while ($item = $result->fetch_assoc()) {
    $ret[] = $item;
  }
  DEBUG($ret);
  return $ret;
}


function get_news_item($id) {
  $id = (int) $id;
  $result = DB::query("SELECT date, subject, content FROM misc.news WHERE id={$id}");
  $ret = $result->fetch_assoc();
  DEBUG($ret);
  return $ret;
}


