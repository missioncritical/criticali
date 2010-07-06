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

require_once('log4php/LoggerManager.php');

/**
 * Helper class for internal logging
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 */
class LoggerLog {

    /**
     * log using trigger_error function with E_USER_NOTICE level
     *
     * @param string $message log message
     * @param integer $errLevel level to log
     * @static
     */
    function log($message, $errLevel = E_USER_NOTICE)
    {
        $hierarchy =& LoggerManager::getLoggerRepository();
        if ($hierarchy->debug)
            trigger_error($message, $errLevel);
    }

}
?>