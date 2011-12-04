<?php

$APP_CONFIG = array();
$APP_CONFIG['cache'] = array();
$APP_CONFIG['cache']['profiles'] = array();

$APP_CONFIG['cache']['profiles']['memory_profile'] = array();
$APP_CONFIG['cache']['profiles']['memory_profile']['engine'] = 'memory';
$APP_CONFIG['cache']['profiles']['memory_profile']['ttl'] = 0;
$APP_CONFIG['cache']['profiles']['short_file_profile'] = array();
$APP_CONFIG['cache']['profiles']['short_file_profile']['engine'] = 'file';
$APP_CONFIG['cache']['profiles']['short_file_profile']['ttl'] = 5;

?>