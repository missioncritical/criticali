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

require_once('log4php/LoggerEvent.php');
require_once('log4php/LoggerLevel.php');
 
/**
 String constant designating no time information. Current value of
 this constant is <b>NULL</b>.
*/
define ('LOGGER_LAYOUT_NULL_DATE_FORMAT',   'NULL');

/**
 String constant designating relative time. Current value of
 this constant is <b>RELATIVE</b>.
*/
define ('LOGGER_LAYOUT_RELATIVE_TIME_DATE_FORMAT', 'RELATIVE');

/**
 * TTCC layout format consists of time, thread, category and nested
 * diagnostic context information, hence the name.
 * <p>Each of the four fields can be individually enabled or
 * disabled. The time format depends on the <code>DateFormat</code>
 * used.
 * @author Marco Vassura
 * @version $Revision: 1.4 $
 * @package log4php
 * @subpackage layout 
 */
class LoggerLayoutTTCC extends LoggerLayout {

    // Internal representation of options
    var $threadPrinting    = true;
    var $categoryPrefixing = true;
    var $contextPrinting   = true;
    
    /**
     * @var string date format. See {@link http://php.net/strftime} for details
     */
    var $dateFormat = '%c';

    /**
     * Constructor
     *
     * @param string date format
     * @see dateFormat
     */
    function LoggerLayoutTTCC($dateFormat = '')
    {
        if (!empty($dateFormat))
            $this->dateFormat = $dateFormat;
        return;
    }

    /**
     * The <b>ThreadPrinting</b> option specifies whether the name of the
     * current thread is part of log output or not. This is true by default.
     */
    function setThreadPrinting($threadPrinting)
    {
        $this->threadPrinting = (bool)$threadPrinting;
    }

    /**
     * Returns value of the <b>ThreadPrinting</b> option.
     */
    function getThreadPrinting() {
        return $this->threadPrinting;
    }

    /**
     * The <b>CategoryPrefixing</b> option specifies whether {@link Category}
     * name is part of log output or not. This is true by default.
     */
    function setCategoryPrefixing($categoryPrefixing)
    {
        $this->categoryPrefixing = (bool)categoryPrefixing;
    }

    /**
     * Returns value of the <b>CategoryPrefixing</b> option.
     */
    function getCategoryPrefixing() {
        return $this->categoryPrefixing;
    }

    /**
     * The <b>ContextPrinting</b> option specifies log output will include
     * the nested context information belonging to the current thread.
     * This is true by default.
     */
    function setContextPrinting($contextPrinting) {
        $this->contextPrinting = (bool)$contextPrinting;
    }

    /**
     * Returns value of the <b>ContextPrinting</b> option.
     */
    function getContextPrinting()
    {
        return $this->contextPrinting;
    }
    
    function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }
    
    function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * In addition to the level of the statement and message, the
     * returned string includes time, thread, category.
     * <p>Time, thread, category are printed depending on options.
     */
    function format($event)
    {
        $format = strftime($this->dateFormat, $event->timeStamp).' ';
        
        if ($this->threadPrinting) {
            $format .= '['.getmypid().'] ';
        }
        $format .= LoggerLevel::toString($event->getLevel()).' ';
        
        if($this->categoryPrefixing) {
            $format .= $event->getLoggerName();
        }
        
        $format .= ' - '.$event->getRenderedMessage();
        $format .= "\n";
        
        return $format;
    }

    function ignoresThrowable()
    {
        return true;
    }
}
?>