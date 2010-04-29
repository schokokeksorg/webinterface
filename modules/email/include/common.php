<?php

function encrypt_mail_password($newpass)
{
  DEBUG("unencrypted PW: »".$newpass."«");
  require_once('inc/base.php');
  if (defined("CRYPT_SHA512") && CRYPT_SHA512 == 1)
  {
    $rounds = rand(1000, 5000);
    $salt = "rounds=".$rounds."$".random_string(8);
    DEBUG("crypt(\"{$newpass}\", \"\$6\${$salt}\$\");");
    $newpass = crypt($newpass, "\$6\${$salt}\$");
  }
  else
  {
    $salt = random_string(8);
    DEBUG("crypt(\"{$newpass}\", \"\$1\${$salt}\$\");");
    $newpass = crypt($newpass, "\$1\${$salt}\$");
  }
  DEBUG("encrypted PW: ".$newpass);
  return chop($newpass);

}

