<?php
/*
 * Add the routes for your application in this file.
 */

// to connect a controller to the site root use:
// $route->root(array('controller'=>'home'));


// default routes:
$route->match('/:controller/:action/:id');
$route->match('/:controller/:action');
$route->match('/:controller');
