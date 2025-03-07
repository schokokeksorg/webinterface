<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function set_newsletter_address($address)
{
    $cid = $_SESSION['customerinfo']['customerno'];
    db_query("UPDATE kundendaten.kunden SET email_newsletter=:address WHERE id=:cid", [":address" => $address, ":cid" => $cid]);
}

function get_newsletter_address()
{
    $cid = $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT email_newsletter FROM kundendaten.kunden WHERE id=?", [$cid]);
    $r = $result->fetch();
    return $r['email_newsletter'];
}


function get_latest_news()
{
    $result = db_query("SELECT id, date, subject, content FROM misc.news WHERE date > CURDATE() - INTERVAL 2 YEAR ORDER BY date DESC");
    $ret = [];
    while ($item = $result->fetch()) {
        $ret[] = $item;
    }
    DEBUG($ret);
    return $ret;
}


function get_news_item($id)
{
    $id = (int) $id;
    $result = db_query("SELECT date, subject, content FROM misc.news WHERE id=?", [$id]);
    $ret = $result->fetch();
    DEBUG($ret);
    return $ret;
}
