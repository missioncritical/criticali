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

require_once('log4php/LoggerAppender.php');
require_once('log4php/LoggerLog.php');
require_once('log4php/LoggerLevel.php');

/**
 * Log event using php trigger_error function 
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage appender 
 */ 
class LoggerAppenderPhp extends LoggerAppender {

    var $requiresLayout = true;
    
    /**
     * Constructor
     *
     * @param string $name appender name
     */
    function LoggerAppenderPhp($name)
    {
        $this->LoggerAppender($name);
    }

    function activateOptions()
    {
        return true;
    }

    function close() 
    {
        return true;
    }

    /**
     * trig a {@link LoggerEvent}
     *
     * @param object the {@link LoggerEvent} to append
     */
    function doAppend($event)
    {
        LoggerLog::log("LoggerAppenderPhp::doAppend()");
        $level = $event->getLevel();
        if ($level == LOGGER_LEVEL_DEBUG or $level == LOGGER_LEVEL_INFO) {
            trigger_error($this->layout->format($event), E_USER_NOTICE);
        } elseif ($level == LOGGER_LEVEL_WARN) {
            trigger_error($this->layout->format($event), E_USER_WARNING);
        } elseif ($level == LOGGER_LEVEL_ERROR or $level == LOGGER_LEVEL_FATAL) {
            trigger_error($this->layout->format($event), E_USER_ERROR);
        } else {
            LoggerLog::log("LoggerAppenderPhp::doAppend() level $level undefined");
        }            
    }
}
?>