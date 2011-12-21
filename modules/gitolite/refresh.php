<?php
require_role(ROLE_SYSTEMUSER);
include("git.php");

$section = 'git_git';

title("GIT-Konfiguration aktualisieren");

output('<p>Bitte warten Sie während die GIT-Konfiguration neu eingelesen wird...</p>');

refresh_gitolite();

if (!$debugmode) {
  header("Location: git");
}

