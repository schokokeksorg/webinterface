<?php

require_once("certs.php");
require_role(ROLE_SYSTEMUSER);

$section = 'vhosts_certs';

if ($_GET['action'] == 'new')
{
  check_form_token('vhosts_certs_new');
  $cert = $_POST['cert'];
  $key = $_POST['key'];
  if (! $cert or ! $key)
    system_failure('Es muss ein Zertifikat und der dazu passende private Schlüssel eingetragen werden');

  $result = validate_certificate($cert, $key);
  switch ($result)
  {
    case CERT_OK:
      $certinfo = parse_cert_details($cert);
      save_cert($certinfo, $cert, $key, $cabundle);
      header('Location: certs');
      die();
      break;
    case CERT_INVALID:
      system_failure("Das Zertifikat konnte nicht gelesen werden. Eventuell ist der private Schlüssel mit einem Paswort versehen?");
      break;
    case CERT_NOCHAIN:
      warning('Ihr Zertifikat konnte nicht mit einer Zertifikats-Kette validiert werden. Dies wird zu Problemen beim Betrachten der damit betriebenen Websites führen. Meist liegt dies an einem nicht hinterlegten CA-Bundle. Die Admins können Ihr Zertifikats-Bundle auf dem System eintragen. Das Zertifikat wurde dennoch gespeichert.');
      $certinfo = parse_cert_details($cert);
      save_cert($certinfo, $cert, $key, $cabundle);
      output('<p>'.internal_link('certs', 'Zurück zur Übersicht').'</p>');
      break;
  }

}
elseif ($_GET['action'] == 'delete')
{
  $cert = cert_details($_GET['id']);
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&id={$cert['id']}", "Soll das Zertifikat für »{$cert['subject']}« (gültig von {$cert['valid_from']} bis {$cert['valid_until']}) wirklich entfernt werden?");
  }
  elseif ($sure === false)
  {
    header('Location: certs');
    die();
  }
  elseif ($sure === true)
  { 
    delete_cert($cert['id']);
    header('Location: certs');
    die();
  }
}
else
{
  system_failure('not implemented');
}



