<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');

function create_new_webapp($appname, $directory, $url, $data)
{
    if (directory_in_use($directory)) {
        system_failure('Sie haben erst kürzlich eine Anwendung in diesem Verzeichnis installieren lassen. Aus Sicherheitsgründen können Sie in diesem Verzeichnis am selben Tag nicht schon wieder eine Anwendung installieren.');
    }
    $args = [":username" => $_SESSION['userinfo']['username'],
                ":appname" => $appname,
                ":dir" => $directory,
                ":url" => $url,
                ":data" => $data, ];
    db_query("INSERT INTO vhosts.webapp_installer (appname, directory, url, state, username, data) VALUES (:appname, :dir, :url, 'new', :username, :data)", $args);
}


function request_update($appname, $directory, $url)
{
    if (directory_in_use($directory)) {
        system_failure('Sie haben erst kürzlich eine Anwendung in diesem Verzeichnis installieren lassen oder ein Update in diesem Verzeichnis angefordert. Bitte warten Sie bis diese Aktion durchgeführt wurde.');
    }
    $args = [":username" => $_SESSION['userinfo']['username'],
                ":appname" => $appname,
                ":dir" => $directory,
                ":url" => $url, ];
    db_query("INSERT INTO vhosts.webapp_installer (appname, directory, url, state, username) VALUES (:appname, :dir, :url, 'old', :username)", $args);
}

function directory_in_use($directory)
{
    $result = db_query("SELECT id FROM vhosts.webapp_installer WHERE (state IN ('new','old') OR DATE(lastchange)=CURDATE()) AND directory=?", [$directory]);
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}

function upgradeable($appname, $version)
{
    DEBUG("Is {$appname}-{$version} upgradeable?");
    /*if ($appname == 'Drupal7') {
        DEBUG("found Drupal-7.*!");
        return 'drupal7';
    }
    if ($appname == 'Drupal') {
        DEBUG("found Drupal!");
        if (substr($version, 0, 2) == '7.') {
            DEBUG("found Drupal-7.*!");
            return 'drupal7';
        }
        DEBUG("Version: ".substr($version, 0, 2));
    } */
    if ($appname == 'MediaWiki') {
        DEBUG("found MediaWiki");
        return 'mediawiki';
    }
    /*elseif ($appname == 'owncloud')
    {
      DEBUG('found OwnCloud');
      return 'owncloud';
    }*/
    DEBUG("found no upgradeable webapp!");
    return null;
}


function get_url_for_dir($docroot, $cutoff = '')
{
    if (substr($docroot, -1) == '/') {
        $docroot = substr($docroot, 0, -1);
    }
    $result = db_query("SELECT `ssl`, IF(FIND_IN_SET('aliaswww', options), CONCAT('www.',fqdn), fqdn) AS fqdn FROM vhosts.v_vhost WHERE docroot IN (?, ?)", [$docroot, $docroot.'/']);
    if ($result->rowCount() < 1) {
        if (!strstr($docroot, '/')) {
            return null;
        }
        return get_url_for_dir(substr($docroot, 0, strrpos($docroot, '/')), substr($docroot, strrpos($docroot, '/')).$cutoff);
    }
    $tmp = $result->fetch();
    $prefix = 'http://';
    if ($tmp['ssl'] == 'forward' || $tmp['ssl'] == 'https') {
        $prefix = 'https://';
    }
    return $prefix.$tmp['fqdn'].filter_output_html($cutoff);
}


function create_webapp_mysqldb($application, $sitename)
{
    // dependet auf das mysql-modul
    require_once('modules/mysql/include/mysql.php');

    $username = $_SESSION['userinfo']['username'];
    $description = "Automatisch erzeugte Datenbank für {$application} ({$sitename})";

    // zuerst versuchen wir username_webappname. Wenn das nicht klappt, dann wird hochgezählt
    $handle = $username.'_'.$application;

    if (validate_mysql_username($handle) && validate_mysql_dbname($handle) && !(has_mysql_user($handle) || has_mysql_database($handle))) {
        logger(LOG_INFO, "webapps/include/webapp-installer", "create", "creating db and user »{$handle}«");
        create_mysql_database($handle, $description);
        create_mysql_account($handle, $description);
        set_mysql_access($handle, $handle, true);
        $password = random_string(10);
        set_mysql_password($handle, $password);
        return ['dbuser' => $handle, 'dbname' => $handle, 'dbpass' => $password];
    }

    for ($i = 0; $i < 100 ; $i++) {
        $handle = $username.'_'.$i;
        if (validate_mysql_username($handle) && validate_mysql_dbname($handle) && !(has_mysql_user($handle) || has_mysql_database($handle))) {
            logger(LOG_INFO, "webapps/include/webapp-installer", "create", "creating db and user »{$handle}«");
            create_mysql_database($handle, $description);
            create_mysql_account($handle, $description);
            set_mysql_access($handle, $handle, true);
            $password = random_string(10);
            set_mysql_password($handle, $password);
            return ['dbuser' => $handle, 'dbname' => $handle, 'dbpass' => $password];
        }
    }
    system_failure('Konnte keine Datenbank erzeugen. Bitte melden Sie diesen Umstand den Administratoren!');
}
