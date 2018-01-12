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


function verify_mail_token($token)
{
  db_query("DELETE FROM kundendaten.mailaddress_token WHERE expire<NOW()");
  $args = array(":token" => $token);
  $result = db_query("SELECT handle, email FROM kundendaten.mailaddress_token WHERE token=:token AND expire>NOW()", $args);
  if ($result->rowCount() > 0)
  {
    $line = $result->fetch();
    db_query("DELETE FROM kundendaten.mailaddress_token WHERE token=:token", $args); 
    return $line;
  } else {
    return NULL;
  }
}


function update_mailaddress($daten)
{
    $handle = $daten['handle'];
    $email = $daten['email'];

    if (! check_emailaddr($email)) {
        system_failure('Es ist eine ungültige Adresse hinterlegt. So wird das nichts. Bitte die Änderung von vorne machen.');
    } 

    $args = array(':handle' => $handle,
                  ':email' => $email);
    db_query("UPDATE kundendaten.handles SET email=:email WHERE id=:handle", $args);
    
}


