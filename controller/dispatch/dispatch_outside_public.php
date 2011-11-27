<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

require_once('../init.php');

$routing_class = Cfg::get('routes/routing_class', 'Controller_Routing');
$routing = new $routing_class();

$params = $_GET;
if (isset($_POST)) $params = array_merge($params, $_POST);

$controller = $routing->controller_for($_SERVER['REQUEST_URI'], $params, $_SERVER['REQUEST_METHOD']);
$controller->set_routing($routing);

foreach ($params as $param=>$value) {
  $_REQUEST[$param] = $value;
}

$controller->handle_request();

?>