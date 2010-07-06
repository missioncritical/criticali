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

require_once('log4php/LoggerLayout.php');
require_once('log4php/LoggerLevel.php');

/**
 * A simple layout.
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage layout  
 */  
class LoggerLayoutSimple extends LoggerLayout {
    
    function LoggerLayoutSimple()
    {
        return;
    }

    function activateOptions() 
    {
        return;
    }

    /**
     * Returns the log statement in a format consisting of the
     * <code>level</code>, followed by " - " and then the
     * <code>message</code>. For example, <pre> INFO - "A message" </pre>
     * <p>The <code>category</code> parameter is ignored.
     * <p>
     * @return string
     */
    function format($event)
    {
        return LoggerLevel::toString($event->getLevel()).' - '.$event->getRenderedMessage()."\n";
    }
}
?>