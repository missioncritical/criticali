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

require_once('log4php/Logger.php');

/**
 * the root logger
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @see Logger
 */
class LoggerRoot extends Logger {

    /**
     * @var string name of logger 
     */
    var $name   = 'root';

    /**
     * @var object must be null for LoggerRoot
     */
    var $parent = null;
    

    /**
     * Constructor
     *
     * @param integer $level initial log level
     */
    function LoggerRoot($level = null)
    {
        if ($level == null)
            $level = LOGGER_LEVEL_ALL;
        $this->setLevel($level);
    } 
    
    function getChainedLevel()
    {
        return $this->level;
    } 
    
    /**
     * Setting a null value to the level of the root category may have catastrophic results.
     */
    function setLevel($level)
    {
        $this->level = (int)$level;
    }    
 
    function setPriority($level)
    {
        $this->setLevel($level); 
    }
    
    function setParent($parent)
    {
        return false;
    }  
}
?>