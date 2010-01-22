<?php

require_once("inc/base.php");
require_once("vhosts.php");

require_role(ROLE_SYSTEMUSER);

$section = 'vhosts_stats';

// Stellt sicher, dass der angegebene VHost dem User gehört
$vhost = get_vhost_details($_REQUEST['vhost']);

if (! isset($_REQUEST['file']))
{
  $_REQUEST['file'] = 'index.html';
}

if (!preg_match('/((daily_|hourly_|ctry_)?(usage|agent|search|ref|url|site)(_[0-9]+)?|index)\.(png|html)/', $_REQUEST['file']))
{
  system_failure("Ungültiger Dateiname: »".filter_input_general($_REQUEST['file'])."«");
}

$path = '/home/stats/webalizer/data/' . $vhost['fqdn'];
$file = $path . '/' . $_REQUEST['file'];

if ( is_file($file) )
{
  DEBUG("opening file ".$file);
  if (preg_match('/\.png/', $file))
  {
    //Binärdateien
    header("Content-Type: image/png");
    header("Content-Length: " . filesize($file));
    header("Content-Transfer-Encoding: binary\n");
    
    $fp = fopen($file, "r");
    fpassthru($fp);
    die();
  }

  $html = iconv('latin9', 'utf8', file_get_contents($file));
  DEBUG($html);
  // Nur content vom HTML
  $html = preg_replace(':^.*?<BODY[^>]*>(.*)</BODY>.*$:si', '$1', $html);
  DEBUG($html);

  // Bilder rewriten
  $html = preg_replace('/SRC="((daily_|hourly_|ctry_)?usage(_[0-9]+)?\.png)"/', 'SRC="showstats?vhost='.$vhost['id'].'&amp;file=$1"', $html);
  // Interne Links rewriten
  $html = preg_replace('!HREF="(./)?((usage|agent|search|ref|url|site|index)(_[0-9]+)?\.html)"!', 'HREF="showstats?vhost='.$vhost['id'].'&amp;file=$2"', $html);
  output($html);
}
else
{
  system_failure("Die Statistiken konnten nicht gefunden werden. Beachten Sie bitte, dass die Erstellung regelmäßig nachts geschieht. Neu in Auftrag gegebene Statistiken können Sie erst am darauffolgenden Tag betrachten.");
}



