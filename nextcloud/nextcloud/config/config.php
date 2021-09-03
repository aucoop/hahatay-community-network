<?php
$CONFIG = array (
  'htaccess.RewriteBase' => '/',
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'apps_paths' => 
  array (
    0 => 
    array (
      'path' => '/var/www/html/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 => 
    array (
      'path' => '/var/www/html/custom_apps',
      'url' => '/custom_apps',
      'writable' => true,
    ),
  ),
  'instanceid' => 'oc3y3p97qcfp',
  'passwordsalt' => 'Gv3jW+UYeIr/wIjHThzOpw9OE1t5Kt',
  'secret' => 'VHgH9H29MN+++XtEsW8Nfa5VAiUXW49gMeMuX2nVvim/Ic9u',
  'trusted_domains' => 
  array (
	  0 => 'localhost:8000',
	  1 => '192.168.*.*',
  ),
  'datadirectory' => '/var/www/html/data',
  'dbtype' => 'mysql',
  'version' => '22.0.0.11',
  'overwrite.cli.url' => 'http://localhost:8000',
  'dbname' => 'nextcloud',
  'dbhost' => 'db',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'mysql.utf8mb4' => true,
  'dbuser' => 'nextcloud',
  'dbpassword' => 'nextcloud',
  'installed' => true,
);
