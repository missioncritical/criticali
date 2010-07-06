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

require_once('log4php/LoggerCategory.php');
require_once('log4php/LoggerManager.php');

/**
 * Main class for logging operations  
 *
 * @author       Marco Vassura
 * @version      $Revision: 1.3 $
 * @package      log4php
 */
class Logger extends LoggerCategory {

    /**
     * Constructor
     * @param string $name logger name 
     */    
    function Logger($name)
    {
        $this->LoggerCategory($name);
    }
    
    /**
     * Get a Logger by name (Delegate to {@link LoggerManager})
     * @param string $name logger name
     * @return object 
     */    
    function &getLogger($name)
    {
        return LoggerManager::getLogger($name);
    }
    
    /**
     * get the Root Logger (Delegate to {@link LoggerManager})
     * @return object 
     */    
    function &getRootLogger()
    {
        return LoggerManager::getRootLogger();    
    }
}
?>