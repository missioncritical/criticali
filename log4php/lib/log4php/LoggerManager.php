<?php
// 
// This framework is based on log4j see http://jakarta.apache.org/log4j
// Copyright (C) The Apache Software Foundation. All rights reserved.
//
// PHP port and modifications by Marco Vassura. All rights reserved.
// For more information, please see <http://www.vxr.it/log4php/>. 
//
// This software is published under the terms of the Apache Software
// License version 1.1, a copy of which has been included with this
// distribution in the LICENSE.txt file.
//
// log4php: a php port of log4j java logging package
//

require_once('log4php/LoggerRoot.php');
require_once('log4php/LoggerHierarchy.php');

/**
 * @global object holds the Logger Hierarchy instance
 */
$GLOBALS['logger.Hierarchy'] = new LoggerHierarchy(new LoggerRoot());

LoggerManager::resetConfiguration();

/**
 * Use the LoggerManager to retreive instances of {@link Logger}.
 *
 * @author Marco Vassura
 * @version $Revision: 1.5 $
 * @package log4php 
 */
class LoggerManager {

    /**
     * check if a given logger exists
     * 
     * @param string $name logger name 
     * @static
     * @return boolean
     */
    function exists($name)
    {
        return $GLOBALS['logger.Hierarchy']->exists($name);
    }

    /**
     * returns an array this whole {@link Logger} instances
     * 
     * @static
     * @return array
     */
    function getCurrentLoggers()
    {
        return $GLOBALS['logger.Hierarchy']->getCurrentLoggers();
    }
    
    /**
     * returns the root logger
     * 
     * @static
     * @return object
     * @see LoggerRoot
     */
    function &getRootLogger()
    {
        return $GLOBALS['logger.Hierarchy']->getRootLogger();
    }
    
    /**
     * returns the specified {@link Logger}
     * 
     * @param string $name logger name
     * @static
     * @return object
     */
    function &getLogger($name)
    {
        return $GLOBALS['logger.Hierarchy']->getLogger($name);
    }
    
    /**
     * returns the {@link LoggerHierarchy} object
     * 
     * @static
     * @return object
     */
    function &getLoggerRepository()
    {
        return $GLOBALS['logger.Hierarchy'];
    }
    

    /**
     * destroy loggers object tree
     * 
     * @static
     * @return boolean 
     */
    function resetConfiguration()
    {
        return $GLOBALS['logger.Hierarchy']->resetConfiguration();    
    }
    
    /**
     * does nothing
     */
    function setRepositorySelector($selector, $guard)
    {
        return;
    }
    
    /**
     * safely close all appenders
     */
    function shutdown()
    {
        return $GLOBALS['logger.Hierarchy']->shutdown();    
    }
}
?>