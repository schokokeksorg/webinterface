<?php
require_role(ROLE_SYSTEMUSER);

$data_dir = realpath( dirname(__FILE__).'/../data/' );
$config_file = $data_dir.'/gitolite-admin/conf/webinterface.conf';
$config_dir = $data_dir.'/gitolite-admin/conf/webinterface';
$key_dir = $data_dir.'/gitolite-admin/keydir';
DEBUG("gitolite-data_dir: ".$data_dir);
$git_wrapper = $data_dir . '/git-wrapper.sh';


function check_env() 
{
  global $git_wrapper, $data_dir, $config_file, $config_dir, $key_dir;
  if (!is_executable($git_wrapper)) {
    system_failure("git_wrapper.sh is not executable: {$git_wrapper}");
  }
  if (! (is_file($data_dir.'/sshkey') && is_file($data_dir.'/sshkey.pub'))) {
    system_failure("SSH-key not found. Please setup the gitolite-module correctly. Run ./data/initialize.sh");
  }
  if (! is_dir($data_dir.'/gitolite-admin')) {
    system_failure("Repository gitolite-admin ot found. Initial checkout must be made manually. Run ./data/initialize.sh");
  }
  if (! is_dir($config_dir)) {
    system_failure("gitolite-admin repository is not prepared.");
  }
  if (! (is_dir($key_dir) && is_writeable($$config_file))) {
    system_failure("Repository gitolite-admin is corrupted or webinterface.conf is not writeable.");
  }
}


function git_wrapper($commandline)
{
  global $git_wrapper, $data_dir;

  $command = $git_wrapper.' '.$commandline;
  $output = array();
  $retval = 0;
  DEBUG($command);
  exec($command, $output, $retval);
  DEBUG($output);
  DEBUG($retval);
  if ($retval > 0) {
    system_failure('Interner Fehler!');
    // FIXME: Hier sollte auf jeden Fall ein Logging angeworfen werden!
  }
}

function refresh_gitolite() 
{
  check_env();
  git_wrapper('pull');
}


function read_config()
{
  global $gitolite_conf;
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $groups = array();

  $data = parse_ini_file($gitolite_conf, TRUE);
  DEBUG($data);
  
}


function list_repos() 
{
  global $config_file, $config_dir;
  $username = $_SESSION['userinfo']['username'];
  $userconfig = $config_dir . '/' . $username . '.conf';
  DEBUG("using config file ".$userconfig);
  if (! is_file($userconfig)) {
    DEBUG("user-config does not exist");
    return array();
  }

  $repos = array();
  $lines = file($userconfig);
  $current_repo = NULL;
  foreach ($lines as $line) {
    $m = array();
    if (preg_match('_^[ \t]*repo ([^]]+)_', $line, $m) != 0) {
      DEBUG("found repo ".$m[1]);
      $repos[] = $m[1];
    }
  }
  DEBUG($repos);
  return $repos;
}




function add_key($pubkey, $handle)
{
  
}



