<?php

function encrypt_mail_password($pw)
{
  DEBUG("unencrypted PW: ".$pw);
  require_once('inc/base.php');
  $salt = random_string(8);
  $encpw = crypt($pw, "\$1\${$salt}\$");
  DEBUG("encrypted PW: ".$encpw);
  return chop($encpw);

}

