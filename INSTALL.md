Installing composer dependencies
================================

We use composer to install PHP dependencies. These are defined in the
file composer.json, the current dependency metadata is in composer.lock.

To fetch the dependencies:

* Get composer.phar as described at
  https://getcomposer.org/download/
* run [path_to]/composer.phar install in the webroot.

This will install all required dependencies to the directory vendor.
The vendor directory is listed in .gitignore to avoid committing
duplicates of upstream projects.
