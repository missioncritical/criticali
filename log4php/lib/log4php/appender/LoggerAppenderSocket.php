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

/**
 * Log event to a network socket
 *
 * @author Marco Vassura
 * @version $Revision: 1.6 $
 * @package log4php
 * @subpackage appender 
 */ 
class LoggerAppenderSocket extends LoggerAppender {

    /**
     * @var resource socket connection resource
     */
    var $sp = 0;
    
    /**
     * on how to define an hostname see {@link http://php.net/fsockopen} 
     * @var string target host
     */
    var $hostname       = '';
    
    /**
     * @var integer network port
     */
    var $port           = null;
    
    /**
     * @var integer connection timeout
     */
    var $timeout        = 30;
    
    /**
     * Constructor
     *
     * @param string $name appender name
     */
    function LoggerAppenderSocket($name)
    {
        $this->LoggerAppender($name);
    }

    /**
     * create a socket connection using defined parameters
     * @return boolean
     */
    function activateOptions()
    {
        if (!$this->sp) {
            LoggerLog::log("LoggerAppenderSocket::activateOptions() creating a socket...");        
            $errno = 0;
            $errstr = '';
            $this->sp = fsockopen($this->hostname, $this->port, $errno, $errstr, $this->timeout);
            if ($errno) {
                $this->sp = 0;
                LoggerLog::log("LoggerAppenderSocket::activateOptions() socket error [$errno] $errstr");
                return false;
            } else {
                LoggerLog::log("LoggerAppenderSocket::activateOptions() socket created [".$this->sp."]");
                return true;
            }
        }
    }
    
    function close()
    {
        $this->closeFile();
    }

    /**
     * Closes the previously opened file.
     */
    function closeFile() 
    {
        if (isset($this->sp)) {
            @fclose($this->sp);
        }
    }
    
    /**
     * Returns the value of the Append option.
     */
    function getAppend()
    {
        return $this->fileAppend;
    }

    /**
     * Get the value of the BufferedIO option.
     */    
    function getHostname()
    {
        return $this->hostname;
    } 

    /**
     * Get the size of the IO buffer.
     */  
    function getPort()
    {
        return $this->bufferSize;
    } 
 
    /**
     * Close any previously opened file and call the parent's reset.
     */
    function reset()
    {
        $this->closeFile();
        $this->fileName = null;
        parent::reset();
    }

    /**
     * The Append option takes a boolean value.
     */           
    function setAppend($flag)
    {
        $this->fileAppend = (bool)$flag;        
    } 
  
    /**
     * The BufferedIO option takes a boolean value.
     */
    function setHostname($hostname)
    {
        $this->hostname = $hostname;
    } 
            
    /**
     * Set the size of the IO buffer.
     */
    function setPort($port)
    {
        $this->port = $port;    
    }
    
    function setTimeout($timeout)
    {
        $this->timeout = $timeout;    
    } 

    /**
     * Appends a {@link LoggerEvent}
     *
     * @param object the {@link LoggerEvent} to append
     */
    function doAppend($event)
    {
        LoggerLog::log("LoggerAppenderSocket::doAppend()");
        if (!$this->sp) {
            LoggerLog::log("LoggerAppenderSocket::doAppend() socket error [$errno] $errstr");
        } else {
            $sEvent = serialize($event);
            @fwrite($this->sp, $sEvent, strlen($sEvent));
            fflush ($this->sp);
        } 
    }
}

?>