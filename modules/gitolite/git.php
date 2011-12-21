<?php

include('git.php');
require_role(ROLE_SYSTEMUSER);

list_repos();
#refresh_gitosis();

