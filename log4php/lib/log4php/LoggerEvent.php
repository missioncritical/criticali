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
 
require_once('log4php/LoggerLevel.php');

/**
 * The internal representation of logging events.
 *
 * @author Marco Vassura
 * @version $Revision: 1.4 $
 * @package log4php
 */
class LoggerEvent {

    /**
     * @var string Fully qualified name of the calling category class.
     */
    var $fqnOfCategoryClass = ''; 
 
    /**
     * @var integer log level 
     */
    var $level;
    
    /**
     * @var string logger that created the event
     */
    var $loggerName = '';
    
    /**
     * @var string event message
     */
    var $message    = '';

    /**
     * @var integer The number of seconds elapsed from 1/1/1970 until logging event was created.
     */
    var $timeStamp;
    
    var $startTime; 
    
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/

    /**
     * Constructor
     *
     * @param string $fqnOfCategoryClass
     * @param object $logger Logger
     * @param integer $priority level
     * @param string $message message
     * @param integer $timestamp The number of seconds elapsed from 1/1/1970 until logging event was created.
     */
    function LoggerEvent($fqnOfCategoryClass, $logger, $priority, $message, $timestamp = 0)
    {
        $this->fqnOfCategoryClass = $fqnOfCategoryClass;
        $this->loggerName         = $logger->getName();
        $this->timestamp          = $timestamp;
        $this->level              = $priority;
        $this->message            = (string)$message;
        $this->timeStamp          = ($timestamp) ? $timestamp : time();
        $this->startTime          = time();
    } 
    
    function getLevel()
    {
        return $this->level;
    } 

    /**
     * get the location information for this logging event. (not used)
     */
    function getLocationInformation()
    {
        return;
    } 

    function getLoggerName()
    {
        return $this->loggerName;
    } 

    function getMessage()
    {
        return $this->message; 
    }
    
    function getRenderedMessage()
    {
        return $this->getMessage();
    }
    
    /**
     * output script pid 
     */
    function getThreadName()
    {
        return (string)getmypid();
    }

}

?>