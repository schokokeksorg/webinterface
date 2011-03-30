<?php

function set_newsletter_address($address) {
  $cid = $_SESSION['customerinfo']['customerno'];
  $address = maybe_null(mysql_real_escape_string($address));
  db_query("UPDATE kundendaten.kunden SET email_newsletter={$address} WHERE id={$cid}");
}

function get_newsletter_address() {
  $cid = $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT email_newsletter FROM kundendaten.kunden WHERE id={$cid}");
  $r = mysql_fetch_assoc($result);
  return $r['email_newsletter'];
}


function get_latest_news() {
  $today = strftime('%Y-%m-%d');
  $result = db_query("SELECT id, date, subject, content FROM misc.news WHERE date > '{$today}' - INTERVAL 1 YEAR ORDER BY date DESC");
  $ret = array();
  while ($item = mysql_fetch_assoc($result)) {
    $ret[] = $item;
  }
  DEBUG($ret);
  return $ret;
}


function get_news_item($id) {
  $id = (int) $id;
  $result = db_query("SELECT date, subject, content FROM misc.news WHERE id={$id}");
  $ret = mysql_fetch_assoc($result);
  DEBUG($ret);
  return $ret;
}


