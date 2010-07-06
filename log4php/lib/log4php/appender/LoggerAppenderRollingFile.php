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
 
require_once('log4php/appender/LoggerAppenderFile.php');

/**
 * RollingFileAppender extends FileAppender to backup the log files when they reach a certain size.
 *
 * @author Marco Vassura
 * @version $Revision: 1.5 $
 * @package log4php
 * @subpackage appender 
 */
class LoggerAppenderRollingFile extends LoggerAppenderFile {

    /**
     * The default maximum file size is 10MB.
     */
    var $maxFileSize = 10485760;
    
    /**
     * There is one backup file by default.
     */
    var $maxBackupIndex  = 1;

    /**
     * The default constructor simply calls its parents constructor.  
     */
    function LoggerAppenderRollingFile($name)
    {
        $this->LoggerAppenderFile($name);
    }

    /**
     * Returns the value of the MaxBackupIndex option.
     */
    function getMaxBackupIndex() {
        return $this->maxBackupIndex;
    }

    /**
     * Get the maximum size that the output file is allowed to reach
     * before being rolled over to backup files.
     */
    function getMaximumFileSize() {
        return $this->maxFileSize;
    }

    /**
     * Implements the usual roll over behaviour.
     *
     * <p>If MaxBackupIndex is positive, then files File.1, ..., File.MaxBackupIndex -1 are renamed to File.2, ..., File.MaxBackupIndex. 
     * Moreover, File is renamed File.1 and closed. A new File is created to receive further log output.
     * 
     * <p>If MaxBackupIndex is equal to zero, then the File is truncated with no backup files created.
     *
     */
    function rollOver()
    {
        // If maxBackups <= 0, then there is no file renaming to be done.
        if($this->maxBackupIndex > 0) {
            // Delete the oldest file, to keep Windows happy.
            $file = $this->fileName . '.' . $this->maxBackupIndex;
            if (file_exists($file))
                unlink($this->fileName . '.' . $this->maxBackupIndex);
            // Map {(maxBackupIndex - 1), ..., 2, 1} to {maxBackupIndex, ..., 3, 2}
            for ($i = $this->maxBackupIndex - 1; $i >= 1; $i--) {
                $file = $this->fileName . "." . $i;
                if (file_exists($file)) {
                    $target = $this->fileName . '.' . ($i + 1);
                    rename($file, $target);
                }
            }
    
            // Rename fileName to fileName.1
            $target = $this->fileName . ".1";
    
            $this->closeFile(); // keep windows happy.
    
            $file = $this->fileName;
            rename($file, $target);
        }
        
        $this->setFile($this->fileName, false, $this->bufferedIO, $this->bufferSize);
        unset($this->fp);
        $this->activateOptions();
    }

    /**
     * Set the maximum number of backup files to keep around.
     * 
     * <p>The <b>MaxBackupIndex</b> option determines how many backup
     * files are kept before the oldest is erased. This option takes
     * a positive integer value. If set to zero, then there will be no
     * backup files and the log file will be truncated when it reaches
     * MaxFileSize.
     */
    function setMaxBackupIndex($maxBackups)
    {
        $this->maxBackupIndex = $maxBackups;
    }

    /**
     * Set the maximum size that the output file is allowed to reach
     * before being rolled over to backup files.
     *    
     * @see setMaxFileSize()
     */
    function setMaximumFileSize($maxFileSize)
    {
        $this->setMaxFileSize($maxFileSize);
    }

    /**
     * Set the maximum size that the output file is allowed to reach
     * before being rolled over to backup files.
     * <p>In configuration files, the <b>MaxFileSize</b> option takes an
     * long integer in the range 0 - 2^63. You can specify the value
     * with the suffixes "KB", "MB" or "GB" so that the integer is
     * interpreted being expressed respectively in kilobytes, megabytes
     * or gigabytes. For example, the value "10KB" will be interpreted
     * as 10240.
     */
    function setMaxFileSize($value)
    {
        $numpart = substr($value,0, strlen($value) -2);
        $suffix  = strtoupper(substr($value, -2));
        
        switch ($suffix) {
            case 'KB': $this->maxFileSize = ((int)$numpart) * 1024; break;
            case 'MB': $this->maxFileSize = ((int)$numpart) * 1024 * 1024; break;
            case 'GB': $this->maxFileSize = ((int)$numpart) * 1024 * 1024 * 1024; break;
            default:
                $this->maxFileSize = (int)$numpart;
        }
    }

    function doAppend($event)
    {
        parent::doAppend($event);
        
        if (ftell($this->fp) > $this->getMaximumFileSize())    
            $this->rollOver();
    }
}
?>