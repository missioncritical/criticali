<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

require_once('private/init.php');

$routing_class = Cfg::get('routes/routing_class', 'Controller_Routing');
$routing = new $routing_class();

$params = array();
$controller = $routing->controller_for($_SERVER['REQUEST_URI'], $params);

foreach ($params as $param=>$value) {
  $_REQUEST[$param] = $value;
  $_GET[$param] = $value;
}

$controller->handle_request();

?>