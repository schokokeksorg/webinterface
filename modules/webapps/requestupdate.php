<?php
require_once('session/start.php');
require_once('webapp-installer.php');

require_role(ROLE_SYSTEMUSER);

$section = 'webapps_freewvs';
$directory = $_GET['dir'];

if (! in_homedir($directory))
  system_failure('Pfad nicht im Homedir oder ungültige Zeichen im Pfad');

$app = $_GET['app'];
verify_input_general($app);


$sure = user_is_sure();
if ($sure === NULL)
{
  are_you_sure("dir={$directory}&app={$app}", "Möchten Sie ein Update der Anwendung »{$app}« im Verzeichnis »{$directory}« automatisch durchführen lassen?");
}
elseif ($sure === true)
{
  request_update($app, $directory, get_url_for_dir($directory));
  if (! $debugmode)
    header("Location: waitforupdate");
}
elseif ($sure === false)
{
  if (! $debugmode)
    header("Location: freewvs");
}


