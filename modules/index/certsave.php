<?php
require_once('session/start.php');
require_once('x509.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_SUBUSER, ROLE_VMAIL_ACCOUNT));


if ($_GET['action'] == 'new')
{
  check_form_token('clientcert_add');
  if (! isset($_SESSION['clientcert_cert']))
    system_failure('Kein Zertifikat');
  
  add_clientcert($_SESSION['clientcert_cert'], $_SESSION['clientcert_dn'], $_SESSION['clientcert_issuer']);

  // Räume session auf
  unset($_SESSION['clientcert_cert']);
  unset($_SESSION['clientcert_dn']);
  unset($_SESSION['clientcert_issuer']);
  header('Location: cert');
}
elseif ($_GET['action'] == 'delete')
{
  $cert = get_cert_by_id($_GET['id']);
  if (! $cert)
    system_failure('no ID');
  $username = NULL;
  if ($_SESSION['role'] == ROLE_SYSTEMUSER) {
    $username = $_SESSION['userinfo']['username'];
    if (isset($_SESSION['subuser']))
      $username = $_SESSION['subuser'];
  } elseif ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT) {
    $username = $_SESSION['mailaccount'];
  }
  if (! ($cert['username'] == $username))
    system_failure('Das Zertifikat ist nicht für Ihren Zugang eingerichtet');
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&id={$cert['id']}", "Möchten Sie das Zertifikat »{$cert['dn']}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_clientcert($cert['id']);
    if (! $debugmode)
      header("Location: cert");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: cert");
  }
}
else
  system_failure('Kein Kommando');


