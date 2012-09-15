<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/


function account_has_googleauth($username)
{
  $username = mysql_real_escape_string($username);
  $result = db_query("SELECT id FROM mail.webmail_googleauth WHERE email='{$username}'");
  if (mysql_num_rows($result) > 0) {
    $tmp = mysql_fetch_assoc($result);
    $id = $tmp['id'];
    return $id;
  } else {
    return false;
  }
}



function validate_password($username, $password) 
{
  $username = mysql_real_escape_string($username);
  $result = db_query("SELECT account, cryptpass FROM mail.courier_mailaccounts WHERE account='{$username}' UNION SELECT account, cryptpass FROM mail.courier_virtual_accounts WHERE account='{$username}'");
  if (mysql_num_rows($result) != 1) {
    // Kein Account mit dem Namen oder Name nicht eindeutig
    return false;
  }
  $account = mysql_fetch_assoc($result);
  return (crypt($password, $account['cryptpass']) == $account['cryptpass']);
}


function store_webmail_password($username, $oldpw, $newpw)
{
  $secret = $newpw;
  if (strlen($oldpw) > strlen($newpw)) {
    $secret = str_pad($newpw, strlen($oldpw), $newpw);
  }
  if (strlen($oldpw) < strlen($newpw)) {
    $newpw = substr($newpw, 0, strlen($oldpw));
  }
  if (strlen($oldpw) != strlen($secret)) {
    system_failure('Interner Fehler: Passwörter sind nicht gleich lang');
  }
  $code = '';
  for ($i = 0 ; $i != strlen($oldpw) ; $i++) {
    $code .= chr( ord($oldpw[$i]) ^ ord($secret[$i]) );
  }
  $code = base64_encode($code);
  DEBUG(array($oldpw, $newpw, $code));

  $uid = (int) $_SESSION['userinfo']['uid'];

  db_query("REPLACE INTO mail.webmail_googleauth (useraccount, email, webmailpass) VALUES ({$uid}, '{$username}', '{$code}')");
}


function decode_webmail_password($crypted, $webmailpw)   
{
  $crypted = base64_decode($crypted);
  $secret = $webmailpw;
  if (strlen($crypted) > strlen($webmailpw)) {
    $secret = str_pad($webmailpw, strlen($crypted), $webmailpw);
  }
  if (strlen($crypted) < strlen($webmailpw)) {
    $webmailpw = substr($webmailpw, 0, strlen($crypted));
  }
  $clear = '';
  for ($i = 0 ; $i != strlen($crypted) ; $i++) {
    $clear .= chr( ord($crypted[$i]) ^ ord($secret[$i]) );
  }
  DEBUG('decrypted: '.$clear);
  return $clear;
}


function check_webmail_password($username, $webmailpass)
{
  $username = mysql_real_escape_string($username);
  $result = db_query("SELECT webmailpass FROM mail.webmail_googleauth WHERE email='{$username}'");
  $tmp = mysql_fetch_assoc($result);
  
  $crypted = $tmp['webmailpass'];
    
  $clear = decode_webmail_password($crypted, $webmailpass);

  return validate_password($username, $clear);
  
}


function generate_secret($username)
{
  $username = mysql_real_escape_string($username);
  require_once('external/googleauthenticator/GoogleAuthenticator.php');
  $ga = new PHPGangsta_GoogleAuthenticator();
  
  $secret = $ga->createSecret();
  DEBUG('GA-Secret: '.$secret);
  DEBUG('QrCode: '.$ga->getQRCodeGoogleUrl('Blog', $secret));
  db_query("UPDATE mail.webmail_googleauth SET ga_secret='{$secret}' WHERE email='{$username}'");
  return $secret;
}

function check_googleauth($username, $code) {
  $username = mysql_real_escape_string($username);

  $result = db_query("SELECT ga_secret, failures FROM mail.webmail_googleauth WHERE email='{$username}' AND (unlock_timestamp IS NULL OR unlock_timestamp <= NOW())");
  $tmp = mysql_fetch_assoc($result);
  $secret = $tmp['ga_secret'];

  require_once('external/googleauthenticator/GoogleAuthenticator.php');
  $ga = new PHPGangsta_GoogleAuthenticator();
  
  $checkResult = $ga->verifyCode($secret, $code, 2);    // 2 = 2*30sec clock tolerance
  if ($checkResult) {
    db_query("UPDATE mail.webmail_googleauth SET failures = 0, unlock_timestamp=NULL WHERE email='{$username}'");
    DEBUG('OK');
  } else {
    if ($tmp['failures'] > 0 && $tmp['failures'] % 5 == 0) {
      db_query("UPDATE mail.webmail_googleauth SET failures = failures+1, unlock_timestamp = NOW() + INTERVAL 5 MINUTE WHERE email='{$username}'");
    } else {
      db_query("UPDATE mail.webmail_googleauth SET failures = failures+1 WHERE email='{$username}'");
    }
    
    DEBUG('FAILED');
  }
  return $checkResult;

}

function generate_qrcode_image($secret) {
  $url = 'otpauth://totp/Webmail?secret='.$secret;
  
  $descriptorspec = array(
    0 => array("pipe", "r"),  // STDIN ist eine Pipe, von der das Child liest
    1 => array("pipe", "w"),  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => array("pipe", "w") 
  );

  $process = proc_open('qrencode -t PNG -s 5 -o -', $descriptorspec, $pipes);

  if (is_resource($process)) {
    // $pipes sieht nun so aus:
    // 0 => Schreibhandle, das auf das Child STDIN verbunden ist
    // 1 => Lesehandle, das auf das Child STDOUT verbunden ist

    fwrite($pipes[0], $url);
    fclose($pipes[0]);

    $pngdata = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Es ist wichtig, dass Sie alle Pipes schließen bevor Sie
    // proc_close aufrufen, um Deadlocks zu vermeiden
    $return_value = proc_close($process);
  
    return $pngdata;
  } else {
    warning('Es ist ein interner Fehler im Webinterface aufgetreten, aufgrund dessen kein QR-Code erstellt werden kann. Sollte dieser Fehler mehrfach auftreten, kontaktieren Sie bitte die Administratoren.');
  }
  
  
}

function accountname($id) 
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT email FROM mail.webmail_googleauth WHERE id={$id} AND useraccount={$uid}");
  if ($tmp = mysql_fetch_assoc($result)) {
    return $tmp['email'];
  }
}


function delete_googleauth($id) 
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  db_query("DELETE FROM mail.webmail_googleauth WHERE id={$id} AND useraccount={$uid}");
}

