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

/**
 * ConsoleAppender appends log events to Stdout or Stderr using a layout specified by the user. 
 * The default target is Stdout.
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage appender 
 */
class LoggerAppenderConsole extends LoggerAppender {

    var $STDOUT = "php://stdout";
    var $STDERR = "php://stderr";
    
    var $target = "php://stdout";
    
    var $requiresLayout = true;
    
    var $fp = null;
    
    /**
     The default constructor does nothing.
    */
    function LoggerAppenderConsole($name)
    {
        return;    
    }

    /**
     *  Sets the value of the Target option.
     */
    function setTarget($value)
    {
        $v = trim($value);
        if ($v == $this->STDOUT) {
            $this->target = $this->STDOUT;
        } elseif ($v == $this->STDERR) {
            $target = $this->STDERR;
        } else {
            $this->targetWarn($value);
        }
    }

    /**
     * Returns the current value of the Target property.
     * See also {@link #setTarget}.
     */
    function getTarget()
    {
        return $this->target;
    }

    function targetWarn($val)
    {
        $warn = "[$val] should be {$this->STDOUT} or {$this->STDERR}. Using previously set target, {$this->STDOUT} by default.";
        LoggerLog::log("LoggerAppenderConsole::targetWarn() $warn");        
        trigger_error($warn, E_USER_WARNING);
    }

    function activateOptions()
    {
        if (!$this->fp)
            $this->fp = fopen($this->getTarget(), 'w');
    }
    
    function close()
    {
        @fclose($this->fp);
    }

    function doAppend($event)
    {
        LoggerLog::log("LoggerAppenderConsole::doAppend()");
        @fwrite($this->fp, $this->layout->format($event)); 
    }
}

?>