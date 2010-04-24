<?php

function encrypt_mail_password($pw)
{
  DEBUG("unencrypted PW: ".$pw);
  require_once('inc/base.php');
  $newpass = '';
  if (defined("CRYPT_SHA512") && CRYPT_SHA512 == 1)
  {
    $rounds = rand(1000, 5000);
    $salt = "rounds=".$rounds."$".random_string(8);
    $newpass = crypt($newpass, "\$6\${$salt}\$");
  }
  else
  {
    $salt = random_string(8);
    $newpass = crypt($newpass, "\$1\${$salt}\$");
  }
  DEBUG("encrypted PW: ".$newpass);
  return chop($newpass);

}

