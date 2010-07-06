<?php

require_once('private/init.php');

$routing = new Controller_Routing();

$params = array();
$controller = $routing->controller_for($_SERVER['REQUEST_URI'], $params);

foreach ($params as $param=>$value) {
  $_REQUEST[$param] = $value;
  $_GET[$param] = $value;
}

$controller->handle_request();

?>