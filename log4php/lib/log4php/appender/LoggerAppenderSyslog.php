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
 * Log event using php syslog() function 
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage appender 
 */ 
class LoggerAppenderSyslog extends LoggerAppender {
    
    /**
     * Constructor
     *
     * @param string $name appender name
     */
    function LoggerAppenderSyslog($name)
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
     * syslog a {@link LoggerEvent}
     *
     * @param object the {@link LoggerEvent} to append
     */
    function doAppend($event)
    {
        LoggerLog::log("LoggerAppenderSyslog::doAppend()");
        $level = $event->getLevel();
        switch ($level) {
            case LOGGER_LEVEL_DEBUG:
                syslog(LOG_DEBUG, $event->getRenderedMessage()); break;
            case LOGGER_LEVEL_INFO:
                syslog(LOG_INFO, $event->getRenderedMessage()); break;
            case LOGGER_LEVEL_WARN:
                syslog(LOG_WARNING, $event->getRenderedMessage()); break;            
            case LOGGER_LEVEL_ERROR:
                syslog(LOG_ERROR, $event->getRenderedMessage()); break;            
            case LOGGER_LEVEL_FATAL:
                syslog(LOG_ALERT, $event->getRenderedMessage()); break;
            default:
                LoggerLog::log("LoggerAppenderSyslog::doAppend() level $level undefined");            
        }
    }
}
?>