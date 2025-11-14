<?php

if (php_sapi_name() != 'cli') {
    echo 'command line script';
    die();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$script_dir = __DIR__;
$module_dir = dirname($script_dir, 1);
$base_dir = dirname($script_dir, 3);

chdir($base_dir);
set_include_path($module_dir . '/include' . PATH_SEPARATOR . get_include_path());

require_once('config.php');
require_once('session/checkuser.php');
require_once("class/database.php");

$result = db_query("SELECT username FROM system.useraccounts");
$users = [];
foreach ($result as $u) {
    $users[] = $u["username"];
}

if (count($users) < 50) {
    echo "STOP, zu uwenig Useracocunts definiert!";
    die();
}


function git($command)
{
    $command = './modules/gitolite/scripts/git-wrapper.sh ' . $command;
    $output = [];
    $retval = 0;
    DEBUG($command);
    exec($command, $output, $retval);
}

git('pull');


function remove_from_config($u)
{
    $config = "../gitolite-data/gitolite-admin/conf/webinterface.conf";
    $content = file_get_contents($config);
    file_put_contents($config, str_replace('include  "webinterface/' . $u . '.conf"' . "\n", "", $content));
}

function list_ssh_keys($conf)
{
    $open_file = fopen($conf, "r");
    $users = [];
    while (($line = fgets($open_file)) !== false) {
        if (str_starts_with($line, "# user ")) {
            $users[] = str_replace("# user ", "", trim($line));
        }
    }
    return $users;
}

function remove_foreign_key($keyname)
{
    foreach (glob("../gitolite-data/gitolite-admin/conf/webinterface/*.conf") as $f) {
        $content = file_get_contents($f);
        file_put_contents($f, str_replace('# foreign user ' . $keyname . "\n", "", $content));
    }
}

foreach (glob("../gitolite-data/gitolite-admin/conf/webinterface/*.conf") as $f) {
    $username = str_replace('.conf', '', basename($f));
    if (!in_array($username, $users)) {
        // User gibt es nicht mehr!
        echo "$username gibt es nicht mehr, lösche $f!\n";
        // Zeile aus webinterface.conf entfernen
        remove_from_config($username);

        // ssh-keys aus config extrahieren
        $ssh_keys = list_ssh_keys($f);
        foreach ($ssh_keys as $key) {
            // prüfen, ob der betreffende ssh-key bei einem anderen User als foreign-key drin steht
            remove_foreign_key($key);
            // ssh-keys löschen
            unlink("../gitolite-data/gitolite-admin/keydir/$key.pub");
        }

        // Include-Datei löschen
        unlink($f);

        git("add -u");
        git("commit -m 'delete nonexisting user »{$username}« from config'");
        echo "erledigt!\n";
    }
}

git("push");
