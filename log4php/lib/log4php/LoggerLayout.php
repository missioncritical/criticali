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

/**
 * Extend this abstract class to create your own log layout format.
 *  
 * @author Marco Vassura
 * @version $Revision: 1.4 $
 * @package log4php
 */
class LoggerLayout {

    function &factory($class)
    {
        $class = basename($class);
        include_once("log4php/layout/$class.php");
        $object =& new $class();
        return $object;
    }

    function activateOptions() 
    {
        // override;
    }

    /**
     * Override this method to create your own layout format.
     */
    function format($event)
    {
        return $event->getRenderedMessage();
    } 
    
    /**
     * Returns the content type output by this layout.
     */
    function getContentType()
    {
        return;
    } 
            
    /**
     * Returns the footer for the layout format.
     */
    function getFooter()
    {
        return;
    } 

    /**
     * Returns the header for the layout format.
     */
    function getHeader()
    {
        return;
    }
}
?>