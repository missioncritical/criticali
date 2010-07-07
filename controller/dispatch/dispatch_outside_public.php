<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

require_once('../init.php');

$routing = new Controller_Routing();

$params = array();
$controller = $routing->controller_for($_SERVER['REQUEST_URI'], $params);

foreach ($params as $param=>$value) {
  $_REQUEST[$param] = $value;
  $_GET[$param] = $value;
}

$controller->handle_request();

?>