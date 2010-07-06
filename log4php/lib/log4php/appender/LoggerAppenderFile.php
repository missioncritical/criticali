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
 *  FileAppender appends log events to a file.
 *
 * @author Marco Vassura
 * @version $Revision: 1.5 $
 * @package log4php
 * @subpackage appender 
 */
class LoggerAppenderFile extends LoggerAppender {

    var $fp;
    var $requiresLayout = true;
    var $fileAppend = true;  
    var $fileName;
    
    function LoggerAppenderFile($name)
    {
        $this->LoggerAppender($name);
    }

    /**
     * If the value of File is not null, then setFile(java.lang.String) is called with the values of File and Append properties.
     */
    function activateOptions()
    {
        if (!isset($this->fp))
            $this->fp = fopen($this->getFile(), ($this->getAppend()? 'a':'w'));
        if (!$this->getAppend()) {
            @fwrite($this->fp, $this->layout->getHeader());
        }            
    }
    
    function close()
    {
        if (!$this->getAppend()) {
            @fwrite($this->fp, $this->layout->getFooter());
        }            
        return $this->closeFile();
    }

    /**
     * Closes the previously opened file.
     */
    function closeFile() 
    {
        if (isset($this->fp)) {
            @fclose($this->fp);
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
     * Returns the value of the File option.
     */
    function getFile()
    {
        return $this->fileName;
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
        $this->fileAppend = (bool)(strtolower(trim($flag)) == 'true');        
    } 
  
    /**
     * The File property takes a string value which should be the name of the file to append to.
     * Sets and opens the file where the log output will go.
     */
    function setFile()
    {
        $numargs = func_num_args();
        $args    = func_get_args();

        if ($numargs == 1 and is_string($args[0])) {
            $this->fileName = (string)$args[0];
        } elseif ($numargs == 4 and is_string($args[0]) and is_bool($args[1])) {
            $this->setFile($args[0]);
            $this->setAppend($args[1]);
        }
    } 

    function doAppend($event)
    {
        LoggerLog::log("LoggerAppenderFile::doAppend()");
        @fwrite($this->fp, $this->layout->format($event)); 
    }
}
?>